lib_require :Core, 'storable/relation'

class RelationMulti < Relation
	AUTO_PRIME_DEFAULT = false
	
	#We override this method because we want to promise the ids stored in memcache if they are available
	def register_storable_promise
		@cached_ids = $site.memcache.get(cache_key)
		if (@cached_ids)
			@ids = @prototype.target.group_ids(@cached_ids, :PRIMARY, @prototype.extracted_options[:selection])
		end
		super #handles actually setting up the promises from @ids
	end

	#loads multiple items first looking for a list of ids from memcache
	def execute
		result = nil
		if (@cached_ids.nil?)
			result = @prototype.target.find(:promise, *(@prototype.find_options+query_ids)) #do a simple find against the database for the results
			result_ids = result.map {|storable| storable.get_primary_key}
			$site.memcache.set(cache_key, result_ids)
		elsif (@cached_ids.empty?)
			result = StorableResult.new()
		else
			#do a query for each of the results from memcache, force_proxy means that we can access the returned
			#objects without fetching them as long as we stick to the primary key
			result = @prototype.target.find(:force_proxy, :promise, *@cached_ids)
		end
		return result
	end
	
	#We need to do some cleanup so we don't look for the same list of ids when we reexecute
	def invalidate
		@cached_ids = nil
		super
	end
end