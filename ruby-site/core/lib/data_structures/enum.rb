require "set"
#This class is designed to store MySQL enums.  Each instance will have it's own
#list of valid symbols defined at creation time.
class Enum
	attr(:symbol)
	attr_reader :symbols

	EMPTY_STR = ''

	def initialize(symbol, symbols) #symbols can be an array, hash of (symbol => true) or a set.
		@symbols = symbols
		if(symbol.nil? || symbol == EMPTY_STR)
			symbol = @symbols.to_a.first
		elsif(@symbols.include?(symbol))
			@symbol = symbol
		else
			raise "Invalid symbol for Enum '#{symbol}', valid symbols are: #{@symbols.inspect}";
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
			raise "Invalid symbol for Enum '#{symbol}', valid symbols are: #{@symbols.inspect}";
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
		begin
			return obj.symbol == self.symbol;
		rescue
			return false;
		end
	end

	#takes the field entry from SHOW FIELDS FROM <table> and returns the valid enum symbols
	#use with caution as it assumes good input
	def self.parse_type(enum_string)
		enum_string.gsub!(/^enum\('|'\)$/, '');
		values = enum_string.split(/','/);
		return values;
	end
end
