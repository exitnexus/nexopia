module Acts
	module As
		module URI

			def self.append_features(base) # :nodoc:
				super
				base.extend ClassMethods
			end

			#
			# Any Methods in this Module will be include as Instance Methods
			#
			#
			module InstanceMethods
				#
				#
				# Generate a uri to the file location based on the following methods
				#
				#
				# uri_protocol : (Defaults to empty "" )
				#
				#	site_address : (Defaults to "/" )
				# uri_spec  : Array of methods to call  (Defaults to [:protocol,:site_address])
				#
				# This would end up with a default address of "/"
				#Å
				def to_uri
					unless '' == protocol.to_s
						protocol + "//" + uri_spec.join("/")
					else
						site_address + uri_spec.join("/")
					end
				end

				# This method returns an array consiting of
				# description and uri
				#
				#
				def uri_info(mode)
					if(mode == linktype)
						[description,to_uri]
					end
				end
				
				def link
					return "<a href='#{to_uri}'>#{description}</a>";
				end

			end
			#
			# Any Methods in this module will become part of the Class's methods
			#
			module ClassMethods
				
				#
				# Convert an Array construct 
				# using _convert_typ
				def _convert_array(array)
						"["+"#{array.collect{|v|
								_convert_type(v)					
						}.join(",")}"+"]"
				end
				#
				# Converts input to code that can
				# be evaled.
				#
				def _convert_type(value)
					#p value
					case value
						when Symbol
							value
						when Array
						 	_convert_array(value)							
						else 
							"'#{value.to_s}'"
					end
				end
				def _create_method(key,value) # :nodoc:
					#
					# This little bit of ruffage generates a method that returns
					# a string, or the result of executing a method with the same
					# name as symbol on the class that 'acts_as_uri' is used on.
					#
					#p key, value
					replacement = _convert_type(value)

					%(
					def #{key.to_s}
						obj = #{replacement}
					end
					)
				end

				#
				# Create a bunch of methods that map to other methods or some string
				# or an array of of method responces
				#{ method_name => String or MethodName}
				#
				def _create_methods_from_options(options = {}) # :nodoc:

					methods =[]
					options.each_pair { |key,value|

						methods << _create_method(key,value)

					}
					class_eval <<-EOV
					#{methods.join("\n")}
					EOV

				end

				def _setup_defaults(options) # :nodoc:
					options[:site_address] = "/" if options[:site_address].nil?
					options[:uri_spec] = [:site_address] if options[:uri_spec].nil?
					options[:protocol] = nil if options[:protocol].nil?
					options[:linktype] = "self" if options[:linktype].nil?
				end

				#
				# Set up how this object constructs uri's to the resource that it represents
				#
				#* This method takes a hash of options.
				#* The special options are:
				#[:uri_spec] An array of symbols that represent methods. These methods are called in order and joined by '/'
				#  to form a path to the uri resource. If this is unspecified it defaults to [:site_address].
				#[:site_address] The Server address. If you do not specify this it defaults to "/"
				#[:protocol]  The protocol that we are using for the uri spec.
				#  If it exists it is pre-pended to the the result of processing :uri_spec
				#[:linktype] Specifies the value of the mode argument for the resulting uri. This is needed
				#  because the method signature changed. 	
				#[:anything] Like the above whatever option you pass in becomes a method in the class that you
				#  are using. In this way you can formulate a uri_spec out of existing methods or created methods
				#  or whatever makes sense in the context your using it in.
				#
				#* The symbol options are mapped to whatever. Special cases are Array and Symbol.
				#* Symbol turns into a method call
				#* Array turns into an array of method calls (or Strings)
				#
				#* The caller is responsible for fully creating the uri_spec excluding the protocol
				#==Example
				#   acts_as_uri(
				#   :uri_spec => [:site_address,:history_sep,:resource_name,:resource_arguments],
				#   :history_sep =>"#",
				#   :resouce_name = self.class.name.to_s,
				#   :resouce_arguments => :user_id)
				#
				def acts_as_uri(options = {})
					_setup_defaults(options)
					_create_methods_from_options(options)

					class_eval <<-EOV
					include InstanceMethods
					EOV

				end

			end # module ClassMethods

		end #module URI
	end
end
class Storable
	include Acts::As::URI
	
	def link
		return "<a href='#{uri_info[1]}'>#{uri_info[0]}</a>";
	end

end

