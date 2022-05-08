lib_require :Core, 'storable/storable'
module Profile
	class ProfileView < Storable
		init_storable(:usersdb, "profileviews")
	
		class << self
			def view(user, profile_user)
				return false if (user.nil? || profile_user.nil?)
				if (user.anonymousviews == 'y' || user.anonymousviews == 'f' && profile_user.friends.include?(user))
					anonymous = 1;
				else
					anonymous = 0;
				end
				#db.query("INSERT INTO #{table} SET userid = #, viewuserid = ?, time = ?, hits = 1, anonymous = ? ON DUPLICATE KEY UPDATE hits = hits + 1, time = ?", user.userid, profile_user.userid, Time.now.to_i, anonymous, Time.now.to_i);
				return true;
			end
		end
	end
end
