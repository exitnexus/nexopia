lib_require :Core, "data_structures/enum"

class Module
	#define name and name= to wrap around an Enum type, if a symbol is invalid it will raise an error on the assignment.
	def enum_attr(name, *syms)
		syms.flatten!

		variable_name = :"@#{name}"

		self.send(:define_method, :"#{name}") {
			if (instance_variable_defined?(variable_name))
				return instance_variable_get(variable_name).symbol;
			else
				instance_variable_set(variable_name, Enum.new(syms.first, syms));
				return instance_variable_get(variable_name).symbol;
			end
		}

		self.send(:define_method, :"#{name}=") { |symbol|
			if (instance_variable_defined?(variable_name))
				instance_variable_get(variable_name).symbol = symbol;
			else
				instance_variable_set(variable_name, Enum.new(symbol, syms));
			end
		}
	end
end
