lib_require :Core, "storable/storable"
lib_require :Core, "users/user", "users/anonymous_user"
lib_require :Core, "json"
lib_require :Friends, 'friend_comment'
lib_require :Orwell, 'send_email'
lib_want :GoogleProfile, 'google_user'
lib_want :UserDump, "dumpable"
lib_want :Scoop, 'event'

module Friends
	class Friend < Storable
		 
		MAX_FRIENDS = 250
		MAX_PLUS_FRIENDS = 1000
		attr_reader :user, :owner
	
		init_storable(:usersdb, "friends");
	
		relation_singular :comment, [:userid, :friendid], FriendComment
		relation_singular :mutual, [:friendid, :userid], Friend
		relation :singular, :original_user, [:friendid], User, :selection => :minimal
		relation :singular, :original_owner, [:userid], User, :selection => :minimal
	
		# Return all the friends of a given user.
		def self.all(userid)
			return Friend.find(:promise, :all, userid)
		end

		def before_delete
			unless (self.comment.nil?)
				self.comment.delete
			end 
			$site.memcache.delete("sorted_friends-#{userid}")
			if (site_module_loaded?(:GoogleProfile))
				self.owner.update_hash
			end
			super
		end

		if (site_module_loaded?(Scoop))
			register_event_hook(:before_delete) {
				Scoop::Event.reevaluate_friendship_defer(self.userid, self.friendid)
			}
		end

		def before_create
			validate!
			$site.memcache.delete("sorted_friends-#{userid}")
			super
		end
		def before_update
			validate!
			$site.memcache.delete("sorted_friends-#{userid}")
			super
		end
	
		def after_create
			if (site_module_loaded?(:GoogleProfile))
				self.owner.update_hash
			end
			super
		end

		if (site_module_loaded?(Scoop))
			register_event_hook(:after_create) {
				Scoop::Event.populate_friendship_defer(self.userid, self.friendid)
				if (self.user.friend?(self.userid))
					#if the reverse friendship exists do this both ways to catch stories that required a friends permission
					Scoop::Event.populate_friendship_defer(self.friendid, self.userid)
				end
			}
		end
		
		def after_update
			if (site_module_loaded?(:GoogleProfile))
				self.owner.update_hash
			end
			super
		end
	
		#raises an exception on failure
		def validate!
			if (self.owner.plus?)
				max = MAX_PLUS_FRIENDS
			else
				max = MAX_FRIENDS
			end
			if (owner.friends_ids.length >= max)
				raise "Unable to add friend, maximum already reached (#{max})."
			end
			return true
		end

		def user()
			user_obj = self.original_user;
			if(user_obj.nil?())
				self.delete();
				user_obj = AnonymousUser.new(nil, friendid);
			end
		
			return user_obj;
		end
	
		def owner()
			user_obj = self.original_owner;
			if(user_obj.nil?())
				user_obj = AnonymousUser.new(nil, userid);
			end
		
			return user_obj;
		end
	
		def uri_info(*args)
			return self.user.uri_info(*args)
		end 

		def img_info(*args)
			return self.user.img_info(*args)
		end
	
		
		if (site_module_loaded?(:UserDump))
		  extend Dumpable
  		# Used by the UserDumpController to get the friends list for a given user.
  		# Start and end time don't matter since we can only get the current friends list.
  		def self.user_dump(userid, startTime = 0, endTime = Time.now())
  			out = "\"Friend User ID\",\"Friend Username\",\"Friend Age\",\"Friend Sex\"\n"
			
  			friends = self.all(userid)
			
  			friends.each { |friend|
  				out += "\"#{friend.user.userid}\",\"#{friend.user.username}\",\"#{friend.user.age}\",\"#{friend.user.sex}\"\n"
  			}
			
  			Dumpable.str_to_file("#{userid}-friends.csv", out)

  		end
		end

	end # Class Friend < Storable
end

class User < Cacheable
	relation :multi, :friends, :userid, Friends::Friend
	relation :ids, :friends_ids, :userid, Friends::Friend
	relation :ids, :friends_of_ids, [:userid], Friends::Friend, :friendid
	relation :multi, :friends_of, [:userid], Friends::Friend, :friendid
	relation :count, :friends_count, [:userid], Friends::Friend
	
	TinyUser = Struct.new(:id, :username)
	class TinyUser
		def json_safe_username()
			begin
				username.to_json
			rescue Iconv::IllegalSequence
				return id.to_s
			end
			return username
		end	
	end
	
	def friends_online()
		@friends_online ||= nil;
		if (!@friends_online)
			@friends_online = [];
			self.friends.map{|friend|
				if (friend.user.logged_in?)
					@friends_online << friend.user
				end
			}
		end
		return @friends_online;
	end

	#returns the list friends as structs with id and name, pre-sorted by username
	#is a user object is passed in as also_friend_of users that are also friends with
	#that user will be moved to the top of the list
	def friends_sorted(also_friend_of=nil)
		sorted_friends = $site.memcache.load("sorted_friends", self.userid, 86400*7) {|hash|
			ids = self.friends.map {|friend| friend.friendid}
			username_objs = UserName.fetch_names_directly(*ids)
			
			username_objs = username_objs.sort_by {|username| 
				username[1].upcase.gsub(/[^A-Z0-9]/, '')
			}

			hash[hash.keys.first] = username_objs.map{|username| TinyUser.new(username[0].to_i, username[1]) }

			hash #return
		}
		if (also_friend_of && also_friend_of.plus?)
			sorted_friends = sorted_friends.sort_by {|friend| 
				[also_friend_of.friend?(friend.id) ? 0 : 1, friend.username.upcase.gsub(/[^A-Z0-9]/, '')]
			}
		end
		
		return sorted_friends
	end
	
	#returns a hash containing the id's of the friends of your friends. Internal to each hash bin
	#is an array containing the id's of users that are mutually friends with you and the keyed user.
	def friends_of_friends
		friends_of_friends = {}
		friends.each {|friend|
			ids = friend.user.friends_ids
			ids.each {|id|
				friends_of_friends[id.last] ||= []
				friends_of_friends[id.last] << id.first
			}
		}
		friends.each {|friend|
			friends_of_friends.delete friend.friendid
		}
		return friends_of_friends
	end

	#
	# returns the number of friends in common this user has with the given user.	
	#
	def friends_in_common_count(user_or_id)
		if(user_or_id.kind_of?(User))
			self._friends_in_common_count(user_or_id);
		elsif(user_or_id.kind_of?(Integer))
			self._friends_in_common_count(User.find(:first, user_or_id));
		end
	end
	def _friends_in_common_count(user)
		
		user_friends_set = Set.new(user.friends_ids.flatten);
		my_friends_set = Set.new(self.friends_ids.flatten);

		# remove the current user and the user we're comparing to		
		user_friends_set.delete(user.userid);
		my_friends_set.delete(self.userid);

		return my_friends_set.intersection(user_friends_set).length;
		
	end

	#
	# returns the number of friends in common this user has with the given user.	
	#
	def friends_in_common(user_or_id)
		if(user_or_id.kind_of?(User))
			self._friends_in_common(user_or_id);
		elsif(user_or_id.kind_of?(Integer))
			self._friends_in_common(User.find(:first, user_or_id));
		end
	end
	def _friends_in_common(user)
		
		# get lists of the two users friends
		user_friends_set = Set.new(user.friends_ids.flatten);
		my_friends_set = Set.new(self.friends_ids.flatten);
		
		# remove the current user and the user we're comparing to
		user_friends_set.delete(user.userid);
		my_friends_set.delete(self.userid);

		# calculate the common friends
		common_friend_ids = my_friends_set.intersection(user_friends_set);
		
		common_friends = []
		# for each friend if it's in the common friend ids list add that friend object to the common friends list.
		user.friends.each { |friend|
			if ( common_friend_ids.member?(friend.friendid) )
				common_friends << friend 			
			end
		}
		
		return common_friends
		
	end

	
	#
	# Add a new friend association to the in memory friends
	# and create that record in the database
	#
	def add_friend(user_or_id)

		user_id = case user_or_id
			when Integer
				user_or_id
			else
				user_or_id.userid
			end
		return _add_friend_by_id(user_id)
	end
	
	def _add_friend_by_id(friend_id)
		return if (Friends::Friend.find(:first, self.userid, friend_id)) #already a friend
		friend = Friends::Friend.new
		friend.userid = self.userid
		friend.friendid = friend_id
		friends << friend
		friend.store(:ignore, :affected_rows)
		
		if (friend.affected_rows == 0)
			return false
		end

		sender = self		
		receiver = User.find(:first, friend_id)
		
		message = Message.new
		message.sender = sender
		message.receiver = receiver
		subject = "Friends List Addition"
		
		if (message.receiver.friendsauthorization && !message.receiver.ignored?(sender))

			key_expire_time = Time.now + (30 * Constants::DAY_IN_SECONDS)
			add_form_key = SecureForm.encrypt( receiver, "/User/friends/add/#{sender.userid}", key_expire_time )
			remove_form_key = SecureForm.encrypt( receiver, "/User/friends/reverse/remove/#{sender.userid}", key_expire_time )

			base_url = $site.www_url/:users
			login_url = ($site.www_url/"login.php").to_s + "?referer="
			sender_profile = base_url/sender.username
			receiver_url = base_url/urlencode(receiver.username)/:friends

			add_url = (receiver_url/:add/sender.userid).to_s + "?form_key[]=" + add_form_key			
			remove_url = (receiver_url/:reverse/:remove/sender.userid).to_s + "?form_key[]=" + remove_form_key

			t = Template.instance('friends', 'add_friend_message')
			t.sender = sender
			t.receiver = receiver
			t.sender_profile = sender_profile
			t.login_url = login_url
			t.add_url = add_url
			t.remove_url = remove_url
			
			message.subject = subject
			message.text = t.display()
			message.send()
			
			if (receiver.fwmsgs)
				msg = Orwell::SendEmail.new
				msg.subject = subject
				msg.send(receiver, 'add_friend_email_plain',
				 	:html_template => 'add_friend_email', 
					:template_module => 'friends',
					:sender => sender,
					:receiver => receiver,
					:sender_profile => sender_profile,
					:login_url => login_url,
					:add_url => add_url,
					:remove_url => remove_url					
				)
				
			end
			
		end
		return friend
	end

	#
	# Given a Friend id remove it from the list of friends on this object
	# and delete that association from the database
	#
	def remove_friend(user_or_id)
		friend_id = case user_or_id
		when Integer
			user_or_id
		else
			user_or_id.userid
		end
		friend = friends.find {|o|
			o.friendid == friend_id
		}
			
		sender = self
		receiver = User.find(:first, friend_id)
		
		message = Message.new
		message.sender = sender
		message.receiver = receiver
		subject = "Friends List Removal"

		unless friend.nil? || receiver.nil?
						
			if (message.receiver.friendsauthorization && !receiver.ignored?(self))
				
				key_expire_time = Time.now + (30 * Constants::DAY_IN_SECONDS)
				remove_form_key = SecureForm.encrypt( receiver, "/User/friends/remove/#{sender.userid}", key_expire_time )				
				
				base_url = $site.www_url/:users				
				login_url = ($site.www_url/"login.php").to_s + "?referer="
				sender_profile = base_url/sender.username
				receiver_url = base_url/urlencode(receiver.username)/:friends/:remove/sender.userid

				remove_url = receiver_url.to_s + "?form_key[]=" + remove_form_key

				t = Template.instance('friends', 'remove_friend_message')
				t.sender = sender
				t.receiver = receiver
				t.sender_profile = sender_profile
				t.login_url = login_url
				t.remove_url = remove_url
				
				message.subject = subject
				message.text = t.display()
				message.send()

				if (receiver.fwmsgs)
					msg = Orwell::SendEmail.new
					msg.subject = subject
					msg.send(receiver, 'remove_friend_email_plain',
					 	:html_template => 'remove_friend_email', 
						:template_module => 'friends',
						:sender => sender,
						:receiver => receiver,
						:sender_profile => sender_profile,
						:login_url => login_url,
						:remove_url => remove_url	
					)
				end

			end

			friends.delete friend
			friends_ids.delete [friend.userid, friend.friendid]
			friend.delete
		end
	end
	#
	# Given a Friend id remove it from the list of friends on this object
	# and delete that association from the database.  This differs from
	# remove_friend in that it does not send a message.
	#
	def reverse_remove_friend(user_or_id)
		friend_id = case user_or_id
		when Integer
			user_or_id
		else
			user_or_id.userid
		end
		friend = friends.find {|o|
			o.friendid == friend_id
		}
		#friend is always nil here in test cases, this is why things aren't getting deleted
		#there is a new ordered_map test that demonstrates that find works right, so it seems
		#the friends list must have an issue.
		unless friend.nil?
			friends.delete friend
			friends_ids.delete [friend.userid, friend.friendid]
			friend.delete
		end
	end
	
	def friend?(user_or_id)
		if(user_or_id.kind_of?(User))
			self._friend?(user_or_id.userid);
		elsif(user_or_id.kind_of?(Integer))
			self._friend?(user_or_id);
		end
	end
	
	alias :is_friend? :friend?
	
	def _friend?(friend_id)
		return friends_ids.include?( [userid, friend_id] );
	end
	
	def friends_with(user_id_list = nil)
		retval = {};
		if(user_id_list.nil?() || user_id_list.empty?)
			Friends::Friend.find(:distinct, self.userid, :index => :friendid).each{|friend|
				retval[friend.userid] = true;
			};
		else
			Friends::Friend.find(:distinct, *user_id_list).each{|friend|
				retval[friend.userid] = true;
			};
		end
		return retval;
	end
	
	def friend_of_friend?(user_or_id)
		if(user_or_id.kind_of?(User))
			self._friend_of_friend?(user_or_id);
		elsif(user_or_id.kind_of?(Integer))
			self._friend_of_friend?(User.find(:first, user_or_id));
		end
	end
	
	alias :is_friend_of_friend? :friend_of_friend?
	
	
	#Compare the friends lists of the user (self) and the viewing user (user).
	#If the viewing user is a friend of a friend there will be an intersection of
	# their friends lists. This only covers mutual friends and is intended as such.
	#This does not use User.friends_of_friends function.
	def _friend_of_friend?(user)
		user_friends_set = Set.new(user.friends_ids.flatten);
		my_friends_set = Set.new(self.friends_ids.flatten);
		
		user_friends_set.delete(user.userid);
		my_friends_set.delete(self.userid);
		
		return !my_friends_set.intersection(user_friends_set).empty?();
	end

end # Class User < Cacheable

class AnonymousUser
	def friend?(user_or_id)
		return false;
	end
	
	def friend_of_friend?(user_or_id)
		return false;
	end
	
	alias :is_friend_of_friend? :friend_of_friend?;
	
	def friends_ids
		return [];
	end
end
