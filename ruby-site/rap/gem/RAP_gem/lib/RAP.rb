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
	
	def initialize config
		@config = Hash.new(nil)
		
		@config[:path]   = config[:path]   if config[:path].is_a? String
		@config[:errors] = config[:errors] if config[:errors] = true
		
		@callback_classes = Hash.new(nil)
		@callback_classes_generated = Hash.new(nil)
		php_register_object self
		@callback_classes["RAP"] = self
	end
	
	def self.php_context(config)
		php = new(config)
		php.php_begin
		yield php 
		php.php_end
	end
	
	def self.about
		version = "0.1.0"
		
		"<html><head><title>RAP #{version}</title></head><body style='font-family: sans-serif; background-color: #222; color: #aad; text-align: center; margin-left: 20%; margin-right: 20%;'><div style='border-bottom: #f55 dashed 3px; margin-bottom: 10px; padding-bottom: 10px; padding-top: 10px; font-size: 40pt;'>RAP #{version}</div><div style='font-size: 15pt;'>Created by Sean Healy at Nexopia.com</div><div style='font-size: 9pt; padding-top: 20px;'>sean@pointscape.org <br />sean@nexopia.com<div></body></html>"
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
	
	def exec code, request = nil	
		headers = ""
		log = ""
		result = ""
		current_working_directory = Dir.getwd if @config[:path]
		Dir.chdir @config[:path] if @config[:path]
		begin
			output = ""
			headers = ""
			errors = ""
			pre = "include 'RAP_RubyObject.php';\n"
			
			@callback_classes.each do |key, value|
				pre << "$#{key} = new RubyObject('#{key}');\n"
			end
			
			php_exec(code, pre, output, headers, errors, request)
		ensure
			Dir.chdir current_working_directory if @config[:path]
			@callback_classes_generated.clear
		end
		
		return {:output => output, :errors => errors, :headers => headers}
	end
	
	# Name cannot be the string of a number; meaning that "42" is invalid.
	def register_object object, name
		if name =~ /^[0-9]/
			raise "INVALID PASSTHROUGH NAME"
		else
			@callback_classes[name] = object
		end
	end
	
	def call klass, method, parameters
		found_klass = @callback_classes[klass]
		found_klass = @callback_classes_generated[klass] unless found_klass
		
		if found_klass then
			result = found_klass.send method.to_sym, *parameters
			
			if result.is_a? FalseClass or result.is_a? TrueClass or result.is_a? NilClass or result.is_a? Fixnum or result.is_a? Float or result.is_a? String or result.is_a? Array or result.is_a? Hash then
				out = {:result => result, :type => "passed"}
			else
				internal_name = @callback_classes_generated.length.to_s
				@callback_classes_generated[internal_name] = result
				out = {:result => internal_name, :type => "wrapped"}
			end
		else
			out = nil
		end
		
		return out
	end
	
	def test_call msg
		return msg
	end
	
	def test_call_add a, b
		return a + b
	end
end
