lib_require :Core, 'storable/storable', 'storable/cacheable'

class BannedWords < Cacheable
	init_storable(:db, "bannedwords");
	
	def BannedWords.all_banned_words
		all_banned_words = $site.memcache.get("ruby-all_banned_words");
		if (all_banned_words.nil?)
			all_banned_words = BannedWords.find(:scan);
			
			# This will only last a day, so there shouldn't be a big need to be smart about invalidating it.
			# If we actually start editing banned words here, we'll want to do after_* methods to invalidate
			# the cache at appropriate moments.
			$site.memcache.set("ruby-all_banned_words", all_banned_words, 60*60*24);
		end
		
		return all_banned_words;
	end
end