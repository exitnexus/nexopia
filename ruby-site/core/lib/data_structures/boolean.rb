lib_require :Core, "data_structures/enum"
class Boolean < Enum
	def initialize(bool=false)
		@symbols = [false, true].to_set;
		if (bool)
			@symbol = true;
		else
			@symbol = false;
		end
	end
end

class TrueClass
	def to_i
		return 1;
	end
end

class FalseClass
	def to_i
		return 0;
	end
end
