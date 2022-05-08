lib_require :Core, 'data_structures/sortedhash'

class LRUCache
	include Enumerable

	CacheObject = Struct.new(:content, :exptime, :hook)

	def initialize(max_num, &hook)
		@max_num
		@hook = hook

		@objs = SortedHash.new
		
		@hits = 0
		@misses = 0
		@evictions = 0
	end


	def cached?(key)
		return @objs.include?(key)
	end
	alias :include? :cached?
	alias :member? :cached?
	alias :key? :cached?
	alias :has_key? :cached?

	def cached_value?(val)
		self.each_value { |v|
			return true if v == val
		}
		return false
	end
	alias :has_value? :cached_value?
	alias :value? :cached_value?

	def index(val)
		self.each_pair{|k,v|
			return k if v == val
		}
		return nil
	end

	def length
		return @objs.length
	end
	alias :size :length

	def to_hash
		ret = {}
		self.each{|k,v|
			ret[k] = v
		}
		return ret
	end

	def keys
		return @objs.keys
	end

	def values
		return @objs.collect {|key, obj| obj.content}
	end

	def delete(key)
		obj = @objs[key]
		if(obj)
			if(obj.hook)
				obj.hook.call(key, obj.content)
			elsif(@hook)
				@hook.call(key, obj.content)
			end

			@objs.delete(key)
		end
		return obj.content
	end

	def clear()
		@objs.keys.each{|key|
			self.delete(key)
		}
	end

	def expire()
		now = Time.now.to_i
		delete_list = []
		@objs.each{|k,v|
			delete_list << k if(v.exptime && v.exptime <= now)
		}

		delete_list.each{|key|
			self.delete(key)
		}

#		GC.start
	end

	def get(key)
		if(!@objs.include?(key))
			@misses += 1
			return nil
		end
		
		obj = @objs[key]

		if(obj.exptime && obj.exptime <= Time.now.to_i)
			@objs.delete(key)
			return nil
		end

		@objs.move_end(key) # move the reference at the back of the list
		@hits += 1

		return obj.content
	end
	alias [] get

	def []=(*args)
		if(args.length == 2)
			return self.store(args[0], args[1], nil)
		elsif(args.length == 3)
			return self.store(args[0], args[2], args[1])
		else
			raise ArgumentError, "wrong number of arguments (#{args.length} for 2-3)"
		end
	end

	def store(key, value, exptime = nil, &block)
		self.delete(key) if(self.cached?(key))

		if(@max_num && @max_num == @objs.size)
			self.expire()
			if(@max_num == @objs.size) #if still full, delete one
				self.delete(@objs.first)
				@evictions += 1
			end
		end

		@objs.push(key, CacheObject.new(value, (exptime ? Time.now.to_i + exptime : exptime), block))

		return value
	end

	def each_pair
		@objs.each{ |key, obj|
			yield key, obj.content
		}
		return self
	end
	alias :each :each_pair

	def each_key
		@objs.each_key{ |key|
			yield key
		}
		return self
	end

	def each_value
		@objs.each_value{ |obj|
			yield obj.content
		}
		return self
	end

	def empty?
		return @objs.empty?
	end

	def fetch(key, default = nil, &block)
		return self[key] if self.has_key?(key);
		return default if default		
		return block.call if block
		return nil	
	end

	def statistics()
		[@hits, @misses, @evictions]
	end
end

