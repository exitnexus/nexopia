lib_require :core, "text_manip"
require 'bbcode'
require 'cgi'

module UserContent
	@@content_converters = Hash.new

	module UserContentString
		attr_accessor(:conversions, :scope)
		def parsed
			result = self
			self.conversions.each {|converter|
				result = converter.convert(result, scope)
			}
			return result
		end
	end
	
	# accessor should be a symbol with the name of the field that contains user content, generally a column name
	# *args takes arguments in the form of :converter_name [=> (true|false|:field|&block)]
	# if just the name is passed in true is assumed, passing in a symbol for a field checks that field at runtime
	# passing in a block executes that block at runtime in the scope of the storable instance (or equivalent if UserContent is used outside of Storable)
	def user_content(accessor, *args)
		hashes = []
		conversions = @@content_converters
		args.each {|conversion|
			if (conversion.kind_of?(Hash))
				hashes << conversion
			else
				conversions[conversion] = conversions[conversion].new_from_prototype(:default => true)
			end
		}
		hashes.each {|hash|
			hash.each_pair{|conversion, value|
				if (value.kind_of? Symbol)
					conversions[conversion] = conversions[conversion].new_from_prototype(:default => lambda {self.send(value)})
				else
					conversions[conversion] = conversions[conversion].new_from_prototype(:default => value)
				end
			}
		}
		conversions = conversions.values.sort_by {|conversion| conversion.priority}
		postchain_method(accessor, &lambda { |original|
			original.extend UserContentString
			original.conversions = conversions
			original.scope = self
			original
		})
	end
	
	class << self
		def register_converter(name, method, default=false, priority=ContentConverter::DEFAULT_PRIORITY)
			@@content_converters[name] = ContentConverter.new(name, method, default, priority)
		end
		
		def content_converters()
			return @@content_converters;
		end
	end
	
	class ContentConverter
		PREPARSES_HTML = -2
		PARSES_HTML = -1
		DEFAULT_PRIORITY = 0
		GENERATES_HTML = 1

		attr_accessor :name, :default, :method, :priority

		def initialize(name, method, default, priority)
			self.name = name
			self.method = method
			self.default = default
			self.priority = priority
		end
		
		#this function could be extracted into a general prototype framework easily if desired
		def new_from_prototype(hash)
			new_obj = self.class.new(self.name, self.method, self.default, self.priority)
			hash.each_pair {|key, value|
				if (new_obj.respond_to? key.to_sym)
					new_obj.send("#{key}=".to_sym, value)
				end
			}
			return new_obj
		end
		
		def convert(input, scope)
			convert = false
			if (self.default.respond_to? :call)
				convert = scope.instance_eval(&self.default)
			else
				convert = self.default
			end
			if (convert)
				result = self.method.call(input)
			else
				result = input
			end
			return result
		end
	end
	register_converter(:nl2br, lambda {|string| return string.gsub("\n", "<br/>")}, true, ContentConverter::GENERATES_HTML)
	register_converter(:htmlescape, lambda {|string| return CGI::escapeHTML(string)}, true, ContentConverter::PARSES_HTML)
	
end