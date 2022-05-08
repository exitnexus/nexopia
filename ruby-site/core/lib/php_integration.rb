lib_require :Core, "storable/storable"
lib_require :Core, "memcache"

class MemcacheKeyMapping < Storable
	init_storable(:db, "keymap")
end

class MemCache

	entries = MemcacheKeyMapping.find(:all, :scan);
	@@keymap = {};
	entries.each{|entry|
		ruby_regex = /^#{entry.rubykey}\-(.*)$/
		@@keymap[ruby_regex] ||= [];
		@@keymap[ruby_regex] << entry.phpkey;
	}

	def delete_mappings(keys)
		del_keys = []
		keys.each{|key|
			@@keymap.each{|ruby_regex, mappings|
				if(match = key.match(ruby_regex))
					mappings.each{|phpkey|
						del_keys << "#{phpkey}-#{match[1]}";
					}
				end
			}
		}

		if(del_keys.length > 0)
			$log.info "php-integration: Deleting ruby keys caused deleting php keys.", :debug
			raw_delete_many(*del_keys);
		end
	end

	### Delete the entry with the specified key, optionally at the specified +time+.
	alias :raw_delete :delete 
	def delete( key, time=nil )
		delete_mappings([key])

		raw_delete(key, time);
	end

	### Delete the entrys with the specified keys
	alias :raw_delete_many :delete_many
	def delete_many( *keys )
		delete_mappings(keys)

		raw_delete_many(*keys);
	end
	
	# Don't need to do this on set/add/replace because we always invalidate (ie delete) first.
end
