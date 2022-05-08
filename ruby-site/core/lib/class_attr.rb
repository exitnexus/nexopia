class Module
	#define @@<classname>_symbol and provide a reader for it and a writer if writer==true.
	def class_attr(symbol, writer=false)
		class_attr_reader(symbol);
		class_attr_writer(symbol) if writer;
	end
	
	#define @@<classname>_symbol for each symbol in syms and provide a reader for it and a writer for it
	def class_attr_accessor(*syms)
		class_attr_reader(*syms);
		class_attr_writer(*syms);
	end
	
	#define @@<classname>_symbol for each symbol in syms and provide a reader for it
	def class_attr_reader(*syms)
		syms.flatten.each { |sym|
			class_eval(%Q/
				unless defined? @@#{sym};
					@@#{self.to_s}_#{sym} = nil;
				end
				
				def self.#{sym}
					return @@#{self.to_s}_#{sym};
				end
				
				def #{sym}
					return @@#{self.to_s}_#{sym}
				end
			/, __FILE__, __LINE__);
		}
	end
	
	#define @@<classname>_symbol for each symbol in syms and provide a writer for it
	def class_attr_writer(*syms)
		syms.flatten.each { |sym|
			class_eval(%Q/
				unless defined? @@#{sym};
					@@#{self.to_s}_#{sym} = nil;
				end
				
				def self.#{sym}=(value)
					@@#{self.to_s}_#{sym}=value;
				end
				
				def #{sym}=(value)
					@@#{self.to_s}_#{sym}=value;
				end
			/, __FILE__, __LINE__);
		}
	end
end