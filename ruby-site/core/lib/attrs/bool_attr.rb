lib_require :Core, "data_structures/boolean"

class Module
	#define name and name= to wrap around a Boolean type, assignments are interpretted and saved as boolean values..
	def bool_attr(name, bool=false)
		if (bool == nil)
			bool = false;
		end

		variable_name = :"@#{name}"

		self.send(:define_method, :"#{name}") {
			if (instance_variable_defined?(variable_name))
				var = instance_variable_get(variable_name);
				# We have cases where we seem to be memcaching Strings (or nil) for Boolean attributes. This code allows us to recover from that 
				# and also gather information that might help figuring out why we are caching them like this in the first place.
				if (var.kind_of?(String))
					bool_var = Boolean.new(var == "y" || var == "true" || var == "t");
					$log.info "Found Boolean cached as a String (#{var}) in memcache for #{variable_name} - converting to Boolean", :warning
					$log.info caller, :warning
					instance_variable_set(variable_name, bool_var);
				elsif (var.kind_of?(Integer))
					bool_var = Boolean.new(var == 1)
					$log.info "Found Boolean cached as an Integer (#{var}) in memcache for #{variable_name} - converting to Boolean", :warning
					$log.info caller, :warning
					instance_variable_set(variable_name, bool_var);
				elsif (var.nil? && !self.columns[name].nullable)
					bool_var = Boolean.new(self.columns[name].default_value.symbol);
					$log.info "Found nil value in memcache for #{variable_name} - converting to default Boolean value for this column", :warning
					$log.info caller, :warning
					instance_variable_set(variable_name, bool_var);
				else
					bool_var = var;
				end
				
				return bool_var.symbol;
			else
				instance_variable_set(variable_name, Boolean.new(bool));
				return instance_variable_get(variable_name).symbol;
			end
		}

		self.send(:define_method, :"#{name}=") { |symbol|
			if (instance_variable_defined?(variable_name))
				instance_variable_get(variable_name).symbol = symbol;
			else
				instance_variable_set(variable_name, Boolean.new(symbol));
			end
		}
	end
end
