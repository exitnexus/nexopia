
# An EnumMap acts as a MySql enum in Ruby, but is backed by a number in MySql.

class EnumMap < Enum
	attr_accessor(:hash);
	
	def initialize(value, hash)
		@hash = hash;
		super(get_symbol(value), hash.keys);
	end
	
	#takes either a backend value or a symbol and returns a symbol
	def get_symbol(value)
		#if this is already the symbol return it now no mapping necessary
		return value if (@hash.key?(value));
		@hash.each_key { |key|
			return key if (@hash[key] == value)
		}
		raise "Invalid symbol value  lookup for EnumMap '#{value.inspect}', valid symbols are: #{@hash.keys.inspect}";
	end
	
	def hash=(value)
		@hash = value;
		@symbols = value.keys;
	end
	
	def symbol=(value)
		super(get_symbol(value));
	end
	
	def value()
		return @hash[self.symbol];
	end
	
	def [](symbol)
		return @hash[:symbol];
	end
	
	def to_s()
		return value.to_s;
	end
end
