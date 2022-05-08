lib_require :Orwell, 'notifications_sent', 'send_email', 'register'
lib_require :Core, 'constants'
lib_require :Metrics, 'category_user_notifications'

module Orwell
	# Was the user's last active time between 14 and 21 days ago?
	class LastActiveTime14
		extend TypeID
		orwell_constraint :matches?
		orwell_action :perform_action
		
		def self.matches?(user)
			return false if (user.frozen? || user.state == "deleted")
			return false if user.email == nil

			now = Time.now.to_i()
			min_active_time = now - (90 * Constants::DAY_IN_SECONDS)
			max_active_time = now - (14 * Constants::DAY_IN_SECONDS)
						
			if ((user.activetime > min_active_time) &&
				(user.activetime < max_active_time))
				# Already notified user since they were last active?
				send = false
				send_date = NotificationsSent::when_sent(user.userid, self.typeid)
				if (send_date)
					if (send_date < user.activetime)
						send = true
					end
				else
					send = true
				end
				return send
			end
			return false
		end
		
		def self.perform_action(user)
			if (!NotificationsSent::add_sent(user.userid, self.typeid))
				return false
			end

			new_message_count = user.newmsgs
			hits = Profile::ProfileView.views(user)
			new_comment_count = user.newcomments
			
			friends_blog_count = self.friends_blog_posts_count(user)
			friends_album_count = self.friends_gallery_create_count(user)
			
			metrics = Metrics::CategoryUserNotifications.new()

			yesterday = Time.now().to_i - Constants::DAY_IN_SECONDS

			forum_post_count_data = metrics.data(Metrics::CategoryUserNotifications::FORUM_POSTS, yesterday, yesterday)
			forum_post_count = forum_post_count_data[0][1]
			
			total_blog_post_count_data = metrics.data(Metrics::CategoryUserNotifications::BLOG_POSTS, yesterday, yesterday)
			total_blog_post_count = total_blog_post_count_data[0][1]

			total_album_count_data = metrics.data(Metrics::CategoryUserNotifications::ALBUMS, yesterday, yesterday)
			total_album_count = total_album_count_data[0][1]
			
			total_pic_count_data = metrics.data(Metrics::CategoryUserNotifications::PROFILE_PIC_UPLOADS, yesterday, yesterday)
			total_pic_count = total_pic_count_data[0][1]

			new_account_count_data = metrics.data(Metrics::CategoryUserNotifications::PROFILE_PIC_UPLOADS, yesterday, yesterday)
			new_account_count = new_account_count_data[0][1]

			if (user.fwsitemsgs)
				msg = SendEmail.new
				msg.subject = 'Nexopia Update'
				msg.send(user, 'inactive_user_plain',
					:limit_emails => true,
				 	:html_template => 'inactive_user',
					:username => user.username,
					:friends_blog_count => friends_blog_count,
					:friends_album_count => friends_album_count,
					:forum_post_count => forum_post_count,
					:total_blog_post_count => total_blog_post_count,
					:total_album_count => total_album_count,
					:total_pic_count => total_pic_count,
					:new_account_count => new_account_count,
					:hits => hits,
					:new_message_count => new_message_count,
					:new_comment_count => new_comment_count
				)
			end
		end
		
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
				min_time = Time.now.to_i() - (14 * Constants::DAY_IN_SECONDS)
				results = Blogs::BlogPost.db.query("SELECT count(*) num FROM blog WHERE userid IN # AND time > ? AND visibility > 0", friend_id_list, min_time)

				results.each{ |row|
					count = row['num']
				}					
			end

			return count
				
		end
		
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
				min_time = Time.now.to_i() - (14 * Constants::DAY_IN_SECONDS)
				results = Blogs::BlogPost.db.query("SELECT count(*) num FROM gallery WHERE ownerid IN # AND created > ?", friend_id_list, min_time)

				results.each{ |row|
					count = row['num']
				}					
			end
				
			return count
				
		end
		
	end
	
end
