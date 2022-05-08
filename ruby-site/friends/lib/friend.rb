lib_require :Core, "storable/storable"
lib_require :Core, "users/user"
lib_require :Friends, 'friend_comment'
lib_want :Observations, "observable";

class Friend < Storable
	attr_reader :user, :owner
	
	init_storable(:usersdb, "friends");
	#set_prefix("ruby_friends")
	
	relation_singular :comment, [:userid, :friendid], FriendComment
	relation_singular :mutual, [:friendid, :userid], Friend
	
	def self.all(userid)
    	return Friend.find(:promise, :all, userid)
	end
	
	def user
		unless (@user)
			@user = User.find(:first, friendid, :promise => lambda {|result| 
				unless result
					self.delete
					return AnonymousUser.new(nil, friendid)
				end
				return result
			})
		end
		return @user
	end
	
	def owner
		unless (@owner)
			@owner = User.find(:first, userid, :promise => lambda {|result|
				unless result
					self.delete
					return AnonymousUser.new(nil, userid)
				end
				return result
			})
		end
		return @owner
	end
	
	if (site_module_loaded?(:Observations))
		include Observations::Observable
		OBSERVABLE_NAME = "Friends"
		observable_event :create, proc{"#{self.owner.link} added #{self.user.link} as #{self.owner.possessive_pronoun} friend."}
	end
end

class User < Cacheable
	relation_multi_cached :friends, :userid, Friend, "ruby_friends"
	relation_multi_cached :friends_of, :userid, Friend, "ruby_friends_of", :friendid
	
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
		return if (Friend.find(:first, self.userid, friend_id)) #already a friend
		friend = Friend.new
		friend.userid = self.userid
		friend.friendid = friend_id
		friends << friend
		friend.store(:ignore, :affected_rows)
		
		if (friend.affected_rows == 0)
			return false
		end
		
		message = Message.new;
		message.sender = self;
		message.receiver = User.find(:first, friend_id);
		form_key = SecureForm.encrypt(message.receiver)
		message.subject = "Friends List Notification";
		message.text = "[url=/profile.php?uid=#{self.userid}]#{self.username}[/url] has added you to #{self.possessive_pronoun} friends " +
			"list. You may remove yourself by clicking [url=/users/#{CGI::escape(message.receiver.username)}/friends/reverse/remove/#{self.userid}?form_key=#{form_key}]here[/url], " +
			"or add #{self.objective_pronoun} to yours by clicking [url=/users/#{CGI::escape(message.receiver.username)}/friends/add/#{self.userid}?form_key=#{form_key}]here[/url]."
		message.send();
		return true
	end
	#
	# Given a Friend id remove it from the list of friends on this object
	# and delete that association from the database
	#
	def remove_friend(user_or_id)
			user_id = case user_or_id
				when Integer
					user_or_id
				else
					user_or_id.userid
				end
			_remove_friend_by_id(user_id)
	end
	def _remove_friend_by_id(friend_id)
		  friend = friends.find {|o|
			o.friendid == friend_id
			}
		#friend is always nil here in test cases, this is why things aren't getting deleted
		#there is a new ordered_map test that demonstrates that find works right, so it seems
		#the friends list must have an issue.
		unless friend.nil?
			message = Message.new;
			message.sender = self;
			message.receiver = User.find(:first, friend_id);
			form_key = SecureForm.encrypt(message.receiver)
			message.subject = "Friends List Notification";
			message.text = "[url=/profile.php?uid=#{self.userid}]#{self.username}[/url] has removed you from #{self.possessive_pronoun} friends " +
				"list. You may remove #{self.objective_pronoun} from yours by clicking [url=/users/#{CGI::escape(message.receiver.username)}/friends/remove/#{self.userid}?form_key=#{form_key}]here[/url]."
			message.send();
			
			friends.delete friend
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
	
	def _friend?(user_id)
		friend_id_list = self.friends.map{|friend| friend.friendid};
		
		return friend_id_list.include?(user_id);
	end
	
	def friend_of_friend?(user_or_id)
		if(user_or_id.kind_of?(User))
			self._friend_of_friend?(user_or_id.userid);
		elsif(user_or_id.kind_of?(Integer))
			self._friend_of_friend?(user_or_id);
		end
	end
	
	alias :is_friend_of_friend? :friend_of_friend?
	
	def _friend_of_friend?(user_id)
		friends_of_friends_hash = self.friends_of_friends();
		if(friends_of_friends_hash.nil?())
			return false;
		end
		friends_of_friends_list = friends_of_friends_hash.keys;
		
		return friends_of_friends_list.include?(user_id);
	end
end