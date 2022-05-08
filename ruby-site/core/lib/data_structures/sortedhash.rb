class SortedHash
	include(Enumerable);

	ListElem = Struct.new(:key, :obj, :prev, :next)

	def initialize(*input)
		@hash = Hash.new
		
		@head = @tail = ListElem.new
		@head.next = @head
		@head.prev = @head

		if(input.size == 1 && input[0].kind_of?(Hash))
			input[0].each{|k,v|
				self[k] = v
			}
		elsif(input.kind_of?(Array)) #assume [[k,v],[k2,v2],...]
			input = input[0] if(input.size == 1)

			input.each {|k,v|
				self[k] = v;
			}
		end
	end

	attr_accessor :hash, :head, :tail;
	protected :hash=, :head=, :tail=;

	#return the first (N) keys
	def first(num = nil)
		return @head.next.obj if !num;
		
		ret = []

		i = 0
		obj = @head.next
		
		while @tail != obj && i < num
			ret << obj.key
			obj = obj.next
		end

		return ret
	end

	#return the last (N) keys
	def last(num = nil)
		return @tail.prev.obj if !num;
		
		ret = []

		i = 0
		obj = @tail.prev
		
		while @head != obj && i < num
			ret << obj.key
			obj = obj.prev
		end

		return ret
	end

	def each
		i = @head.next
		
		while @tail != i
			yield i.key, i.obj
			i = i.next
		end
	end

	def reverse_each
		i = @tail.end
		
		while @head != i
			yield i.key, i.obj
			i = i.prev
		end
	end

	def each_key(&block)
		i = @head.next
		
		while @tail != i
			yield i.key
			i = i.next
		end
	end

	def each_value(&block)
		i = @head.next
		
		while @tail != i
			yield i.key
			i = i.next
		end
	end
	
	def each_pair(&block)
		i = @head.next
		
		while @tail != i
			yield [i.key, i.obj]
			i = i.next
		end
	end

	def [](key)
		return @hash[key].obj if @hash[key]
		return nil
	end

	def has_key?(key)
		return @hash.has_key?(key)
	end
	alias include? has_key?
	alias key? has_key?
	alias member? has_key?

	def has_value?(value)
		self.each_value{|v|
			return true if v == value
		}
		return false;
	end
	alias value? has_value?

	def fetch(key, default = nil, &block)
		return self[key] if self.has_key?(key);
		return default if default		
		return block.call if block
		return nil	
	end

	def index(value)
		self.each{|k,v|
			return k if v == value
		}

		return nil;
	end

	def []=(key, val)
		if(@hash.has_key?(key))
			@hash[key].obj = val
		else
			obj = ListElem.new(key, val, @tail.prev, @tail)
			@hash[key] = obj
			@tail.prev.next = obj
			@tail.prev = obj
		end
		return val
	end
	alias store []=

	def delete(*keys)
		keys.each{|key|
			obj = @hash[key]
			obj.next.prev = obj.prev
			obj.prev.next = obj.next
			@hash.delete(key)
		}
	end

	def shift
		obj = @head.next
		return nil if !obj

		self.delete(obj.key)
		return [obj.key, obj.value]
	end

	def pop
		obj = @tail.prev
		return nil if !obj

		self.delete(obj.key)
		return [obj.key, obj.value]
	end

	def unshift(key, val, replace = false)
		if(@hash.has_key?(key))
			if(replace)
				self.delete(key)
			else
				return nil
			end
		end
		obj = ListElem.new(key, val, @head.next, @head)
		@hash[key] = obj
		@head.next.prev = obj
		@head.next = obj
		return val
	end
	alias prepend unshift

	def push(key, val, replace = false)
		if(@hash.has_key?(key))
			if(replace)
				self.delete(key)
			else
				return nil
			end
		end
		obj = ListElem.new(key, val, @tail.prev, @tail)
		@hash[key] = obj
		@tail.prev.next = obj
		@tail.prev = obj
		return val
	end
	alias append push

	def move_front(key)
		return false unless @hash.has_key?(key)

		obj = @hash[key]

		#remove from the current point in the linked list
		obj.next.prev = obj.prev
		obj.prev.next = obj.next

		#add back to the head of the linked list
		obj.prev = @head.next
		obj.next = @head

		@head.next.prev = obj
		@head.next = obj

		return true
	end
	alias move_begin move_front

	def move_back(key)
		return false unless @hash.has_key?(key)

		obj = @hash[key]

		#remove from the current point in the linked list
		obj.next.prev = obj.prev
		obj.prev.next = obj.next

		#add back to the tail of the linked list
		obj.prev = @tail.prev
		obj.next = @tail

		@tail.prev.next = obj
		@tail.prev = obj

		return true
	end
	alias move_end move_back

	def clear()
		@hash = Hash.new

		@head = @tail = ListElem.new
		@head.next = @head
		@head.prev = @head
	end

	def map(&block)
		ret = []
		self.each{|k,v|
			ret << block.call(k,v)
		}
		return ret
	end
	alias collect map
	
	def map_sortedhash(&block)
		ret = SortedHash.new
		self.each{|k,v|
			rk, rv = block.call(k,v)
			ret[rk] = rv
		}
		return ret
	end

	def map_hash(&block)
		ret = Hash.new
		self.each{|k,v|
			rk, rv = block.call(k,v)
			ret[rk] = rv
		}
		return ret
	end

	def merge(other)
		ret = self.dup
		ret.merge!(other)

		return ret
	end

	def merge!(other)
		other.each_pair { |key, value|
			self[key] = value;
		}
		return self
	end

	alias concat merge
	alias concat! merge!
	alias update merge!

	def dup()
		return self.map_sortedhash{|k,v| [k, v] }
	end
	
	def invert
		return self.map_sortedhash{|k,v| [v, k] }
	end

	def reverse
		ret = SortedHash.new
		self.reverse_each{|k,v|
			ret[k] = v
		}
		return ret
	end

	def to_hash
		return self.map_hash{|k,v| [k,v] }
	end

	def to_a
		return self.map{|k,v| [k,v] }
	end
	alias to_ary to_a

	def values_at(*ks)
		return ks.map{|key| self[key] }
	end

	def select(&block)
		ret = SortedHash.new

		self.each{|k,v|
			ret[k] = v if block.call(k, v)
		}

		return ret;
	end

	def reject(&block)
		ret = SortedHash.new

		self.each{|k,v|
			ret[k] = v if !block.call(k, v)
		}

		return ret;
	end

	def sort(&block)
		arr = hash.sort(&block)
		ret = SortedHash.new
		arr.each_pair{|k,v|
			ret[k] = v
		}
		return ret
	end

	def sort!(&block)
		arr = hash.sort(&block)
		self.clear
		arr.each_pair{|k,v|
			self[k] = v
		}
		return self
	end

	def empty?()
		return @head == @tail;
	end

	def rehash
		@hash.rehash
		self
	end

	def inspect
		return '<SortedHash: {' + self.map{|k,v| k.inspect + '=>' + v.inspect}.join(', ') + '}>'
	end

	def keys
#		return @hash.keys #can't do this because we probably want them in sorted order
		ret = []
		self.each_key{|key|
			ret << key
		}
		return ret;
	end

	def values
		ret = []
		self.each_value{|val|
			ret << val
		}
		return ret;
	end

	def length
		return @hash.length
	end
	alias size length
	
	def to_json
		arr = []
		self.each{|k, v|
			arr << "'" + k.to_s.gsub('\'', '\\\'') + "': " + v.to_json;
		}
		return '{' + arr.join(',') + '}';
	end
end

