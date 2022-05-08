lib_require :Orwell, 'notifications_sent', 'send_email', 'register'
lib_require :Core, 'constants'
lib_require :Messages, 'message'
lib_require :Profile, 'profile_view'
lib_require :Friends, 'friend'
lib_require :Blogs, 'blog_user'

module Orwell
	# Send the user a message after they've been on the site for two weeks.
	# Use a week range so that we don't miss anyone.
	# NotificationsSent will take care of not sending it multiple times.
	class TwoWeek
		extend TypeID
		orwell_constraint :matches?
		orwell_action :perform_action
		
		def self.matches?(user)			
			return false if (user.frozen? || user.state == "deleted")
			
			now = Time.now.to_i()
			latest_join_time = now - (14 * Constants::DAY_IN_SECONDS)
			earliest_join_time = now - (21 * Constants::DAY_IN_SECONDS)
			
			if ((user.activetime < latest_join_time) && (user.jointime >= earliest_join_time) && (user.jointime < latest_join_time))
				# Already notified user?
				result = NotificationsSent::when_sent(user.userid, self.typeid)
				return !result
			end
			return false
		end
		
		def self.perform_action(user)
			return false if (user.nil?)

			if (!NotificationsSent::add_sent(user.id, self.typeid))
				return false
			end

			subject = "New User Update"
			hits = Profile::ProfileView.views(user)
			friend_count = user.friends_ids.length
			album_count = user.galleries.length
			friend_album_count = self.friends_gallery_create_count(user)
			blog_post_count = user.post_count
			friend_blog_count = user.friends_sorted_raw_blog_post_data.length
			
			t = Template.instance('orwell', 'two_week')
			t.username = user.username
			t.hits = hits
			t.friends = friend_count
			t.albums = album_count
			t.friends_albums = friend_album_count
			t.blog_entries = user.post_count
			t.friends_blog_entries = friend_blog_count
			
			message = Message.new
			message.sender_name = "Nexopia"
			message.receiver = user
			message.subject = subject
			message.text = t.display()
			message.send();
			
			# if the user doesn't have message forwarding enabled send them an email with 
			# the same contents as the message
			if (user.fwsitemsgs && !user.email.nil?)
				msg = SendEmail.new
				msg.subject = subject
				msg.send(user, 'two_week_plain',
				 	:html_template => 'two_week', 
					:template_module => 'orwell',
					:limit_emails => true,
					:username => user.username,
					:hits => hits,
					:friends => friend_count,
					:albums => album_count,
					:friends_albums => friend_album_count,
					:blog_entries => blog_post_count,
					:friends_blog_entries => friend_blog_count
				)
			end
		end # def self.perform_action(user)
		
		
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
				min_time = Time.now.to_i() - (31 * Constants::DAY_IN_SECONDS)
				results = Blogs::BlogPost.db.query("SELECT count(*) num FROM gallery WHERE ownerid IN # AND created > ?", friend_id_list, min_time)

				results.each{ |row|
					count = row['num']
				}					
			end
				
				return count
				
		end
		
	end
	
end
