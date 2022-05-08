Dir.new($site.config.site_base_dir + '/core/lib/storable').each { |filename|
	if (filename =~ /^(relation(_[\w_]+)?)/)
		lib_require :Core, "storable/#{$1}"
	end
}

class RelationManager
	class << self
		@@relation_prototypes = {} #used to store the prototypes for all relations
		
		#Defines a new prototype and registers it as a type of relation
		def create_prototype(name, type, origin, key_columns, target, *extra_args)
			rp = RelationPrototype.new(name, type, origin, key_columns, target, *extra_args)
			self.register_prototype(rp)
			return rp
		end
		
		#A prototype must be registered with the relation manager before it can be built
		#by storable.
		def register_prototype(prototype)
			@@relation_prototypes[[prototype.origin, prototype.name]] = prototype
		end
		
		#returns the prototype that matches origin and name if it exists, otherwise nil
		def get_prototype(origin, name)
			return @@relation_prototypes[[origin, name]]
		end
		
		#return a list of prototypes that are affected when an instance of table has changes in columns
		def find_prototypes(instance, columns=nil)
			return @@relation_prototypes.values.select {|prototype|
				prototype.match?(instance, columns)
			}
		end
		
		#The relation cache is used to store pointers to all existing relations.
		#It expires at the end of the page view.
		def relation_cache
			return $site.cache.get(:relation_manager_cache, :page) { Hash.new };
		end
		protected :relation_cache

		def test_reset(class_name)
			relation_cache.delete_if {|key, val| 
				key =~ /^#{class_name}/
			}
		end
		
		#Adds a relation to the relation manager internal cache (page duration)
		#If a relation already exists in the cache for the given cache key it is
		#returned instead and should be used to ensure object consistency.
		#Relation caching is critical to being able to invalidate relations when
		#objects enter/leave them.
		def cache_relation(relation)
			relation_cache[relation.cache_key] ||= relation
			return relation_cache[relation.cache_key]
		end
		
		#Creates an instance of a relation using a relation prototype
		def create_relation(origin, name, instance, options)
			prototype = self.get_prototype(origin, name)
			relation = prototype.create_relation(instance, options) unless prototype.nil?
			if (relation)
				return self.cache_relation(relation)
			else
				raise "Unable to locate relation #{origin}-#{name}"
			end
		end
		
		#invalidate all relations that are no longer valid for instance (takes instance's modification state into account)
		def invalidate_store(instance)
			modified_columns = instance.modified_columns
			prototypes = find_prototypes(instance, modified_columns)
			original_instance = instance.original_version
			#we want to invalidate the relations the object is leaving and the relations the object is entering
			prototypes.each {|prototype|
				invalidate_relation(prototype.cache_key(instance))
				invalidate_relation(prototype.cache_key(original_instance))
			}
		end
		
		#invalidate all relations that are no longer valid given that the instance is being deleted
		def invalidate_delete(instance)
			prototypes = find_prototypes(instance)
			original_instance = instance.original_version
			prototypes.each {|prototype|
				invalidate_relation(prototype.cache_key(original_instance))
			}
		end
		
		#delete both the internal and memcache versions the relations for cache_key
		def invalidate_relation(cache_key)
			relation_cache[cache_key].invalidate if relation_cache[cache_key]
			$site.memcache.delete(cache_key)
		end
	end
end