lib_require :Core, "storable/storable"

class UserName < Cacheable
	init_storable(:masterdb, 'usernames');
	set_prefix("ruby_username");

	def to_s
		return self.username
	end
	
	def UserName.by_name(username)	
		user = find(:first, :conditions => ["username = ? AND live IS NOT NULL", username]);

		return user;
	end

	#returns an array of id, name pairs
	def self.fetch_names_directly(*ids)
		names = []
		if (!ids.empty?)
			result = self.db.query("SELECT * FROM #{self.table} WHERE userid IN ?", ids)

			result.each {|row|
				names << [row['userid'], row['username']]
			}
		end
		
		return names
	end
	
	def before_update()
		$site.memcache.delete("ruby_username-#{@userid}");
	end
	
end

class SplitUserName < Storable
	init_storable(:usersdb, 'usernames');
end
