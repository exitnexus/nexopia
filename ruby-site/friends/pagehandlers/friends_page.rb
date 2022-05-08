lib_require :Friends, "friend", 'ignored_friend_of_friend'
lib_require :Core, 'users/user_name'

class FriendsPage < PageHandler
	MAX_FRIENDS_OF_FRIENDS = 100
	MAX_FRIENDS_OF_LENGTH = 1000 #if the reverse friends list is longer than this it sorts only by uid, not online/name
	PAGE_LENGTH = 20
	
	declare_handlers("friends") {
		area :User
		access_level :Any
		page :GetRequest, :Full, :friends
		page :GetRequest, :Full, :friends, 'page', input(Integer) #optional page
				
		access_level :IsUser, CoreModule, :editprofile
		page :GetRequest, :Full, :friends_reverse, 'reverse'
		page :GetRequest, :Full, :friends_reverse, 'reverse', 'page', input(Integer) #optional page
		page :GetRequest, :Full, :friends_of_friends, 'friends'
		handle :PostRequest, :hide_friend_of_friend, 'friends', 'hide', input(Integer)
		handle :PostRequest, :update_comment, 'comments', 'update', input(Integer)
		handle :PostRequest, :remove_friend, 'remove', input(Integer)
		handle :PostRequest, :reverse_remove_friend, 'reverse', 'remove', input(Integer)
		handle :PostRequest, :add_friend, 'add', input(Integer)
		handle :PostRequest, :show_all_ignored_friends_of_friends, 'friends', 'show', 'all'
	}
	
	def friends(page=1)
		page -= 1 #adjust start at 1 to start at 0
		user_skin = Profile::ProfileSkin.find(request.user.userid, request.user.friendskin, :first)
		request.reply.headers['X-user-skin'] = user_skin.header if (user_skin)
		t = Template::instance('friends', 'friends')
		t.friends_page = "selected"
		t.user = request.user
		t.friends = request.user.friends.sort_by {|friend| 
			online = friend.user.online ? 0 : 1
			[online, friend.user.username.upcase]
		}
		if (page*PAGE_LENGTH >= t.friends.length)
			site_redirect(url/request.user.username/:friends)
		end
		t.pages = generate_paging_string(page, t.friends.length, "/users/#{request.user.username}/friends")
		t.friends = t.friends.slice(page*PAGE_LENGTH, PAGE_LENGTH)
		
		t.logged_in = (request.session.user.userid == request.user.userid) || request.session.has_priv?(CoreModule, :profileedit)
		request.reply.headers['X-width'] = 0
		puts t.display
	end
	
	def friends_reverse(page=1)
		page -= 1 #adjust start at 1 to start at 0
		user_skin = Profile::ProfileSkin.find(request.user.userid, request.user.friendskin, :first)
		request.reply.headers['X-user-skin'] = user_skin.header if (user_skin)
		t = Template::instance('friends', 'reverse_friends')
		t.reverse_page = "selected"
		t.user = request.user
		t.reverse = true
		friends_of_ids = request.user.friends_of_ids
		if (page*PAGE_LENGTH >= friends_of_ids.length)
			site_redirect(url/request.user.username/:friends/:reverse)
		end
		
		if (friends_of_ids.length < MAX_FRIENDS_OF_LENGTH)
			friends_of = request.user.friends_of
			friends_of = friends_of.sort_by {|friend| 
				online = friend.owner.online ? 0 : 1
				[online, friend.owner.username.upcase]
			}
			t.friends = friends_of.slice(page*PAGE_LENGTH, PAGE_LENGTH)
		else
			friends_of_ids.sort!
			friends_of_ids = friends_of_ids.slice(page*PAGE_LENGTH, PAGE_LENGTH)
			t.friends = Friend.find(:promise, *friends_of_ids)
		end
		
		t.form_key = SecureForm.encrypt(request.session.user)
		t.pages = generate_paging_string(page, friends_of_ids.length, "/users/#{request.user.username}/friends/reverse")
		t.logged_in = true
		request.reply.headers['X-width'] = 0
		puts t.display
	end
	
	def friends_of_friends
		user_skin = Profile::ProfileSkin.find(request.user.userid, request.user.friendskin, :first)
		request.reply.headers['X-user-skin'] = user_skin.header if (user_skin)
		t = Template::instance('friends', 'friends_of_friends')
		t.friends_of_friends_page = "selected"
		t.user = request.user
		t.logged_in = true
		ignored_friends_of_friends = request.user.ignored_friends_of_friends.to_hash
		t.hidden_count = ignored_friends_of_friends.length
		friends_of_friends = request.user.friends_of_friends
		friends_of_friends.delete_if {|friend_of_friend, friend_id_pair|
			ignored_friends_of_friends[[request.user.userid, friend_of_friend]]
		}
		sorted_friends_of_friends = friends_of_friends.sort_by {|friend_of_friend, friends|
			-friends.length
		}
		sorted_friends_of_friends = sorted_friends_of_friends[0,MAX_FRIENDS_OF_FRIENDS]
		
		friends_of_friends = sorted_friends_of_friends.map{|friend_of_friend, friends|
			[User.find(:promise, :first, friend_of_friend), User.find(:promise, *friends)]
		}
		t.friends_of_friends = friends_of_friends.sort_by {|friend_of_friend, friends|
			[-friends.length, friend_of_friend.username.upcase]
		}
		t.form_key = SecureForm.encrypt(request.session.user)
		request.reply.headers['X-width'] = 0
		puts t.display
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
	
	def add_friend(id)
		request.user.add_friend(id)
		site_redirect(url/request.user.username/:friends)
	end
	
	def remove_friend(id)
		request.user.remove_friend(id)
		site_redirect(url/request.user.username/:friends)
	end
	
	def reverse_remove_friend(id)
		user = User.find(id, :first)
		if (user)
			user.remove_friend(request.user.userid)
		end
		site_redirect(url/request.user.username/:friends/:reverse)
	end
	
	def update_comment(friendid)
		comment = FriendComment.find(request.session.userid, friendid, :first)
	 	unless (comment)
			comment = FriendComment.new
			comment.userid = request.session.userid
			comment.friendid = friendid
		end
		original_comment = comment.comment
		
		comment.comment = params['comment', String]
		
		comment.comment.strip!
		
		request.reply.headers['Content-Type'] = PageRequest::MimeType::PlainText
		
		if (comment.comment.empty?)
			comment.delete
			puts "<div class=\"comment\" id=\"comment_#{friendid}\"><span class=\"minor\">(Click to edit)</span></div>"
		else
			comment.store
			puts "<div class=\"comment\" id=\"comment_#{comment.friendid}\">#{comment.comment}</div>"
		end
		friend_username = UserName.find(:first, friendid)
		$log.info(["friend_comments", "Changed #{request.user.username}'s comment for #{friend_username} from '#{original_comment}' to '#{comment.comment}'"], :info, :admin);	
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
