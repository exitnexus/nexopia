lib_require :Core, "data_structures/boolean"

class Module
	#define name and name= to wrap around a Boolean type, assignments are interpretted and saved as boolean values..
	def bool_attr(name, bool=false)
		if (bool == nil)
			bool = false;
		end
		self.send(:define_method, :"#{name}") {
			if (instance_variables.include?("@#{name}"))
				return instance_variable_get(:"@#{name}").symbol;
			else
				instance_variable_set(:"@#{name}", Boolean.new(bool));
				return instance_variable_get(:"@#{name}").symbol;
			end
		}

		self.send(:define_method, :"#{name}=") { |symbol|
			if (instance_variables.include?("@#{name}"))
				instance_variable_get(:"@#{name}").symbol = symbol;
			else
				instance_variable_set(:"@#{name}", Boolean.new(symbol));
			end
		}
	end
end
