lib_require :Core, 'typeid'
lib_require :Metrics, 'metric_category'

module Metrics
	class CategoryUserNotifications < MetricCategory
		extend TypeID
		
		metric_category
		
		FORUM_POSTS                  = 1
		BLOG_POSTS                   = 2
		ALBUMS                       = 3
		PROFILE_PIC_UPLOADS          = 4
		NEW_ACCOUNTS                 = 5
		
		def initialize()
			super()
			
			@metrics[FORUM_POSTS] = {
				:description => "# of Forum Posts in Past Two Weeks",
				:header => "Posts",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => true
			}
			@metrics[BLOG_POSTS] = {
				:description => "# of Blog Posts in Past Two Weeks",
				:header => "Posts",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => true
			}
			@metrics[ALBUMS] = {
				:description => "# of Albums Created In Past Two Weeks",
				:header => "Albums",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => true
			}
			@metrics[PROFILE_PIC_UPLOADS] = {
				:description => "# of Profile Picture Uploads In Past Two Weeks",
				:header => "Pic uploads",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => true
			}
			@metrics[NEW_ACCOUNTS] = {
				:description => "# of New Accounts Created In Past Two Weeks",
				:header => "New accounts",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => true
			}
		end
		
		def self.description()
			return "User Notifications"
		end
		
		def populate(metrics, date, historical)
			metrics = [*metrics]
			date_from, date_to = get_date_from_to(date, 14)
			date = date_to - Constants::DAY_IN_SECONDS
			
			if (okay_to_run?(FORUM_POSTS, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM forumposts
					WHERE `time` >= #{date_from} AND `time` <= #{date_to}"
				populate_type(FORUM_POSTS, query, date, 0, 'na',
				 	:db => :forumdb)
			end
			
			if (okay_to_run?(BLOG_POSTS, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM blog
					WHERE `time` >= #{date_from} AND `time` <= #{date_to}"
				populate_type(BLOG_POSTS, query, date, 0, 'na')
			end
			
			if (okay_to_run?(ALBUMS, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM gallery
					WHERE `created` >= #{date_from} AND `created` <= #{date_to}"
				populate_type(ALBUMS, query, date, 0, 'na')
			end
			
			if (okay_to_run?(PROFILE_PIC_UPLOADS, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM pics p
					JOIN gallerypics gp ON p.userid = gp.userid AND
					p.gallerypicid = gp.id
					WHERE gp.`created` >= #{date_from} AND
					gp.`created` <= #{date_to}"
				populate_type(PROFILE_PIC_UPLOADS, query, date, 0, 'na')			
			end
			
			if (okay_to_run?(NEW_ACCOUNTS, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM users
					WHERE jointime >= #{date_from} AND
					jointime <= #{date_to}"
				populate_type(NEW_ACCOUNTS, query, date, 0, 'na')
			end
		end
		
	end
end
