lib_require :Friends, "friend", 'ignored_friend_of_friend'
lib_require :Core, 'users/user_name', 'secure_form'
lib_want	:Profile, "profile_block_query_info_module"

module Friends
	class FriendsProfileBlock < PageHandler
		PAGE_LENGTH = 10
	
		declare_handlers("profile_blocks/Friends/list") {
			area :User
			access_level :Any
			page :GetRequest, :Full, :friends, input(Integer);
		
			handle :GetRequest, :ajax_pages, :ajax;
		}
	
		def friends(block_id)
			edit_mode = params["profile_edit_mode", Boolean, false];
		
			if(!Profile::ProfileDisplayBlock.verify_visibility(block_id, request.user, request.session.user, edit_mode))
				print "<h1>Not visible</h1>";
				return;
			end
		
			t = Template::instance('friends', 'friends_profile_block')
		
			t.friend_count = request.user.friends_ids.length
			if(request.session.user.plus?())
				t.common_friends_count = request.user.friends_in_common_count(request.session.user)
			end
			t.page_count = (request.user.friends_ids.length.to_f/PAGE_LENGTH).ceil
			t.add_form_key = SecureForm.encrypt(request.session.user, "/User/friends/add")
			t.user = request.user
			t.viewer = request.session.user
			t.json_viewer = {:anonymous => t.viewer.anonymous?, :username => t.viewer.username}
			friends = request.user.friends_sorted(request.session.user);
			t.friends = friends.slice(0,20)
			t.list_view_data = friends.map { |tinyuser|
				[tinyuser.id, tinyuser.json_safe_username, request.session.user.friend?(tinyuser.id)]
			}
			t.names = friends.map {|friend| friend.json_safe_username}
		
			puts t.display
		
		end
	
		def ajax_pages
			request.reply.headers['Content-Type'] = PageRequest::MimeType::PlainText

			page = params["page", Integer, nil]
			page_list = params["page_list", String, nil]
			if (page_list)
				page_list = page_list.split(',')
				page_list = page_list.map {|p| p.to_i}
			else
				page_list = []
			end
			page_list << page if page
		
			#grab the friend ids, pre-sorted by username
			friendids = request.user.friends_sorted(request.session.user).map {|friend| friend.id}

			#grab the ids we need for the given page.
			ids = []
			page_list.each{|page|
				ids.concat(friendids.slice(PAGE_LENGTH*page, PAGE_LENGTH))
			}

			#grab the actual user objects and re-sort (find doesn't guarantee sort order)
			friends = (ids.empty? ? [] : User.find(:selection => :minimal, *ids) )
			User.prime_user_activetime(friends);
			friends_hash = friends.to_hash
			friends = ids.map {|id|
				friends_hash[[id]] || AnonymousUser.new(nil, id)
			}
		
			add_form_key = SecureForm.encrypt(request.session.user, "/User/friends/add")
		
			page_list.each {|page|
				t = Template::instance("friends", "friends_profile_block_list_elements")
				t.friends = friends.slice!(0,PAGE_LENGTH)
				t.viewer = request.session.user
				t.add_form_key = add_form_key
				t.page_id = "friends_list_page_" + page.to_s
				puts t.display
			}
		end
	
		def self.friends_query(info)
			if(site_module_loaded?(:Profile))
				info.extend(ProfileBlockQueryInfo);
				info.title = "Friends";
				info.initial_position = 20;
				info.initial_column = 0;
				info.form_factor = :both;
				info.removable = false;
				info.editable = false;
				info.moveable = false;
				info.initial_block = true;
				info.page_url = ["Friends", url/:friends, 4];
			
				# changes on a per user basis of "friend/defriend user" buttons and the like.
				info.content_cache_timeout = 0 
			end
		
			return info;
		end
	end
end