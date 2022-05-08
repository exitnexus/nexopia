lib_require :Core, 'storable/storable'

# Properties:
#
# userid
# content
class MockProfile < Storable
	init_storable(:videodb, 'mockprofile');

	relation_singular :user, :userid, User;
	
	def username
		if (user.nil?)
			return "";
		end
		
		return user.username;
	end
end