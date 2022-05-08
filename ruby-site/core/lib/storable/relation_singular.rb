lib_require :Core, 'storable/relation'

class RelationSingular < Relation
	#executed to load a single item from the database
	def execute
		result = @prototype.target.find(:first,*(@prototype.find_options+query_ids)) #query with all columns, options, and :first since it is a singular relation
		return result
	end
	
	class << self
		#Singular relations depend on the Storable layer to handle their caching.
		def memcache?
			return false
		end
	end
end