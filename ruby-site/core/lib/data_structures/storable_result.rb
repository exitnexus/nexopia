#StorableResult is returned by any find call to storable that doesn't return a single storable object
#its purpose is to add extra functionality to array that is useful for sets of storable objects
#most of the methods are simple wrappers to array methods that return a storableresult as opposed to an array
class StorableResult < Array
	attr :total_rows, true
	
	def total_rows
		if (@total_rows)
			return @total_rows
		else
			return self.length
		end
	end
	
	def compact
		return StorableResult.new(super)
	end

	def concat(*args)
		if (args.length == 1 && args.first.kind_of?(Array))
			super(*args)
		else
			super(args)
		end
	end
	
	def flatten
		return StorableResult.new(super)
	end
	
	def map(*args)
		return StorableResult.new(super(*args))
	end
	alias :collect :map
	
	#return a storable result of objects that matches the storableid (should be of type StorableID)
	def match(storableid)
		return self.select {|element| storableid === element }
	end
	
	def reverse
		return StorableResult.new(super)
	end
	
	def select(*args)
		return StorableResult.new(super(*args))
	end

	def slice(*args)
		result = super(*args)
		if (result)
			return StorableResult.new(result)
		else
			return result
		end
	end
	
	def [](*args)
		super_result = super(*args)
		if (super_result.kind_of? Array)
			return StorableResult.new(super_result)
		else
			return super_result
		end
	end
	
	def to_hash
		hash = {}
		self.each {|storable|
			hash[[*storable.get_primary_key]] = storable
		}
		hash
	end
	
	def sort(*args)
		return StorableResult.new(super(*args))
	end

	def sort_by(*args, &block)
		return StorableResult.new(super(*args, &block))
	end
	
	def uniq
		return StorableResult.new(super)
	end
	
	def |(*args)
		return StorableResult.new(super(*args))
	end
end
