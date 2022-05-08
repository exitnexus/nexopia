lib_require :Core, 'typeid'
lib_require :Metrics, 'metric_category'

module Metrics
	class CategoryForums < MetricCategory
		extend TypeID
		
		metric_category
		
		NUM_FORUMS                   = 1
		NUM_FORUM_THREADS            = 2
		NUM_PRIVATE_FORUMS           = 3
		NUM_PRIVATE_FORUM_THREADS    = 4
		NUM_FORUM_POSTS              = 5
		NUM_WITH_FORUM_PREFERENCES   = 6
		NUM_WITH_POSTS_PER_PAGE      = 7
		NUM_THREAD_SUBSCRIPTIONS     = 8
		
		def initialize()
			super()
			
			@metrics[NUM_FORUMS] = {
				:description => "# of Forums",
				:header => "Forums",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => false
			}
			@metrics[NUM_FORUM_THREADS] = {
				:description => "# of Forum Threads",
				:header => "Threads",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => false
			}
			@metrics[NUM_PRIVATE_FORUMS] = {
				:description => "# of Private Forums",
				:header => "Forums",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => false
			}
			@metrics[NUM_PRIVATE_FORUM_THREADS] = {
				:description => "# of Private Forum Threads",
				:header => "Threads",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => false
			}
			@metrics[NUM_FORUM_POSTS] = {
				:description => "# of Forum Posts",
				:header => "Posts",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => true
			}
			@metrics[NUM_WITH_FORUM_PREFERENCES] = {
				:description => "# With Forum Preferences...",
				:header => "Users",
				:subheaders => ['Return to thread listing after posting',
					'Automatically subscribe after posting',
					'Jump to last old post',
					'Show user signatures'],
				:usertypes => true,
				:allow_historical => false
			}
			@metrics[NUM_WITH_POSTS_PER_PAGE] = {
				:description => "# With Posts Per Page...",
				:header => "Users",
				:subheaders => [
					'Posts per page 10',
					'Posts per page 25',
					'Posts per page 50',
					'Posts per page 100'],
				:usertypes => true,
				:allow_historical => false
			}
			@metrics[NUM_THREAD_SUBSCRIPTIONS] = {
				:description => "# of Thread Subscriptions",
				:header => "Subscriptions",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => false
			}
		end
		
		def self.description()
			return "Forums"
		end
		
		def populate(metrics, date, historical)
			metrics = [*metrics]
			day_from, day_to = get_date_from_to(date, 1)
			date = day_from
			
			if (okay_to_run?(NUM_FORUMS, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM forums"
				populate_type(NUM_FORUMS, query, date, 0, 'na',
			 		:db => :forumdb)
			end
			
			if (okay_to_run?(NUM_FORUM_THREADS, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM forumthreads"
				populate_type(NUM_FORUM_THREADS, query, date, 0, 'na',
			 		:db => :forumdb)
			end
			
			if (okay_to_run?(NUM_PRIVATE_FORUMS, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM forums
					WHERE public = 'n'"
				populate_type(NUM_PRIVATE_FORUMS, query, date, 0, 'na',
			 		:db => :forumdb)
			
				query = "SELECT COUNT(*) AS thecount FROM forumthreads
					JOIN forums ON forumthreads.forumid = forums.id
					WHERE forums.public = 'n'"
				populate_type(NUM_PRIVATE_FORUM_THREADS, query, date, 0, 'na',
					:db => :forumdb)
			end

			if (okay_to_run?(NUM_FORUM_POSTS, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM forumposts
				 	WHERE `time` >= #{day_from} AND `time` <= #{day_to}"
				populate_type(NUM_FORUM_POSTS, query, date, 0, 'na',
				 	:db => :forumdb)
			end
			
			if (okay_to_run?(NUM_WITH_FORUM_PREFERENCES, metrics, historical))
				# Return to thread listing after posting
				query = "SELECT COUNT(*) AS thecount FROM users
					JOIN useractivetime ON users.userid = useractivetime.userid
					WHERE replyjump = 'thread'"
				populate_types(NUM_WITH_FORUM_PREFERENCES, query, date, 0)
				# Automatically subscribe after posting
				query = "SELECT COUNT(*) AS thecount FROM users
					JOIN useractivetime ON users.userid = useractivetime.userid
					WHERE autosubscribe = 'y'"
				populate_types(NUM_WITH_FORUM_PREFERENCES, query, date, 1)
				# Jump to last old post
				query = "SELECT COUNT(*) AS thecount FROM users
					JOIN useractivetime ON users.userid = useractivetime.userid
					WHERE forumjumplastpost = 'y'"
				populate_types(NUM_WITH_FORUM_PREFERENCES, query, date, 2)
				# Show user signatures
				query = "SELECT COUNT(*) AS thecount FROM users
					JOIN useractivetime ON users.userid = useractivetime.userid
					WHERE showsigs = 'y'"
				populate_types(NUM_WITH_FORUM_PREFERENCES, query, date, 3)
			end
			
			if (okay_to_run?(NUM_WITH_POSTS_PER_PAGE, metrics, historical))
				# Posts per page 10
				query = "SELECT COUNT(*) AS thecount FROM users
					JOIN useractivetime ON users.userid = useractivetime.userid
					WHERE forumpostsperpage = 10"
				populate_types(NUM_WITH_POSTS_PER_PAGE, query, date, 0)
				# Posts per page 25
				query = "SELECT COUNT(*) AS thecount FROM users
					JOIN useractivetime ON users.userid = useractivetime.userid
					WHERE forumpostsperpage = 25"
				populate_types(NUM_WITH_POSTS_PER_PAGE, query, date, 1)
				# Posts per page 50
				query = "SELECT COUNT(*) AS thecount FROM users
					JOIN useractivetime ON users.userid = useractivetime.userid
					WHERE forumpostsperpage = 50"
				populate_types(NUM_WITH_POSTS_PER_PAGE, query, date, 2)
				# Posts per page 100
				query = "SELECT COUNT(*) AS thecount FROM users
					JOIN useractivetime ON users.userid = useractivetime.userid
					WHERE forumpostsperpage = 100"
				populate_types(NUM_WITH_POSTS_PER_PAGE, query, date, 3)
			end

			if (okay_to_run?(NUM_THREAD_SUBSCRIPTIONS, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM forumread
					WHERE subscribe = 'y'"
				populate_type(NUM_THREAD_SUBSCRIPTIONS, query, date, 0, 'na',
			 		:db => :forumdb)
			end
			
		end
		
	end
end
