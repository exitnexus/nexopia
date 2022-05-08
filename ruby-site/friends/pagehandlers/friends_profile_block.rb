lib_require :Friends, "friend", 'ignored_friend_of_friend'
lib_require :Core, 'users/user_name', 'secure_form'
lib_want	:Profile, "profile_block_query_info_module";

class FriendsProfileBlock < PageHandler
	PAGE_LENGTH = 10
	
	declare_handlers("profile_blocks/Friends") {
		area :User
		access_level :Any
		page :GetRequest, :Full, :friends, 'list', input(Integer)
	}
	
	def friends(block_id)
		request.reply.headers['X-width'] = 178
		t = Template::instance('friends', 'friends_profile_block')
		t.friends = request.user.friends.sort_by {|friend| 
			-friend.user.activetime
		}
		t.add_form_key = SecureForm.encrypt(request.session.user, Time.now, "/User/friends/add")
		t.user = request.user
		t.viewer = request.session.user
		puts t.display
	end
	
	def self.friends_query(info)
		if(site_module_loaded?(:Profile))
			info.extend(ProfileBlockQueryInfo);
			info.title = "Friends";
			info.initial_position = 20;
			info.initial_column = 0;
			info.form_factor = :both;
		end
		
		return info;
	end
end
