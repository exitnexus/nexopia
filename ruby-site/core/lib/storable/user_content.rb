lib_require :core, "text_manip"
require 'hpricot'

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
		conversions = @@content_converters.clone
		args.each {|conversion|
			if (conversion.kind_of?(Hash))
				hashes << conversion
			else
				conversions[conversion] = conversions[conversion].new_from_prototype(:default => true) if conversions[conversion]
			end
		}
		hashes.each {|hash|
			hash.each_pair{|conversion, value|
				if (value.kind_of? Symbol)
					conversions[conversion] = conversions[conversion].new_from_prototype(:default => lambda {self.send(value)}) if conversions[conversion]
				else
					conversions[conversion] = conversions[conversion].new_from_prototype(:default => value) if conversions[conversion]
				end
			}
		}
		conversions = conversions.values.sort_by {|conversion| conversion.priority}.compact
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
		PROCESSES_HTML = 2

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
	
	
	def self.allowed_html
		@allowed_html = @allowed_html || {
			"a" => {"href" => /^(https?:\/\/|mailto:|\/).{1,500}$/, "target" => /^_new$/, "class" => /^(body)?\s*(user_content)?/},
			"img" => {"src" => /^(https?:\/\/|\/).{1,500}$/, "minion_name" => /^(user_content_image)?$/, "url" => /^(https?:\/\/|\/).{1,500}$/, "width" => /^[0-9]{1,3}%?$/, "height" => /^[0-9]{1,3}%?$/, "border" => /^0$/},
			"font" => {"size" => /^[1-7]$/, "color" => /^[#a-zA-Z0-9]{1,16}$/, "face" => /.{1,24}/},
			"b" => {},
			"i" => {},
			"u" => {},
			"sub" => {},
			"sup" => {},
			"strike" => {},
			"center" => {},
			"hr" => {},
			"wbr" => {},
			"br" => {},
			"ul" => {},
			"ol" => {"start" => /^[0-9a-zA-Z]{1,3}$/, "type" => /^[AaIi1]$/},
			"li" => lambda { |elem, str, block| 
								if(elem.xpath =~ /ul|ol/)  
									str << elem.to_html;
								else
									str << "<ul>" + elem.to_html + "</ul>";
								end
							}, # If it's a list element and has no ul or ol ancestor, wrap it in a <ul></ul> to make it valid
			"em" => {},
			"strong" => {},
			"div" => {"style" => /^text\-align:(center|left|right|justify)$/, "class" => /^(quote)$/, "align" => /^center$/, "width" => /^[0-9]{1,3}$/, "height" => /^[0-9]{1,3}$/},
		}
		return @allowed_html
	end
	
	def self.add_allowed_html(key, value)
		allowed_html[key] = value
	end


	def self.html_filter(string)
		return string unless string['<']

		doc = Hpricot(string)
		result = StringIO.new("")

		clean_attrs = lambda {|clean_elem, safe_attr|
			clean_elem.attributes.each {|key, val|
				if (!safe_attr[key] || !safe_attr[key].match(val))
					clean_elem.remove_attribute(key)
				end
			}
		}
		do_node = lambda {|elem, level_str|
			case elem
			when Hpricot::Text
				level_str << elem.to_html.gsub('<', '&lt;')
			when Hpricot::Comment
				level_str << htmlencode(elem.to_s)
			when Hpricot::Elem
				valid = allowed_html[elem.name]
				if (valid.respond_to?(:call) && valid.arity == 3)
					res = valid.call(elem, level_str, lambda {|elem_child| do_node.call(elem_child, level_str)})
					if (res)
						next # we assume the callee did all the processing necessary.
					else
						valid = nil # treat it as 'invalid'
					end
				end
				if (elem.empty?)
					if (!valid)
						# not valid html as we see it
						level_str << htmlencode(elem.to_s)
					else
						clean_attrs.call(elem, valid)
						level_str << elem.to_html
					end
				else
					if (!valid)
						level_str << htmlencode(elem.stag.inspect)
					else
						clean_attrs.call(elem, valid)
						level_str << elem.stag.inspect
					end
					elem.search('/*').each {|child_elem|
						do_node.call(child_elem, level_str)
					}
					if (!valid)
						level_str << htmlencode(elem.etag.inspect) if elem.etag # don't add an escaped copy of what wasn't actually there
					else
						level_str << "</#{elem.name}>" # close an unbalanced tag even if the user didn't.
					end
				end
			end
		}
		doc.search('/*').each {|doc_elem|
			do_node.call(doc_elem, result)
		}

		return result.string
	rescue
		# there was an error in the html, so just htmlentities the whole thing.
		return %Q{<strong class="html_error">[There was an error in the html below]</strong>#{htmlencode(string)}}
	end
	
	register_converter(:nl2br, lambda {|string| return string.gsub("\n", "<br/>")}, true, ContentConverter::GENERATES_HTML)
	register_converter(:htmlescape, lambda {|string| return html_filter(string)}, true, ContentConverter::PROCESSES_HTML)
	register_converter(:wrap, lambda {|string|	return string.wrap(50)}, true, UserContent::ContentConverter::PROCESSES_HTML+0.5)
	
	
end
