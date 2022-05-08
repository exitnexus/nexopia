module Template
	# A 'Processor' handles all the tags and attributes under a namespace.
	# If you want to make a new namespace, you need to make a new Processor.
	# See DefaultView.rb and LiteralView.rb
	class Processor
		def self.inherited(othermod)
			@@processor_classes ||= []
			@@processor_classes << othermod
			@@processor_classes.uniq!
			othermod.extend TypeID unless othermod.respond_to?(:typeid)
		end
		
		def self.show_in_html(bool)
			@show_in_html = bool;
		end
		def self.show_in_html?()
			return @show_in_html;
		end

		def self.namespace(symbol)
			@@processor_map ||= {}
			@@processor_map[symbol.to_s] = self
		end

		def self.classes
			return @@processor_classes;
		end
		
		def self.for_namespace(symbol)
			if (@@processor_map.key? symbol.to_s)
				return @@processor_map[symbol.to_s];
			end
			return @@processor_map[""];
		end
		
		class << self
			def method_missing(name, *args, &block)
				name = name.to_s;
				if (match = /^translate_(.*)$/.match(name))
					return translate_default(*args,&block) #{|*yargs| yield(*yargs); };
				elsif (match = /^attribute_translate_(.*)$/.match(name))
					return attribute_translate_default(*args,&block) #{|*yargs| yield(*yargs); };
				else
					raise "WTF? #{name}"
					#super(name, *args);
				end
			end
	
			def attribute_translate_default(ns,attr,code)
				code.append_print " #{ns}:#{attr.name}=\"#{attr.value}\""
			end
			
			def translate_default(element,code)
				yield element;
			end
	
		end
	end
end