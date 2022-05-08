class RelationIds < Relation
	#works like multi but only returns the id sets not actual objects
	def execute
		result_ids = $site.memcache.get(cache_key)
		if (result_ids.nil?)
			result = @prototype.target.find(:promise, *(@prototype.find_options+query_ids)) #do a simple find against the database for the results
			result_ids = result.map {|storable| storable.get_primary_key}
			$site.memcache.set(cache_key, result_ids)
		end
		return result_ids
	end
end