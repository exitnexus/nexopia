class OrderedMap
	include(Enumerable);

	def initialize(*key_value_list)
		@array = Array.new
		@hash = Hash.new;
		if (key_value_list.last.kind_of?(Hash))
			key_value_list.pop.each_pair {|key, value|
				key_value_list << key;
				key_value_list << value;
			}
		end
		key_value_list.each_index {|i|
			if (i%2 == 0) #2 #this comment is here only to appease the eclipse syntax highlighter
				self[key_value_list[i]] = key_value_list[i+1];
			end
		}
	end

	attr_accessor :array, :hash;
	protected :array=, :hash=;

	def dup()
		new = OrderedMap.new();
		new.array = array.dup;
		new.hash = hash.dup;
		return new;
	end

	def first()
		return self.at(0);
	end

	def each(&block)
		@array.each { |key|
			if (@hash[key] != nil)
				yield @hash[key];
			end
		}
	end

	def each_pair(&block)
		@array.each { |key|
			if (@hash[key] != nil)
				yield key, @hash[key];
			end
		}
	end

	def each_with_index(&block)
		@array.each_with_index { |key, i|
			if (@hash[key] != nil)
				yield @hash[key], i;
			end
		}
	end

	def [](key)
		key = [*key]
		return @hash[key];
	end

	def []=(key, val)
		key = [*key]
		@array << key if (!@array.include?(key))
		@hash[key] = val;
	end

	def at(index)
		return @hash[@array[index]];
	end

	def index(key)
		key = [*key]
		return @array.index(key);
	end

	def clear()
		@array.clear();
		@hash.clear();
	end

	def collect(&block)
		return @array.map { |key|
			yield @hash[key];
		}
	end
	alias map collect;
	def select_ordered(&block)
		cp = OrderedMap.new;
		self.each_pair {|key, val|
			if (yield(key, val))
				cp.add(val);
			end
		}
		return cp;
	end

	def merge(map)
		map.each_pair { |key, value|
			self[key] = value;
		}
		return self
	end

	alias concat merge

	def delete_key(*keys)
		keys.each {|key|
			key = [*key]
			@array.delete(key);
			@hash.delete(key);
		}
	end

	def delete(*storables)
		delete_key(*storables.collect {|storable| storable.get_primary_key; });
	end
	alias remove delete

	def add(*storables)
		storables.each {|storable|
			self[storable.get_primary_key] = storable;
		}
	end
	alias << add;

	def empty?()
		return @hash.empty?;
	end

	def inspect
		return @array.inspect + ":" + @hash.inspect;
	end

	def keys
		return @array;
	end

	def slice!(start_or_index_or_range, length=nil)
		first = 0;
		last = self.length;
		
		unless (length)
			unless (start_or_index_or_range.kind_of?(Range))
				return self[start_or_index_or_range];
			else
				first = start_or_index_or_range.first;
				last = start_or_index_or_range.last;
			end
		else
			first = start_or_index_or_range;
			last = start_or_index_or_range + length;
		end
		
		to_be_deleted = [];
		@array.each_with_index {|key, i|
			if (i < first || i >= last)
				to_be_deleted << key;
			end
		}
		delete_key(to_be_deleted);
		return self;
	end
	
	def slice(start_or_index_or_range, length=nil)
		first = 0;
		last = self.length;
		
		unless (length)
			unless (start_or_index_or_range.kind_of?(Range))
				return self[start_or_index_or_range];
			else
				first = start_or_index_or_range.first;
				last = start_or_index_or_range.last;
			end
		else
			first = start_or_index_or_range;
			last = start_or_index_or_range + length;
		end
		
		to_be_returned = OrderedMap.new()
		@array.each_with_index {|key, i|
			unless (i < first || i >= last)
				to_be_returned.add(self[key]);
			end
		}
		return to_be_returned;
	end

	

	def length
		return @array.length
	end
end
