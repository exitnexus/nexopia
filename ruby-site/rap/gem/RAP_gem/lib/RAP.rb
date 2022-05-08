#!/usr/bin/env ruby
#
#  Created by Sean Healy
#  sean@pointscape.org
#
#  RAP.rb
#  ------
#  Class for interacting with PHP from ruby.
#
###

class RAP
	require "RAP_clib"
	include RAP_clib
	
	def initialize(config)
		@config = Hash.new(nil)
		
		@config[:path]   = config[:path]   if config[:path].is_a? String
		@config[:errors] = config[:errors] if config[:errors] = true
		
		@callback_classes = Hash.new(nil)
		@callback_classes_generated = Hash.new(nil)
		php_register_object self
	
		@current_working_directory = Dir.getwd
		@in_php = false
	end
	
	def self.php_context(config)
		php = new(config)
		php.php_begin
		yield php 
		php.php_end
	end
	
	def self.about
		version = "0.1.7"
		
		"<html><head><title>RAP #{version}</title></head><body style='font-family: sans-serif; background-color: #222; color: #aad; text-align: center; margin-left: 20%; margin-right: 20%;'><div style='border-bottom: #f55 dashed 3px; margin-bottom: 10px; padding-bottom: 10px; padding-top: 10px; font-size: 40pt;'>RAP #{version}</div><div style='font-size: 15pt;'>Created by Sean Healy at Nexopia.com, maintained by Chris Thompson at Nexopia.com</div><div style='font-size: 9pt; padding-top: 20px;'>cthompson@nexopia.com <br />cthompson@nexopia.com<div></body></html>"
	end
	
	class PHPRequest
		attr :cookies, true
		attr :get, true
		attr :post, true
		attr :server, true
		attr :files, true
		attr :env, true
		attr :ruby, true
		attr :globals, true
	end
	
	def exec(code, request = nil)
	
		raise "RAP is not re-entrant, so cannot be called from within itself!" if @in_php
		@in_php = true

		@callback_classes["RAP"] = self

		headers = ""
		log = ""
		result = ""
		if @config[:path]
			@current_working_directory = Dir.getwd #save ruby's working directory
			Dir.chdir @config[:path] #set to php's working directory
		end
		begin
			output = ""
			headers = ""
			errors = ""
			pre = "include_once 'RAP_RubyObject.php';\n"

			@callback_classes.each { |key, value|
				pre << "$#{key} = new RubyObject('#{key}');\n"
			}

			php_exec(code, pre, output, headers, errors, request)
		ensure
			Dir.chdir @current_working_directory if @config[:path] #restore ruby's working directory
			@callback_classes_generated.clear
			@callback_classes.clear
			@in_php = false
		end
		
		return {:output => output, :errors => errors, :headers => headers}
	end
	
	# Register an object so it can be called from Php.
	# Name cannot be the string of a number; meaning that "42" is invalid.
	def register_object(object, name)
		name = name.to_s
		if(name =~ /^[0-9]/)
			raise "INVALID PASSTHROUGH NAME, may not start with a number"
		else
			@callback_classes[name] = object
		end
	end
	
	# Register an unnamed object so it can be called from Php.
	# We return the generated name of the object, which will be a string
	# version of a number, such as "42".  This is guaranteed to be
	# unique for each call.
	def register_proxy_obj(obj)
		internal_name = @callback_classes_generated.length.to_s
		@callback_classes_generated[internal_name] = obj
		return internal_name
	end
	
	def call(klass, method, parameters)
		Dir.chdir @current_working_directory if @config[:path] #restore ruby's working directory

		found_klass = @callback_classes[klass]
		found_klass = @callback_classes_generated[klass] unless found_klass
		
		err = nil
		
		if(found_klass)
			begin
				result = found_klass.send(method.to_sym, *parameters)
			rescue
				$log.info "RAP Error: #{$!}", :error
				$log.info "RAP Error Backtrace: #{$!.backtrace}", :error
				result = nil
				err = $!.to_s
			end
		else
			$log.info "RAP: No object #{klass} with method #{method}", :error
			result = nil
			err = "No object #{klass} with method #{method}";
		end
		
		if(result.is_a?(FalseClass) or result.is_a?(TrueClass) or
		 	result.is_a?(NilClass) or result.is_a?(Fixnum) or 
			result.is_a?(Bignum) or result.is_a?(Float) or
			result.is_a?(String) or result.is_a?(Array) or result.is_a?(Hash))
			
			out = {:result => result, :type => "passed", :error => err}
		else
			internal_name = register_proxy_obj(result)
			out = {:result => internal_name, :type => "wrapped", :error => err}
		end

		Dir.chdir @config[:path] if @config[:path] #reset to php's working directory

		return out
	end
	
	def test_call(msg)
		return msg
	end
	
	def test_call_add(a, b)
		return a + b
	end
end
