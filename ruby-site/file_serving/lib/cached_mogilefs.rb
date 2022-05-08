lib_require :FileServing, "mogilefs", "fast_mogilefs"

module MogileFS
	# Derives from the outside-provided mogilefs library to provide caching of mogile backend
	# paths.
	class CachingMogileFS < MogileFS
		MEMCACHE_KEY_NAME = "ruby_mog_path"

		# Invalidates the memcache entry for the file specified
		def invalidate(key)
			$site.memcache.delete("#{MEMCACHE_KEY_NAME}-#{urlencode(key)}")
		end

		def store_file(key, klass, file)
			$log.info "storing #{file} at #{key} with class #{klass}", :debug, :fileserving
			super(key, klass, file)
			invalidate(key)
		end
		
		def get_paths(key)
			$log.info "looking up path for file '#{key}'", :debug, :fileserving
			paths = $site.memcache.load(MEMCACHE_KEY_NAME, urlencode(key), 86400){|hash|
				hash.each_pair{ |fkey, null|
					deckey = urldecode(*fkey)
					mpaths = super(deckey)
					if (mpaths)
						$log.info "Path not cached.  Caching #{mpaths.join(',')} at #{fkey}", :debug, :fileserving
						hash[fkey] = mpaths
					else
						hash.delete(fkey);
					end
				}
				hash
			}
			if (!paths or paths.empty?)
				return nil	
			end
			paths
		end
		def delete(key)
			$log.info "deleting key '#{key}'", :debug, :fileserving
			super(key)
			invalidate(key)
		end
	end
end

