lib_require :Core, 'data_structures/enum_map'

class Module
	#define name and name= to wrap around an Enum type, if a symbol is invalid it will raise an error on the assignment.
	def enum_map_attr(name, hash)
		self.send(:define_method, :"#{name}") {
			if (instance_variables.include?("@#{name}"))
				return instance_variable_get(:"@#{name}").symbol;
			else
				instance_variable_set(:"@#{name}", EnumMap.new(hash.keys.first, hash));
				return instance_variable_get(:"@#{name}").symbol;
			end
		}

		self.send(:define_method, :"#{name}!") {
			if (instance_variables.include?("@#{name}"))
				return instance_variable_get(:"@#{name}");
			else
				instance_variable_set(:"@#{name}", EnumMap.new(hash.keys.first, hash));
				return instance_variable_get(:"@#{name}");
			end
		}
		
		self.send(:define_method, :"#{name}=") { |symbol|
			if (instance_variables.include?("@#{name}"))
				instance_variable_get(:"@#{name}").symbol = symbol;
			else
				instance_variable_set(:"@#{name}", EnumMap.new(symbol, hash));
			end
		}
	end
end
