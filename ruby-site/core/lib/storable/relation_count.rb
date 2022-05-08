lib_require :Core, 'storable/relation'

class RelationCount < Relation
	MAXIMUM_PAGES_IN_CACHE = 200
	
	#executed to load a single item from the database
	def execute
		result_set = $site.memcache.get(cache_key)
		result = result_set[@options.to_s] unless result_set.nil?
		if (result.nil?)
			result = @prototype.target.find(:count, @options, *(@prototype.find_options+query_ids)).to_i #do a database query to find the number of results
			result_set = {} if (result_set.nil? || result_set.length >= MAXIMUM_PAGES_IN_CACHE)
			result_set[@options.to_s] = result
			$site.memcache.set(cache_key, result_set) #cache the count so we don't need to hit the db in the future
		end
		return result.to_i
	end
	
	#don't promise here or we can end up loading way too many objects
	def register_storable_promise
	end
end