require "set"
#This class is designed to store MySQL enums.  Each instance will have it's own
#list of valid symbols defined at creation time.
class Enum
	attr(:symbol)
	def initialize(symbol, *symbols)
		@symbols = Set.new;
		symbols.each { |sym|
			if (sym.is_a?(Enumerable)) 
				@symbols.merge(sym);
			else
				@symbols.add(sym);
			end
		}
		if (@symbols.include?(symbol))
			@symbol = symbol
		else
			raise "Invalid symbol for Enum";
		end
	end
	
	def to_s
		return @symbol.to_s;
	end
	
	def inspect
		return "<:#{@symbol.to_s} #{@symbols.inspect}>";
	end
	
	#assign a new symbol, raise an exception if it's not valid
	def symbol=(symbol)
		if (@symbols.include?(symbol))
			@symbol = symbol;
		else
			raise "Invalid symbol for Enum";
		end
	end

	#adds a symbol to the set of valid symbols	
	def add_symbol(symbol)
		@symbols.add(symbol);
	end
	
	#deletes a symbol from the set of valid symbols, raises an exception if the symbol is currently active
	def delete_symbol(symbol)
		if (@symbol != symbol)
			@symbols.delete(symbol);
		else
			raise "Tried to delete current symbol in Enum";
		end
	end
	
	#a == b tests that both a and b are descendants of Enum and have the same symbol
	def ==(obj)
		if (obj.is_a?(Enum))
			return obj.symbol == self.symbol;
		else
			return false;
		end
	end
	
	#a.eql?(b) tests that both a and b have the same symbol and the same set of valid symbols
	def eql?(obj)
		if (obj.instance_of?(Enum))
			return obj.symbol == self.symbol && obj.instance_variable_get(:@symbols) == @symbols;
		else
			return false;
		end
	end
	
	#a === b tests that both a and b have a symbol method and that it returns equal values
	def ===(obj)
		if (obj.method_defined?(:symbol))
			return obj.symbol == self.symbol;
		else
			return false;
		end
	end
	
	#takes a DBI row from DESCRIBE <table> <column> and returns the set of valid enum symbols
	#use with caution as it assumes good input
	def self.parse_column(result)
		enum_string = result[1];
		enum_string.gsub!(/^enum\('|'\)$/, '');
		values = enum_string.split(/','/);
		values.each_index {|i|
			values[i] = :"#{values[i]}";
		}
		return values;
	end
end