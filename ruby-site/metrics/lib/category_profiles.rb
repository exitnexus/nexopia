lib_require :Core, 'typeid'
lib_require :Metrics, 'metric_category'

module Metrics
	class CategoryProfiles < MetricCategory
		extend TypeID
		
		metric_category
		
		NUM_WITH_BASICS_ENABLED        = 1
		NUM_WITH_COMMENTS_ENABLED      = 2
		NUM_WITH_CONTACT_ENABLED       = 3
		NUM_WITH_INTERESTS_ENABLED     = 4
		NUM_WITH_BLOG_ENABLED          = 5
		NUM_WITH_POLL_ENABLED          = 6
		NUM_WITH_GALLERY_ENABLED       = 7
		FREEFORM_BLOCKS                = 8
		GALLERY_STRIPS                 = 9
		PLUS_USER_VISITS_SHOW          = 10
		NUM_SHOW_PLUS                  = 11
		NUM_SPOTLIGHT                  = 12
		NUM_HIDE_PROFILE_HITS          = 13
		NUM_HIDE_PROFILE_GUEST         = 14
		NUM_EMAIL_SEARCHABLE           = 15
		NUM_NOTIFY_REMOVE_AS_FRIEND    = 16
		NUM_THUMBNAIL_FRIENDS_BLOCK    = 17
		NUM_THUMBNAIL_RECENT_VISIT     = 18
		NUM_ANON_ON_RECENT_VISIT       = 19
		NUM_CLASSIC_PICTURE_VIEWER     = 20
		NUM_IMPROVED_PICTURE_VIEWER    = 21
		NUM_WHO_UPDATED_PROFILES_DAY   = 22
		NUM_WHO_UPDATED_PROFILES_WEEK  = 23
		NUM_WHO_UPDATED_PROFILES_MONTH = 24
		
		def initialize()
			super()
			
			@metrics[NUM_WITH_BASICS_ENABLED] = {
				:description => "# of Users with Basics Block Enabled",
				:header => "Users",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => false
			}
			@metrics[NUM_WITH_COMMENTS_ENABLED] = {
				:description => "# of Users with Comments Block Enabled",
				:header => "Users",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => false
			}
			@metrics[NUM_WITH_CONTACT_ENABLED] = {
				:description => "# of Users with Contact Enabled",
				:header => "Users",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => false
			}
			@metrics[NUM_WITH_INTERESTS_ENABLED] = {
				:description => "# of Users with Interests Blocks Enabled",
				:header => "Users",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => false
			}
			@metrics[NUM_WITH_BLOG_ENABLED] = {
				:description => "# of Users with Latest Blog Entry Blocks Enabled",
				:header => "Users",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => false
			}
			@metrics[NUM_WITH_POLL_ENABLED] = {
				:description => "# of Users with Poll Block Enabled",
				:header => "Users",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => false
			}
			@metrics[NUM_WITH_GALLERY_ENABLED] = {
				:description => "# of Users with Recent Galleries Block Enabled",
				:header => "Users",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => false
			}
			@metrics[FREEFORM_BLOCKS] = {
				:description => "# of Freeform Blocks",
				:header => "Freeform blocks",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => false
			}
			@metrics[GALLERY_STRIPS] = {
				:description => "# of Gallery Strips",
				:header => "Gallery strips",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => false
			}
			@metrics[PLUS_USER_VISITS_SHOW] = {
				:description => "# Who Allow Plus Members To See Visits...",
				:header => "Users",
				:subheaders => ['Anyone', 'Friends only', 'Nobody'],
				:usertypes => true,
				:allow_historical => false
			}
			@metrics[NUM_SHOW_PLUS] = {
				:description => "# who Show Plus",
				:header => "Users",
				:subheaders => [],
				:usertypes => true,
				:allow_historical => false
			}
			@metrics[NUM_SPOTLIGHT] = {
				:description => "# who are Eligible for Spotlight",
				:header => "Users",
				:subheaders => [],
				:usertypes => true,
				:allow_historical => false
			}
			@metrics[NUM_HIDE_PROFILE_HITS] = {
				:description => "# who Hide Profile Hits",
				:header => "Users",
				:subheaders => [],
				:usertypes => true,
				:allow_historical => false
			}
			@metrics[NUM_HIDE_PROFILE_GUEST] = {
				:description => "# who Hide Profile From Guests and Ignored",
				:header => "Users",
				:subheaders => [],
				:usertypes => true,
				:allow_historical => false
			}
			@metrics[NUM_EMAIL_SEARCHABLE] = {
				:description => "# who have Email Searchable",
				:header => "Users",
				:subheaders => [],
				:usertypes => true,
				:allow_historical => false
			}
			@metrics[NUM_NOTIFY_REMOVE_AS_FRIEND] = {
				:description => "# who are Notified when Someone Removes Them As Friend",
				:header => "Users",
				:subheaders => [],
				:usertypes => true,
				:allow_historical => false
			}
			@metrics[NUM_THUMBNAIL_FRIENDS_BLOCK] = {
				:description => "# who use Thumbnail View of Friends Profile Block",
				:header => "Users",
				:subheaders => [],
				:usertypes => true,
				:allow_historical => false
			}
			@metrics[NUM_THUMBNAIL_RECENT_VISIT] = {
				:description => "# who use Thumbnails on Recent Visitor List",
				:header => "Users",
				:subheaders => [],
				:usertypes => true,
				:allow_historical => false
			}
			@metrics[NUM_ANON_ON_RECENT_VISIT] = {
				:description => "# who Show Anons on Recent Visitor List",
				:header => "Users",
				:subheaders => [],
				:usertypes => true,
				:allow_historical => false
			}
			@metrics[NUM_CLASSIC_PICTURE_VIEWER] = {
				:description => "# who use Classic Picture Viewer",
				:header => "Users",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => false
			}
			@metrics[NUM_IMPROVED_PICTURE_VIEWER] = {
				:description => "# who use Improved Picture Viewer",
				:header => "Users",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => false
			}
			@metrics[NUM_WHO_UPDATED_PROFILES_DAY] = {
				:description => "# of Users Who Updated Profile In Last Day",
				:header => "Users",
				:subheaders => [],
				:usertypes => true,
				:allow_historical => true
			}
			@metrics[NUM_WHO_UPDATED_PROFILES_WEEK] = {
				:description => "# of Users Who Updated Profile In Last Week",
				:header => "Users",
				:subheaders => [],
				:usertypes => true,
				:allow_historical => true
			}
			@metrics[NUM_WHO_UPDATED_PROFILES_MONTH] = {
				:description => "# of Users Who Updated Profile In Last Month",
				:header => "Users",
				:subheaders => [],
				:usertypes => true,
				:allow_historical => true
			}
		end
		
		def self.description()
			return "Profiles"
		end
		
		def populate(metrics, date, historical)
			metrics = [*metrics]
			day_from, day_to = get_date_from_to(date, 1)
			date = day_from

			if (okay_to_run?(NUM_WITH_BASICS_ENABLED, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM
		 			(SELECT DISTINCT pdb.userid
		 			 FROM profiledisplayblocks pdb
			 		 WHERE path = 'basics'
					) AS innerquery"
				populate_type(NUM_WITH_BASICS_ENABLED, query, date, 0, 'na')
			end
			
			if (okay_to_run?(NUM_WITH_COMMENTS_ENABLED, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM
		 			(SELECT DISTINCT pdb.userid
		 			 FROM profiledisplayblocks pdb
			 		 WHERE path = 'comments'
					) AS innerquery"
				populate_type(NUM_WITH_COMMENTS_ENABLED, query, date, 0, 'na')
			end
			
			if (okay_to_run?(NUM_WITH_CONTACT_ENABLED, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM
		 			(SELECT DISTINCT pdb.userid
		 			 FROM profiledisplayblocks pdb
			 		 WHERE path = 'contact'
					) AS innerquery"
				populate_type(NUM_WITH_CONTACT_ENABLED, query, date, 0, 'na')
			end
			
			if (okay_to_run?(NUM_WITH_INTERESTS_ENABLED, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM
		 			(SELECT DISTINCT pdb.userid
		 			 FROM profiledisplayblocks pdb
			 		 WHERE path = 'interests'
					) AS innerquery"
				populate_type(NUM_WITH_INTERESTS_ENABLED, query, date, 0, 'na')
			end
			
			if (okay_to_run?(NUM_WITH_BLOG_ENABLED, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM
		 			(SELECT DISTINCT pdb.userid
		 			 FROM profiledisplayblocks pdb
			 		 WHERE path = 'blog'
					) AS innerquery"
				populate_type(NUM_WITH_BLOG_ENABLED, query, date, 0, 'na')
			end
			
			if (okay_to_run?(NUM_WITH_POLL_ENABLED, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM
		 			(SELECT DISTINCT pdb.userid
		 			 FROM profiledisplayblocks pdb
			 		 WHERE path = 'poll'
					) AS innerquery"
				populate_type(NUM_WITH_POLL_ENABLED, query, date, 0, 'na')
			end
			
			if (okay_to_run?(NUM_WITH_GALLERY_ENABLED, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM
		 			(SELECT DISTINCT pdb.userid
		 			 FROM profiledisplayblocks pdb
			 		 WHERE path = 'recent_galleries'
					) AS innerquery"
				populate_type(NUM_WITH_GALLERY_ENABLED, query, date, 0, 'na')
			end
			
			if (okay_to_run?(FREEFORM_BLOCKS, metrics, historical))
				query = "SELECT COUNT(*) AS thecount
				FROM profiledisplayblocks pdb
				WHERE pdb.path = 'freeform'"
				populate_type(FREEFORM_BLOCKS, query, date, 0, 'na')
			end
			
			if (okay_to_run?(GALLERY_STRIPS, metrics, historical))
				query = "SELECT COUNT(*) AS thecount
				FROM profiledisplayblocks pdb
				WHERE pdb.path = 'gallery'"
				populate_type(GALLERY_STRIPS, query, date, 0, 'na')
			end
			
			if (okay_to_run?(PLUS_USER_VISITS_SHOW, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM users
					JOIN useractivetime ON users.userid = useractivetime.userid
					WHERE anonymousviews = 'y'"
				populate_types(PLUS_USER_VISITS_SHOW, query, date, 0)
				query = "SELECT COUNT(*) AS thecount FROM users
					JOIN useractivetime ON users.userid = useractivetime.userid
					WHERE anonymousviews = 'f'"
				populate_types(PLUS_USER_VISITS_SHOW, query, date, 1)
				query = "SELECT COUNT(*) AS thecount FROM users
					JOIN useractivetime ON users.userid = useractivetime.userid
					WHERE anonymousviews = 'n'"
				populate_types(PLUS_USER_VISITS_SHOW, query, date, 2)
			end
			
			if (okay_to_run?(NUM_SHOW_PLUS, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM profile JOIN users
					ON profile.userid = users.userid
					JOIN useractivetime ON users.userid = useractivetime.userid
					WHERE showpremium = 'y'"
				populate_types(NUM_SHOW_PLUS, query, date, 0)
			end
			if (okay_to_run?(NUM_SPOTLIGHT, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM users
					JOIN useractivetime ON users.userid = useractivetime.userid
					WHERE spotlight = 'y'"
				populate_types(NUM_SPOTLIGHT, query, date, 0)
			end
			if (okay_to_run?(NUM_HIDE_PROFILE_HITS, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM users
					JOIN useractivetime ON users.userid = useractivetime.userid
					WHERE hidehits = 'y'"
				populate_types(NUM_HIDE_PROFILE_HITS, query, date, 0)
			end
			if (okay_to_run?(NUM_HIDE_PROFILE_GUEST, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM users
					JOIN useractivetime ON users.userid = useractivetime.userid
					WHERE hideprofile = 'y'"
				populate_types(NUM_HIDE_PROFILE_GUEST, query, date, 0)
			end
			if (okay_to_run?(NUM_EMAIL_SEARCHABLE, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM users
					JOIN useractivetime ON users.userid = useractivetime.userid
					WHERE searchemail = 'y'"
				populate_types(NUM_EMAIL_SEARCHABLE, query, date, 0)
			end
			if (okay_to_run?(NUM_NOTIFY_REMOVE_AS_FRIEND, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM users
					JOIN useractivetime ON users.userid = useractivetime.userid
					WHERE friendsauthorization = 'y'"
				populate_types(NUM_NOTIFY_REMOVE_AS_FRIEND, query, date, 0)
			end
			if (okay_to_run?(NUM_THUMBNAIL_FRIENDS_BLOCK, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM users
					JOIN useractivetime ON users.userid = useractivetime.userid
					WHERE profilefriendslistthumbs = 'y'"
				populate_types(NUM_THUMBNAIL_FRIENDS_BLOCK, query, date, 0)
			end
			if (okay_to_run?(NUM_THUMBNAIL_RECENT_VISIT, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM users
					JOIN useractivetime ON users.userid = useractivetime.userid
					WHERE recentvisitlistthumbs = 'y'"
				populate_types(NUM_THUMBNAIL_RECENT_VISIT, query, date, 0)
			end
			if (okay_to_run?(NUM_ANON_ON_RECENT_VISIT, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM users
					JOIN useractivetime ON users.userid = useractivetime.userid
					WHERE recentvisitlistanon = 'y'"
				populate_types(NUM_ANON_ON_RECENT_VISIT, query, date, 0)
			end
			if (okay_to_run?(NUM_CLASSIC_PICTURE_VIEWER, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM profiledisplayblocks
					WHERE profiledisplayblocks.path = 'classic_film_strip'"
				populate_type(NUM_CLASSIC_PICTURE_VIEWER, query, date, 0, 'na')
			end
			if (okay_to_run?(NUM_IMPROVED_PICTURE_VIEWER, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM profiledisplayblocks
					WHERE profiledisplayblocks.path = 'film_strip'"
				populate_type(NUM_IMPROVED_PICTURE_VIEWER, query, date, 0, 'na')
			end
				
			if (okay_to_run?(NUM_WHO_UPDATED_PROFILES_DAY, metrics, historical))
				query = "SELECT COUNT(*) AS thecount
					FROM profile JOIN users
					ON profile.userid = users.userid
					JOIN useractivetime ON users.userid = useractivetime.userid
					WHERE profile.profileupdatetime >= #{day_from}
					AND profile.profileupdatetime <= #{day_to}"
				populate_types(NUM_WHO_UPDATED_PROFILES_DAY, query, date, 0)
			end
			
			if (okay_to_run?(NUM_WHO_UPDATED_PROFILES_WEEK, metrics, historical))
				week_from, week_to = get_date_from_to(date, 7)
				query = "SELECT COUNT(*) AS thecount
					FROM profile JOIN users
					ON profile.userid = users.userid
					JOIN useractivetime ON users.userid = useractivetime.userid
					WHERE profile.profileupdatetime >= #{week_from}
					AND profile.profileupdatetime <= #{week_to}"
				populate_types(NUM_WHO_UPDATED_PROFILES_WEEK, query, date, 0)
			end
			
			if (okay_to_run?(NUM_WHO_UPDATED_PROFILES_MONTH, metrics, historical))
				month_from, month_to = get_date_from_to(date, 30)
				query = "SELECT COUNT(*) AS thecount
					FROM profile JOIN users
					ON profile.userid = users.userid
					JOIN useractivetime ON users.userid = useractivetime.userid
					WHERE profile.profileupdatetime >= #{month_from}
					AND profile.profileupdatetime <= #{month_to}"
				populate_types(NUM_WHO_UPDATED_PROFILES_MONTH, query, date, 0)
			end
		end

	end
end
