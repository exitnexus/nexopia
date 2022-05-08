module Kernel
	if (methods.include?(:funcall))
		alias send funcall
	end
end

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
		self_name = self.to_s;
		self_name.gsub!(':', '_');

		syms.flatten.each { |sym|
			if (!class_variable_defined?("@@#{self_name}_#{sym}"))
				class_variable_set(:"@@#{self_name}_#{sym}", nil);
			end

			self.send(:define_method, sym) {
				return self.class.send(sym);
			}

			Thread.current['class_attr_temp'] = [self_name, sym];
			class << self
				self_name, sym = ::Thread.current['class_attr_temp'];
				variable_name = :"@@#{self_name}_#{sym}"
				self.send(:define_method, sym) {
					class_variable_get(variable_name);
				}
			end
		}
	end


	#define @@<classname>_symbol for each symbol in syms and provide a writer for it
	def class_attr_writer(*syms)
		self_name = self.to_s;
		self_name.gsub!(':', '_');

		syms.flatten.each { |sym|
			self.send(:define_method, :"#{sym}=") { |value|
				self.class.send(:"#{sym}=", value);
			}

			Thread.current['class_attr_temp'] = [self_name, sym];
			class << self
				self_name, sym = ::Thread.current['class_attr_temp'];
				variable_name = :"@@#{self_name}_#{sym}"
				self.send(:define_method, :"#{sym}=") { |value|
					class_variable_set(variable_name, value);
				}
			end
		}
	end
end
