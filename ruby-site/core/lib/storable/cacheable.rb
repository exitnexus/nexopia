lib_require :Core, 'storable/storable', 'memcache'

#Cacheable extends storable by adding caching abilities.  Caching works only
#when retrieving results using +find(:by_id, ...)+.  A basic implementation will looking
#similar to this:
#  class SomeTable < Cacheable
#      init_cacheable(<memcache>, <database handle>, <table name>);
#
#      def created()
#          ...do something when an object is inserted into the database...
#      end
#
#      def updated()
#          ...do something when an object changes in the database...
#      end
#
#      def deleted()
#         ...do something when an object is deleted from the database...
#      end
#
#      ...the rest of your class here...
#  end
class Cacheable < Storable
	#valid entries here are :insert, :update, :replace
	attr_reader(:cache_key);

	class_attr_reader(:cache, :default_cache_time)
	class_attr_writer(:cache, :default_cache_time)
	self.cache = $site.memcache
	self.default_cache_time = 604800 #one week

	#Save the object to the cache and the database.
	def store(*args)
		super(*args);
		self.class.cache.delete(cache_key);
	end

	#Save the object to cache only.
	def cache(cache_time = self.class.default_cache_time)
		self.class.cache.set( cache_key, self, cache_time);
	end

	#generate the cache key for this object
	def cache_key()
		keys = primary_key.map { |key|
			self.send(key);
		}
		key = self.class.prefix + '-' + keys.join('/');
		return key;
	end

	def delete(*args)
		super(*args)
		self.class.cache.delete(cache_key);
	end

	class << self
		def init_cacheable(cache = nil, db = nil, table = nil)
			set_cache(cache) if (cache);
			init_storable(db, table);
		end

		#Default prefix is the name of the class.
		def prefix()
			return self.name;
		end

		protected ############BEGIN PROTECTED METHODS#############

		#Sets a class specific cache for a subclass, if this isn't called the parents cache will be used.
		def set_cache(new_cache)
			class_attr(:cache, true);
			self.cache = new_cache;
		end

		#Sets a class specific default cache time for a subclass, if this isn't called the parents cache time will be used.
		def set_default_cache_time(new_cache_time)
			class_attr(:default_cache_time, true);
			self.default_cache_time = new_cache_time;
		end

		#Specify a non-standard prefix for this class.
		def set_prefix(new_prefix)
			class_attr(:prefix, true);
			self.prefix = new_prefix;
		end

		def find_in_cache(id_sets, options={})
			found = super(id_sets)
			found.meta.keys ||= []
			id_sets = id_sets - found.meta.keys
			if (!id_sets.empty? && !options[:nomemcache])
				keys = [];
				id_sets.each{|id|
					if (id.memcacheable?)
						keys << id.id
						found.meta.keys << id
					end
				}
				hash_result = cache.load(prefix, keys, self.default_cache_time) { |hash|
					array_keys = Array.new
					hash.each_pair{|key, value|
						array_keys << key
					}
					results = self.find(:nomemcache, *array_keys)
					results.each {|result|
						hash[[*result.get_primary_key]] = result
					}
					hash
				}
				storable_result = StorableResult.new;
				hash_result.each_pair {|key, value|
					storable_result << value
					value.update_method = :update
					value.after_load
					cache_result(value.storable_id, value);
				}
				found.concat(storable_result)
			end
			return found;
		end
	end
end
