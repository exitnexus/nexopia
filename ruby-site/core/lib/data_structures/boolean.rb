lib_require :Core, "data_structures/enum"
class Boolean < Enum

	@@bool_symbols = [false, true]

	def initialize(bool=false)
		@symbols = @@bool_symbols;
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
