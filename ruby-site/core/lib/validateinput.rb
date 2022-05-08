# extend core classes to have the ability to validate and convert against
# a string through the validate_input function.

class IO
	# All strings validate as strings
	def self.validate_input(value)
		return value;
	end

	# instance form does a straight comparison of the string with self.
	def validate_input(value)
		return self;
	end
end

class String
	# All strings validate as strings
	def String.validate_input(value)
		return value.to_s;
	end

	# instance form does a straight comparison of the string with self.
	def validate_input(value)
		if (self == value)
			return value;
		else
			return nil;
		end
	end
end

class Boolean
	# Must be a string that is either "true" or "false"
	def Boolean.validate_input(value)
		if (value.kind_of?(Boolean) || /(true|false|on|off|y|n|enabled|disabled)/ =~ value.to_s)
			return !!(/(true|on|y|enabled)/ =~ value.to_s);
		else
			return nil;
		end
	end


	def validate_input(value)
		if (self == !!(/(true|on|y|enabled)/ =~ value.to_s))
			return !!(/(true|on|y|enabled)/ =~ value.to_s);
		else
			return nil;
		end
	end
end

class Date
	# Check for a string of the regex /[0-9]{1,2}\/[0-9]{1,2}\/[0-9]{4}/
	# and make a Date object out of it
	def Date.validate_input(value)
		if (value.kind_of?(Date))
			return value
		end
		match = value.to_s.match(/([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{4})/)
		begin
			if (match)
				return Date.civil(match[3].to_i, match[2].to_i, match[1].to_i)
			end
		rescue ArgumentError
			# just let it return nil.
		end
		return nil
	end
end

class Integer
	# All characters must be numbers, except for the first which may be -
	def Integer.validate_input(value)
		if (value.kind_of?(Integer) || /^-?[0-9]+$/ =~ value)
			return value.to_i;
		else
			return nil;
		end
	end

	# instance form does a straight comparison of value.to_i with self.
	def validate_input(value)
		if (self == value.to_i)
			return value.to_i;
		else
			return nil;
		end
	end
end

class PageList
	def PageList.validate_input(value)
		return Integer.validate_input(value);
	end
end

class Float
	# All characters must be numbers, except there can be up to one decimal
	# and a negative sign at the front
	def Float.validate_input(value)
		if (value.kind_of?(Float) || /^-?[0-9]*(\.[0-9]+)?$/ =~ value)
			return value.to_f;
		else
			return nil;
		end
	end

	# instance form does a straight comparison of value.to_f with self.
	def validate_input(value)
		if (self == value.to_f)
			return value.to_f;
		else
			return nil;
		end
	end
end

class Regexp
	# Returns the string if value matches self's regex, returns nil otherwise.
	# Note that this is not a class method like the other overrides. This one
	# compares against the regexp INSTANCE you pass in.
	def validate_input(value)
		if (value.kind_of?(String) && matches = self.match(value))
			return matches;
		else
			return nil;
		end
	end
end

class Range
	def validate_input(value)
		if(self.first.class != self.last.class)
			$log.info "Range endpoint types don't match", :critical
			return nil;
		end

		value = self.first.class.validate_input(value);

		if(!value || !(self === value))
			return nil;
		end

		return value;
	end
end

class Array
	def Array.validate_input(value)
		if (!value.kind_of?(Array) && value.length > 0)
			return nil;
		else
			return value;
		end
	end
	
	# Checks each element of the value array to ensure that they match against
	# the first element of self
	def validate_input(value)
		# eliminate the obvious
		if (!value.kind_of?(Array) || self.length != 1)
			return nil;
		end
		innertype = self[0];
		# now check whether the inner elements of the array match
		output = value.collect {|innervalue|
			# if innertype is not an array, but value is, we need to flatten it
			if (!innertype.kind_of?(Array) && innervalue.kind_of?(Array))
				innervalue = innervalue[0];
			end

			innertype.validate_input(innervalue); # returns
		}.compact();

		if (output.length > 0)
			return output;
		else
			return nil;
		end
	end
end

class Set
	def Symbol.validate_input(value)
		begin
			return value.to_set;
		rescue
			return nil;
		end
	end

	def validate_input(value)
		if(self.include?(value))
			return value;
		end
		return nil;
	end

end

class Symbol
	# Must be composed of numbers, letters, and _'s, can't start with a number.
	def Symbol.validate_input(value)
		begin
			return value.to_sym;
		rescue
			return nil;
		end
	end

	# instance form does a straight comparison of value.to_sym with self.
	def validate_input(value)
		begin
			if (self == value.to_sym)
				return value.to_sym;
			else
				return nil;
			end
		rescue
			return nil;
		end
	end
end

class Class
	def validate_input(value)
		if (value.kind_of?(String) && value == name)
			return value
		elsif (value.kind_of?(Class) && value == self)
			return value
		end
		return nil
	end
end

class Object
	def validate_input(value)
		if (value == self)
			return value
		end
		return nil
	end
end
