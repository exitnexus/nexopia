lib_require :Orwell, 'notifications_sent', 'send_email', 'register'
lib_require :Core, 'constants'
lib_require :Messages, 'message'
lib_require :Profile, 'profile_view'
lib_require :Friends, 'friend'
lib_require :Blogs, 'blog_user'
lib_require :Core, 'constants'
lib_require :Plus, 'plus'

module Orwell
	# Send the user a message after they've been on the site for a year.
	# Use a range between a year ago and 1 year + 1 week ago so that we don't miss anyone.
	# NotificationsSent will take care of not sending it multiple times to the same person.
	class Aniversary
		extend TypeID
		orwell_constraint :matches?
		orwell_action :perform_action
		
		def self.matches?(user)
			return false if (user.frozen? || user.state == "deleted")
			
			now = Time.now.to_i()
			earliest_join_time = now - (372 * Constants::DAY_IN_SECONDS)
			latest_join_time = now - (365 * Constants::DAY_IN_SECONDS)

			if ((user.jointime >= earliest_join_time) && (user.jointime < latest_join_time))
				# Already notified user?
				result = NotificationsSent::when_sent(user.userid, self.typeid)
				return !result
			end
			return false
		end
		
		def self.perform_action(user)		
			return false if (user.nil?)
			
			if (!NotificationsSent::add_sent(user.userid, self.typeid))
				return false
			end

			t = Template.instance('orwell', 'aniversary')
			t.username = user.username
			t.hits = Profile::ProfileView.views(user)
			t.friends = user.friends_ids.length
			t.albums = user.galleries.length
			t.friends_albums = self.friends_gallery_create_count(user)
			t.blog_entries = user.post_count
			t.friends_blog_entries = self.friends_blog_posts_count(user)
			
			message = Message.new
			message.sender_name = "Nexopia"
			message.receiver = user
			message.subject = "Happy Nexiversary!"
			message.text = t.display()
			message.send();
			
			# if the user doesn't have message forwarding enabled send them an email with 
			# the same contents as the message
			
			if (user.fwsitemsgs && !user.email.nil?)
				msg = SendEmail.new
				msg.subject = 'Happy Nexiversary!'
				msg.send(user, 'aniversary_plain',
				 	:html_template => 'aniversary', 
					:template_module => 'orwell',
					:limit_emails => true,
					:username => user.username,
					:hits => Profile::ProfileView.views(user),
					:friends => user.friends_ids.length,
					:albums => user.galleries.length,
					:friends_albums => self.friends_gallery_create_count(user),
					:blog_entries => user.post_count,
					:friends_blog_entries => self.friends_blog_posts_count(user)
				)
			end
			
 			# This is the id for the NexopiaPlus account.  Seemed like a good idea at the time.
			nexopia_plus_id = 1106759
			duration = Plus::PLUS_QUANTITIES[Plus::FREE_PLUS] # This is the standard value for a free week of Plus
			fromid = 0 # This will be from an admin, so no 'from' id.
			trackid = 0 # No invoice, will show up as 'Given' in the Plus log.
			
			old_plus_expire_time = user.premiumexpiry
			
			Plus::Plus.add_plus(user.userid, duration, fromid, trackid, nexopia_plus_id)

			user_post_update = User.find(:first, user.userid)
			new_plus_expire_time = user_post_update.premiumexpiry
			
			if( (user.userid == user_post_update.userid) && !(new_plus_expire_time > old_plus_expire_time))
				$log.info "User #{user_post_update.username} didn't get Plus from Nexiversary message.\nOld plusexpiry = #{old_plus_expire_time}, New plusexpiry = #{old_plus_expire_time}, userid = #{user_post_update.userid}"
			end
			
		end # def self.perform_action(user)
		
		# Get the total number of blog posts a user's friends have made
		# in the last year.
		def self.friends_blog_posts_count(user)
			
				#  Get the userids of the user's friends
				count = 0
 				friend_id_list = []
				user.friends_ids.each{ |id|
					if( id[1] != user.userid )
						friend_id_list << id[1];
					end
				}
				
				# Do a direct query to get the count from the blog table.
				if(!friend_id_list.empty?())
					min_time = Time.now.to_i() - Constants::YEAR_IN_SECONDS
					results = Blogs::BlogPost.db.query("SELECT count(*) num FROM blog WHERE userid IN # AND time > ? AND visibility > 0", friend_id_list, min_time)

					results.each{ |row|
						count = row['num']
					}					
				end
				
				return count
				
		end

		# Get the total number of galleries created by user's friends in the last year.
		def self.friends_gallery_create_count(user)
			
			#  Get the userids of the user's friends
			count = 0
				friend_id_list = []
			user.friends_ids.each{ |id|
				if( id[1] != user.userid )
					friend_id_list << id[1];
				end
			}
			
			# Do a direct query to get the count from the gallery table.
			if(!friend_id_list.empty?())
				min_time = Time.now.to_i() - Constants::YEAR_IN_SECONDS
				results = Blogs::BlogPost.db.query("SELECT count(*) num FROM gallery WHERE ownerid IN # AND created > ?", friend_id_list, min_time)

				results.each{ |row|
					count = row['num']
				}					
			end
				
				return count
				
		end


	end
	
end
