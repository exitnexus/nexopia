lib_require :Core, "storable/storable"
lib_require :Core, "memcache"

class MemcacheKeyMapping < Storable
	init_storable(:db, "keymap")
end

class MemCache

	entries = MemcacheKeyMapping.find(:all, :scan);
	@@keymap = {};
	entries.each{|entry|
		@@keymap[entry.rubykey] ||= [];
		@@keymap[entry.rubykey] << entry.phpkey;
	}
	### Delete the entry with the specified key, optionally at the specified
	### +time+.
	alias raw_delete delete 
	def delete( key, time=nil )
		$log.info "php-integration: deleting ruby key #{key}", :debug
		@@keymap.each{|rubykey, mappings|
			if key =~ /^#{rubykey}\-(.*)$/
				mappings.each{|phpkey|
					$log.info "php-integration: deleting key #{phpkey}-#{$1}", :debug
					raw_delete("#{phpkey}-#{$1}");
				}
			end
		}
		raw_delete(key, time);
	end
	
	### Store the specified +value+ to the cache associated with the specified
	### +key+ and expiration time +exptime+.
	alias raw_store store
	def store( type, key, val, exptime )
		$log.info "php-integration: Storing key '#{key}'.", :debug
		@@keymap.each{|rubykey, mappings|
			if key =~ /#{rubykey}/
				mappings.each{|phpkey|
					raw_delete(phpkey);
				}
			end
		}
		raw_store(type, key, val, exptime);
	end

end
