lib_require :Friends, "friend", 'ignored_friend_of_friend'
lib_require :Core, 'users/user_name'
lib_require :Profile, "user_skin";

module Friends
	class FriendsPage < PageHandler
		MAX_FRIENDS_OF_FRIENDS = 100
		MAX_FRIENDS_OF_LENGTH = 1000 #if the reverse friends list is longer than this it sorts only by uid, not online/name
		PAGE_LENGTH = 50
	
		declare_handlers("friends") {
			area :Self
			site_redirect(:GetRequest) { ['/friends', [:User, PageRequest.current.session.user]] };
			
			area :User
			access_level :Any
			page :GetRequest, :Full, :friends
			page :GetRequest, :Full, :friends, 'page', input(Integer) #optional page
			
			access_level :Plus
			page :GetRequest, :Full, :common_friends, 'common'
			page :GetRequest, :Full, :common_friends, 'common', 'page', input(Integer) #optional page
				
			access_level :IsUser, CoreModule, :editfriends
			page :GetRequest, :Full, :friends_reverse, 'reverse'
			page :GetRequest, :Full, :friends_reverse, 'reverse', 'page', input(Integer) #optional page
			page :GetRequest, :Full, :friends_of_friends, 'discover'
			handle :PostRequest, :hide_friend_of_friend, 'discover', 'hide', input(Integer)
			handle :PostRequest, :update_comment, 'comments', 'update', input(Integer)
			handle :PostRequest, :remove_friend, 'remove', input(Integer)
			handle :PostRequest, :reverse_remove_friend, 'reverse', 'remove', input(Integer)
			handle :PostRequest, :add_friend, 'add', input(Integer)
			handle :PostRequest, :re_add_friend, 'readd', input(Integer)
			handle :PostRequest, :show_all_ignored_friends_of_friends, 'friends', 'show', 'all'
		}

		def friends(page=1)
		
			# adjust start at 1 to start at 0
			page -= 1 
			
			num_pages = (request.user.friends_count.to_f()/PAGE_LENGTH).ceil();
			
			if(page < 0)
				site_redirect(url / request.user.username / :friends);
			elsif(page >= num_pages && num_pages > 0)
				site_redirect(url / request.user.username / :friends / :page / (num_pages-1));
			end
			
			user_skin = Profile::UserSkin.find(request.user.userid, request.user.friendsskin, :first);
			request.reply.headers['X-user-skin'] = user_skin.header if (user_skin && request.user.plus?())
			request.reply.headers['X-width'] = 0
		
			t = Template::instance('friends', 'friends')
			t.reverse = false
			t.user = request.user # User who's page we're looking at.
			t.form_key = self.form_key

			friend_user_id_list = request.user.friends_ids.map{|friend| [friend[1]]};
			activetime_list = User.get_all_activetime(friend_user_id_list);
			curr_time = Time.now.to_i();

			ids = request.user.friends_sorted.map {|friend| friend.id};
			username_objs = UserName.fetch_names_directly(*ids)
		
			# Put the friends who are online at the top of the list, then sort alphabetically		
			username_objs = username_objs.sort_by {|username|
				online = (activetime_list["useractive-#{username[0]}"].to_i() > curr_time - $site.config.session_active_timeout) ? 0 : 1; 
				[online, username[1].upcase.gsub(/[^A-Z0-9]/, '')]
			}

			sorted_friends = username_objs.map{|username| User::TinyUser.new(username[0].to_i, username[1]) }
		
			username_slice = sorted_friends.slice(page*PAGE_LENGTH, PAGE_LENGTH);

			friends_slice = username_slice.map{|tiny_user| 
				temp = Friend.new();
				temp.userid = request.user.userid;
				temp.friendid = tiny_user.id;
				temp;
			};
		
			# prime the users and friend comments
			friends_slice.each{|friend| friend.original_user}
			friends_slice.each{|friend| friend.comment}

		
			#Get the activetime for all of the friends' user objects at once.
			friends_user_list = friends_slice.map{|friend| friend.user };
		
			User.prime_user_activetime(friends_user_list);
		
			#prime the username objects and the user profile objects
			friends_user_list.each{|user| user.username_obj}
			friends_user_list.each{|user| user.profile}
			temp_list = friends_slice.map{|friend| [friend.friendid, request.user.userid]};
			t.friends_with = request.user.friends_with(temp_list);

			# redirect to the first page if the requested page doesn't exist and you have friends
			if ( (request.user.friends.length > 0) && (page*PAGE_LENGTH >= request.user.friends.length) )
				site_redirect(url/request.user.username/:friends)
			end

			# generate the page numbers at the top and bottom of the page.
			t.pages = generate_paging_string(page, request.user.friends.length, url/:users/request.user.username/:friends)
			t.friends = friends_slice;

			t.edit_mode = (request.session.user.userid == request.user.userid) || request.session.has_priv?(CoreModule, :editfriends)

			layout = Template::instance('friends', 'friends_layout')
			layout.user = request.user
			layout.inner = t
			layout.friends = "selected" # set the navigation tab
			layout.edit_mode = t.edit_mode

			puts layout.display
		end

		def common_friends(page=1)
			# adjust start at 1 to start at 0
			page -= 1 
		
			user_skin = Profile::UserSkin.find(request.user.userid, request.user.friendsskin, :first);
			request.reply.headers['X-user-skin'] = user_skin.header if (user_skin && request.user.plus?())
			request.reply.headers['X-width'] = 0
		
			common_friends = request.user.friends_in_common(request.session.user)
			if(common_friends.nil?())
				common_friends = [];
			end
		
			# redirect to the first page if the requested page doesn't exist and you have friends
			if ( (common_friends.length > 0) && (page*PAGE_LENGTH >= common_friends.length) )
				site_redirect(url/request.user.username/:friends/:common)
			end
		
			common_friend_user_id_list = common_friends.map{|friend| [friend.friendid]};
			activetime_list = User.get_all_activetime(common_friend_user_id_list);
			username_objs = UserName.fetch_names_directly(*common_friend_user_id_list);
			curr_time = Time.now.to_i();
		
			username_objs = username_objs.sort_by {|username|
				online = (activetime_list["useractive-#{username[0]}"].to_i() > curr_time - $site.config.session_active_timeout) ? 0 : 1; 
				[online, username[1].upcase.gsub(/[^A-Z0-9]/, '')]
			}
			username_slice = username_objs.slice(page*PAGE_LENGTH, PAGE_LENGTH);
		
			common_friends_slice = username_slice.map{|user|
				temp = Friend.new();
				temp.userid = request.user.userid;
				temp.friendid = user[0].to_i();
				temp;
			}
		
			common_friends_slice.each{|friend| friend.original_user}
			common_friends_slice.each{|friend| friend.comment}
		
			common_friends_user_list = common_friends_slice.map{|friend| friend.user };	
		
			User.prime_user_activetime(common_friends_user_list);
		
			common_friends_user_list.each{|user| user.username_obj};
			common_friends_user_list.each{|user| user.profile};
		
			t = Template::instance('friends', 'common_friends')
			t.reverse = true
			t.user = request.user # User who's page we're looking at.
			t.form_key = self.form_key
			t.friends = common_friends_slice;
			t.friends_with = {};
			# generate the page numbers at the top and bottom of the page.
			t.pages = generate_paging_string(page, common_friends.length, url/:users/request.user.username/:friends/:common)

			layout = Template::instance('friends', 'common_friends_layout');
			layout.user = request.user;
			layout.inner = t;
			layout.friends = "selected"; # set the navigation tab

			puts layout.display;
		end
	
		#
		# Displays the list of people who have friended the current user.
		#
		def friends_reverse(page=1)
		
			# adjust start at 1 to start at 0
			page -= 1
		
			# Skin the page with the user skin if they're plus users.
			user_skin = Profile::UserSkin.find(request.user.userid, request.user.friendsskin, :first);
			request.reply.headers['X-user-skin'] = user_skin.header if (user_skin && request.user.plus?())
			request.reply.headers['X-width'] = 0
		
			# redirect to the first page if the requested page doesn't exist and you have friends
			friends_of_ids = request.user.friends_of_ids
			if ( (friends_of_ids.length > 0) && (page*PAGE_LENGTH >= friends_of_ids.length) )
				site_redirect(url/request.user.username/:friends/:reverse)
			end

			# if the reverse friends list is longer than MAX_FRIENDS_OF_LENGTH it sorts only by uid, not online/name
			if (friends_of_ids.length < MAX_FRIENDS_OF_LENGTH)
				# get the list of user ids for the friend of the current user. 
				user_friends_of_id_list = request.user.friends_of_ids.map{|friend| [friend[0]]};
				activetime_list = User.get_all_activetime(user_friends_of_id_list);
				curr_time = Time.now.to_i();
				username_objs = UserName.fetch_names_directly(*user_friends_of_id_list)
			
				username_objs = username_objs.sort_by {|username|
					online = (activetime_list["useractive-#{username[0]}"].to_i() > curr_time - $site.config.session_active_timeout) ? 0 : 1; 
					[online, username[1].upcase.gsub(/[^A-Z0-9]/, '')]
				}
			
				user_slice = username_objs.slice(page*PAGE_LENGTH, PAGE_LENGTH);
			
				friends_slice = user_slice.map{|user|
					temp = Friend.new();
					temp.userid = user[0].to_i();
					temp.friendid = request.user.userid;
					temp;
				}
			else
				friends_of_ids.sort!
				friends_of_ids = friends_of_ids.slice(page*PAGE_LENGTH, PAGE_LENGTH)
				friends_slice = Friend.find(:promise, *friends_of_ids)
			end

			# prime the users and usernames
			friends_slice.each{|friend| friend.original_owner}
			friends_slice.each{|friend| friend.comment}

			friends_user_list = friends_slice.map{|friend| friend.owner };

			User.prime_user_activetime(friends_user_list);
		
			friends_with = {};
			friends_user_list.each{|user|
				if(request.user.is_friend?(user.userid))
					friends_with[user.id] = true;
				end
			}
			
			friends_user_list.each{|user| user.username_obj}
			friends_user_list.each{|user| user.profile}
	
			t = Template::instance('friends', 'reverse_friends')
			t.reverse = true
			t.user = request.user # User who's page we're looking at.
			t.form_key = self.form_key
			t.friends = friends_slice
			t.friends_with = friends_with;
			# generate the page numbers at the top and bottom of the page.
			t.pages = generate_paging_string(page, friends_of_ids.length, url/:users/request.user.username/:friends/:reverse)		

			# wrap the page with the Friends page header.
			layout = Template::instance('friends', 'friends_layout')
			layout.user = request.user
			layout.inner = t
			layout.reverse = "selected"
			layout.edit_mode = (request.session.user.userid == request.user.userid) || request.session.has_priv?(CoreModule, :editfriends)
		
			puts layout.display	
		end # def reverse_friends()

		#
		# Removes the user with 'friend_id' from the current user's friends list.
		# Return an overlay to put up telling the user how to re-add the friend.
		#
		def remove_friend(friend_id)

			request.user.remove_friend(friend_id)
			friend_user = User.get_by_id(friend_id);
			
			if (params['ajax', Boolean] && !friend_user.nil?() && friend_user.kind_of?(User))
				t = Template.instance('friends', 'removed_friend_overlay')
				t.person = User.find(friend_id, :first)
				t.friends_with = request.user.friends_with()
				t.reverse = false
				t.form_key = self.form_key
				t.message = "Click the plus to re-add this friend."
		
				puts t.display()
			else
				site_redirect(url/request.user.username/:friends)
			end
		
		end
	
		#
		# Re-adds a friend and returns the friend block that should be displayed.
		# 
		#
		def re_add_friend(friend_id)

			t = Template.instance('friends', 'friend')
			new_friend = request.user.add_friend(friend_id)
			if(!new_friend.nil?())
				t.person = new_friend.user;
			else
				t.person = User.get_by_id(friend_id);
			end
		
			t.friends_with = request.user.friends_with()
			t.comment = ""
			t.form_key = self.form_key
			t.edit_mode = (request.session.user.userid == request.user.userid) || request.session.has_priv?(CoreModule, :editfriends)
		
			puts t.display()
		
		end
	
		#
		# Adds the specified friend_id to the current session user's friends list.
		# 
		def add_friend(friend_id)
			error = InfoMessages.display_errors {
				request.session.user.add_friend(friend_id)
			}
			if (error)
				request.reply.headers['Status'] = 403
			end

			if (!params["ajax", Boolean])
				site_redirect(url/request.user.username/:friends)
			end
		end
	
		#
		# Removes the current session user from the given id's Friends list.
		# Returns a block to display indicating the user has been removed from the person's Friends list.
		# 
		def reverse_remove_friend(id)
		
			user = User.find(id, :first)
			if (user)
				user.reverse_remove_friend(request.user.userid)
			end

			if (params['ajax', Boolean])		
				t = Template.instance('friends', 'removed_friend_overlay')
				t.person = user
				t.reverse = true
				t.form_key = self.form_key
				t.friends_with = request.user.friends_with()
				t.message = "You've been removed from this person's Friends list."
				
				puts t.display()
			else
				site_redirect(url/request.user.username/:friends)
			end

		end
	
		def update_comment(friendid)
			friend_user = User.get_by_id(friendid);
			if(friend_user.nil?())
				$log.info("Attempt made to update comment for nonexistent user(#{friendid}) by #{request.session.user.username}(#{request.session.user.userid})", :warning);
				return;
			end
		
			if(request.user.userid != request.session.user.userid && request.session.has_priv?(CoreModule, :editfriends))
				original_comment = FriendComment.find(request.session.userid, friendid, :first);
				friend_username = friend_user.username;
			end
	 	
			comment = FriendComment.new();
			comment.userid = request.user.userid;
			comment.friendid = friendid;
		
			comment.comment = params['comment', String];
		
			comment.comment.strip!();
		
			request.reply.headers['Content-Type'] = PageRequest::MimeType::PlainText;
			
			comment.store(:duplicate);
	
			request.reply.headers['Content-Type'] = PageRequest::MimeType::PlainText;
			t = Template::instance('friends', 'notes');
			t.comment = comment.comment;
			t.friendid = friendid;
			t.edit_mode = true;
			puts t.display();
		
			if(original_comment)
				$log.info(["friend_comments", "Changed #{request.user.username}'s comment for #{friend_username} from '#{original_comment.comment}' to '#{comment.comment}'"], :info, :admin);
			end
		end
	
		def friends_of_friends
			user_skin = Profile::UserSkin.find(request.user.userid, request.user.friendsskin, :first);
			request.reply.headers['X-user-skin'] = user_skin.header if user_skin
			request.reply.headers['X-width'] = 0

	# 		user_skin = Profile::UserSkin.find(request.user.userid, request.user.friendsskin, :first);
	# 		request.reply.headers['X-user-skin'] = user_skin.header if (user_skin && request.user.plus?())
	# 		t = Template::instance('friends', 'friends_of_friends')
	# 		t.friends_of_friends_page = "selected"
	# 		t.user = request.user
	# 		t.logged_in = true
	# 		ignored_friends_of_friends = request.user.ignored_friends_of_friends.to_hash
	# 		t.hidden_count = ignored_friends_of_friends.length
	# 		friends_of_friends = request.user.friends_of_friends
	# 		friends_of_friends.delete_if {|friend_of_friend, friend_id_pair|
	# 			ignored_friends_of_friends[[request.user.userid, friend_of_friend]]
	# 		}
	# 		sorted_friends_of_friends = friends_of_friends.sort_by {|friend_of_friend, friends|
	# 			-friends.length
	# 		}
	# 		sorted_friends_of_friends = sorted_friends_of_friends[0,MAX_FRIENDS_OF_FRIENDS]
	# 		
	# 		friends_of_friends = sorted_friends_of_friends.map{|friend_of_friend, friends|
	# 			[User.find(:promise, :first, friend_of_friend), User.find(:promise, *friends)]
	# 		}
	# 		t.friends_of_friends = friends_of_friends.sort_by {|friend_of_friend, friends|
	# 			[-friends.length, friend_of_friend.username.upcase.gsub(/[^A-Z0-9]/, '')]
	# 		}
	# 		t.form_key = SecureForm.encrypt(request.session.user)
	# 		request.reply.headers['X-width'] = 0
	# 		puts t.display

			layout = Template::instance('friends', 'friends_layout')
			layout.user = request.user

			layout.inner = Template::instance('friends', 'friends_of_friends')

			layout.discover = "selected"
			puts layout.display
		end

		def show_all_ignored_friends_of_friends
			request.user.ignored_friends_of_friends.each {|ignored_friend|
				ignored_friend.delete
			}
			site_redirect(url/request.user.username/:friends/:friends)
		end

		def hide_friend_of_friend(id)
			to_hide = IgnoredFriendOfFriend.new
			to_hide.userid = request.user.userid
			to_hide.ignoreid = id
			to_hide.store
			site_redirect(url/request.user.username/:friends/:friends)
		end

		private
		def generate_paging_string(page, total_length, base_url)
			current_page = page + 1 #user displayable page number
			total_pages = (total_length.to_f/PAGE_LENGTH).ceil
			pages = []
			add_page!(pages, total_pages, 1)
			add_page!(pages, total_pages, 2)
			middle_pages = [current_page]
			before = current_page-1
			after = current_page+1
			(1..4).each {|i|
				if (before && before < 1)
					before = false
				end
				if (after && after > total_pages)
					after = false
				end
				if ((i%2 == 0 || !after) && before)
					middle_pages << before
					before = before - 1
				elsif ((i%2 == 1 || !before) && after)
					middle_pages << after
					after = after + 1
				end
			}
			middle_pages.sort!
			if (middle_pages.first > 3)
				add_page!(pages, total_pages, '...')
			end
			middle_pages.each {|mp|
				add_page!(pages, total_pages, mp)
			}
			if (pages.last && pages.last < total_pages-2)
				add_page!(pages, total_pages, '...')
			end
			add_page!(pages, total_pages, total_pages-1)
			add_page!(pages, total_pages, total_pages)
			pages = pages.map {|page| 
				if (page.kind_of?(String))
					page
				elsif (page == current_page)
					"<a class='current_page' href='#{base_url}/page/#{page}'>#{page}</a>"
				else
					"<a href='#{base_url}/page/#{page}'>#{page}</a>"
				end
			}
			return_string = ""
			if (current_page > 1)
				return_string += "<a href='#{base_url}/page/#{current_page-1}'>&lt; Previous</a>&#160;"
			end
			return_string += pages.join("&#160;")
			if (current_page < total_pages)
				return_string += "&#160;<a href='#{base_url}/page/#{current_page+1}'>Next &gt;</a>"
			end
			return "<div class='pages'>#{return_string}</div>"
		end
	
		#add page to the array pages, ensure that it's not already there and that it's a valid page
		def add_page!(pages, total_pages, page)
			if (page.kind_of?(String) || (page <= total_pages && page >= 1 && !pages.include?(page)))
				pages << page
			end
		end
	end
end