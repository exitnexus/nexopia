lib_require :Core, "storable/storable"

class UserName < Cacheable
	init_storable(:masterdb, 'usernames');
	set_prefix("ruby_username");

	def to_s
		return self.username
	end
	
	def UserName.by_name(username)
		return find(:first, :conditions => ["username = ? AND live IS NOT NULL", username]);
	end
end

class SplitUserName < Storable
	init_storable(:usersdb, 'usernames');
end
