lib_require :Core, 'storable/relation'

class RelationMultiNoncached < Relation
	#a version of multi that doesn't utilize memcache, intended for use
	#with things where data consistency is critical or the overhead of
	#putting it in and out of memcache would be too high
	def execute
		result = @prototype.target.find(:promise, *(@prototype.find_options+query_ids)) #query with all columns, options
		result.each {|storable|
			storable.register_relation_to(self)
		}
		return result
	end
	
	class << self
		def memcache?
			return false
		end
	end
end