lib_require :Core, "validateinput";

# TypeSafeHash is a read-only hash that requires you to specify an expected type
# when retrieving elements from it. When fetching an item from the hash, you must
# specify the type as the second argument. It uses the type specified's
# validate_input member to determine if the value is valid and then uses it.
class TypeSafeHash
	attr_reader :real_hash
	attr_reader :original_hash
	
	include Enumerable;

	# Initializes the type safe hash based on an existing normal hash.
	def initialize(cgi_hash)
		@original_hash = cgi_hash
		@real_hash = convert_to_nested_hash(cgi_hash)
	end
	
	# Compares a type safe hash with another.
	def ==(other)
		if (other.respond_to?(:real_hash))
			return real_hash == other.real_hash;
		else
			return false;
		end
	end

	# Retrieves an item from the hash based on the key. If it's not there,
	# or doesn't validate with type.validate_input(value), returns default.
	def fetch(key, type, default = nil)
		if (real_hash.has_key?(key))
			value = real_hash[key];

			# if type is not an array, but value is, flatten it.
			if (type != Array && value.kind_of?(Array))
				value = value[0];
			end

			real = type.validate_input(value);
			if (real != nil)
				return real;
			end
		end
		return default;
	end
	alias :[] :fetch;

	# Enumerates the keys in the hash.
	def each_key()
		real_hash.each_key() { |key| yield key; }
	end
	alias :each :each_key;

	# Enumerates the key, value pairs in the has.
	def each_pair(type, default = nil)
		real_hash.each_key() { |key|
			value = fetch(key, type, default)
			if (!value.nil?)
				yield(key, value);
			end
		}
	end

	# Enumerates keys that match a regex, passing the match object and the
	# value.
	def each_match(regex, type, default = nil)
		real_hash.each_key() { |key|
			if (matchinfo = regex.match(key))
				value = fetch(key, type, default);
				if (!value.nil?)
					yield(matchinfo, value);
				end
			end
		}
	end

	# Returns true if the hash is empty.
	def empty?()
		return real_hash.empty?();
	end

	# Returns true if key is in the hash.
	def has_key?(key)
		return real_hash.has_key?(key);
	end
	alias :include :has_key?;
	alias :key? :has_key?;
	alias :member? :has_key?;

	# Returns a string representation of the inner hash.
	def inspect()
		return real_hash.inspect();
	end

	# Returns an array of the keys in the hash.
	def keys(sorted=false)
		return sorted ? real_hash.keys().sort() : real_hash.keys();
	end

	# Returns an array of the values in the hash.
	def values()
		return real_hash.values();
	end

	# Returns the number of elements in the hash.
	def length()
		return real_hash.length();
	end
	alias :size :length;

	# Turns the inner hash into an array.
	def to_array()
		return real_hash.to_array();
	end

	# Returns the inner hash.
	def to_hash()
		result = real_hash
		
		result.each_value do |value|
			value = value.to_hash if value.is_a? TypeSafeHash
		end
		
		return result
	end
	
	def self.validate_input(value)
		if (value.kind_of?(Hash))
			return TypeSafeHash.new(value);
		else
			return nil
		end
	end

	# Returns a string representation of the inner hash.
	def to_s()
		return real_hash.to_s;
	end
	
	private
	def convert_to_nested_hash(cgi_hash)
		new_hash = {};
		cgi_hash.each_pair {|key, value|
			if ((value.kind_of? Array) && key !~ /\[(.*)\]$/)
				value = value.first
			end
			while (key =~ /(.*)\[(\w+)?\]$/)
				key = $1;
				if ($2)
					value = {$2 => value}
				else
					new_hash[key] ||= {}
					max_key = new_hash.keys.reject{|a| !a.kind_of? Integer}.max || -1 #muahahahahaha!
					[*value].each{|v|
						max_key += 1
						value = {max_key => v}
					}
				end
			end
			if (new_hash[key].kind_of?(Hash) && value.kind_of?(Hash))
				new_hash[key] = recursive_merge(value, new_hash[key]);
			else
				new_hash[key] = value;
			end
		}
		return new_hash
	end
	
	#This changes hash2 and its subhashes in place, it's private for a reason, 
	#not meant to be used outside of convert_to_nested_hash.
	def recursive_merge(hash1, hash2)
		hash1.each_pair { |key, value|
			if (!hash2[key])
				hash2[key] = value;
			elsif (hash2[key].kind_of?(Hash) && value.kind_of?(Hash))
				hash2[key] = recursive_merge(value, hash2[key]);
			end
		}
		return hash2;
	end	
end
