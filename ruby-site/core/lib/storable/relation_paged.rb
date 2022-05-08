lib_require :Core, 'storable/relation'

class RelationPaged < Relation
	AUTO_PRIME_DEFAULT = false
	
	MAXIMUM_PAGES_IN_CACHE = 50
	
	def initialize(*args)
		super(*args)
		if (@options.nil? || @options.empty?)
			$log.info("Attempted to load paged relation #{@prototype}<#{@options}> without any runtime arguments.", :warning)
			$log.object(caller, :info)
		end
	end
	
	#We override this method because we want to promise the ids stored in memcache if they are available
	def register_storable_promise
		@cached_entry = $site.memcache.get(cache_key)

		@cached_ids = @cached_entry[@options.to_s] unless @cached_entry.nil?
		if (@cached_ids)
			@ids = @prototype.target.group_ids(@cached_ids, :PRIMARY, @prototype.extracted_options[:selection])
		end
		super #handles actually setting up the promises from @ids
	end

	#loads multiple items first looking for a list of ids from memcache
	def execute
		result = nil
		if (@cached_entry.nil?)
			result = @prototype.target.find(:promise, @options, *(@prototype.find_options+query_ids)) #do a simple find against the database for the results
			result_ids = result.map {|storable| storable.get_primary_key}
		 	@cached_entry = { @options.to_s => result_ids }
			$site.memcache.set(cache_key, @cached_entry)
		elsif (@cached_ids.nil?)
			result = @prototype.target.find(:promise, @options, *(@prototype.find_options+query_ids)) #do a simple find against the database for the results
			result_ids = result.map {|storable| storable.get_primary_key}
			@cached_entry = {} if @cached_entry.length >= MAXIMUM_PAGES_IN_CACHE
			@cached_entry[@options.to_s] = result_ids
			$site.memcache.set(cache_key, @cached_entry)
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
		@cached_entry = nil
		@cached_ids = nil
		super
	end
end
