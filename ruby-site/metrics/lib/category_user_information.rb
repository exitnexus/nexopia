lib_require :Core, 'users/locs', 'users/interests', 'typeid'
lib_require :Metrics, 'metric_category'

module Metrics
	class CategoryUserInformation < MetricCategory
		extend TypeID
		
		metric_category
		
		AGE                          = 1
		SEX                          = 2
		LOCATION                     = 3
		INTERESTS                    = 4
		NUM_OF_USERS                 = 5
		NUM_OF_FRIENDS               = 15
		NUM_OF_FRIEND_INVITES        = 7
		NUM_WHO_HAVE_USED_GALLERIES  = 8
		NUM_WHO_HAVE_USED_BLOGS      = 9
		NUM_WHO_HAVE_USED_FORUMS     = 10
		NUM_WITH_GENERAL_PREFERENCES = 11
		NUM_OF_FRIENDS_TOTAL         = 12
		NUM_OF_MUTUAL_FRIENDS_TOTAL  = 13
		NUM_OF_MUTUAL_FRIENDS_AVG    = 14
		
		def initialize()
			super()
			
			@metrics[AGE] = {
				:description => "Age",
				:header => "Users",
				:subheaders => [],
				:usertypes => true,
				:allow_historical => false
			}
			@metrics[SEX] = {
				:description => "Sex",
				:header => "Users",
				:subheaders => ["Male", "Female"],
				:usertypes => true,
				:allow_historical => false
			}
			@metrics[LOCATION] = {
				:description => "Location",
				:header => "Users",
				:subheaders => [],
				:usertypes => true,
				:allow_historical => false
			}
			@metrics[INTERESTS] = {
				:description => "Interests",
				:header => "Users",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => false
			}
			@metrics[NUM_OF_USERS] = {
				:description => "# of Users",
				:header => "Users",
				:subheaders => [],
				:usertypes => true,
				:allow_historical => false
			}
			@metrics[NUM_OF_FRIENDS] = {
				:description => "# of Friends",
				:header => "Friends",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => false
			}
			@metrics[NUM_OF_FRIEND_INVITES] = {
				:description => "# of Friend Invites",
				:header => "Users",
				:subheaders => [],
				:usertypes => true,
				:allow_historical => false
			}
			@metrics[NUM_WHO_HAVE_USED_GALLERIES] = {
				:description => "# Who Have Used Galleries",
				:header => "Users",
				:subheaders => [],
				:usertypes => true,
				:allow_historical => true
			}
			@metrics[NUM_WHO_HAVE_USED_BLOGS] = {
				:description => "# Who Have Used Blogs",
				:header => "Users",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => true
			}
			@metrics[NUM_WHO_HAVE_USED_FORUMS] = {
				:description => "# Who Have Used Forums",
				:header => "Users",
				:subheaders => [],
				:usertypes => true,
				:allow_historical => true
			}
			@metrics[NUM_WITH_GENERAL_PREFERENCES] = {
				:description => "# With General Preferences",
				:header => "Users",
				:subheaders => ["Show Status Bar", "Show Graffiti Block", "Show Fewer Ads"],
				:usertypes => true,
				:allow_historical => false
			}
			@metrics[NUM_OF_FRIENDS_TOTAL] = {
				:description => "# of Friends Total",
				:header => "Friends",
				:subheaders => [],
				:usertypes => true,
				:allow_historical => false
			}
			@metrics[NUM_OF_MUTUAL_FRIENDS_TOTAL] = {
				:description => "# of Mutual Friends",
				:header => "Friends",
				:subheaders => [],
				:usertypes => true,
				:allow_historical => false
			}
			@metrics[NUM_OF_MUTUAL_FRIENDS_AVG] = {
				:description => "# of Mutual Friends (avg)",
				:header => "Friends (avg)",
				:subheaders => [],
				:usertypes => true,
				:allow_historical => false
			}
		end
		
		def self.description()
			return "User Information"
		end
		
		def subheaders(metric)
			if (metric == AGE)
				retval = Array.new
				for i in (13...70)
					retval[i] = i
				end
			elsif (metric == LOCATION)
				retval = Array.new
				locs = Locs.find(:all, :scan,
					:conditions => "collect_metrics = 'y'")
				locs.each { |loc|
					retval[loc.id] = loc.name
				}
			elsif (metric == INTERESTS)
				retval = Array.new
				interests = Interests.find(:all, :scan)
				interests.each { |interest|
					retval[interest.id] = interest.name
				}
			else
				retval = super(metric)
			end
			
			return retval
		end
		
		def populate(metrics, date, historical)
			metrics = [*metrics]
			day_from, day_to = get_date_from_to(date, 1)
			date = day_from

			if (okay_to_run?(AGE, metrics, historical))
				query = "SELECT age, COUNT(*) AS thecount FROM users
					JOIN useractivetime ON users.userid = useractivetime.userid"
				populate_types(AGE, query, date, 0, :group_col => 'age')
			end

			if (okay_to_run?(SEX, metrics, historical))
				query = "SELECT IF(sex = 'Male', 0, 1) AS sex,
					COUNT(*) AS thecount FROM users
					JOIN useractivetime ON users.userid = useractivetime.userid"
				populate_types(SEX, query, date, 0, :group_col => 'sex')
			end

			if (okay_to_run?(LOCATION, metrics, historical))
				locs = Locs.find(:all, :scan,
					:conditions => "collect_metrics = 'y'")
				locs.map! { |loc|
					loc.id
				}
				query = "SELECT loc, COUNT(*) AS thecount FROM users
					JOIN useractivetime ON users.userid = useractivetime.userid
					AND loc IN (#{locs.join(',')})"
				populate_types(LOCATION, query, date, 0, :group_col => 'loc')
			end
			
			if (okay_to_run?(INTERESTS, metrics, historical))
				query = "SELECT interestid, COUNT(*) AS thecount
					FROM userinterests
					JOIN users ON userinterests.userid = users.userid
					JOIN useractivetime ON users.userid = useractivetime.userid"
				populate_type(INTERESTS, query, date, 0, 'na',
					:group_col => 'interestid')
			end

			if (okay_to_run?(NUM_OF_USERS, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM users
					JOIN useractivetime ON users.userid = useractivetime.userid"
				populate_types(NUM_OF_USERS, query, date)
			end

			if (okay_to_run?(NUM_OF_FRIENDS, metrics, historical))
				query = "SELECT COUNT(*) AS thecount
					FROM friends"
				populate_type(NUM_OF_FRIENDS, query, date, 0, 'na')
			end

			if (okay_to_run?(NUM_OF_FRIEND_INVITES, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM emailinvites
					JOIN users ON emailinvites.userid = users.userid
					JOIN useractivetime ON users.userid = useractivetime.userid"
				populate_types(NUM_OF_FRIEND_INVITES, query, date)
			end

			if (okay_to_run?(NUM_WHO_HAVE_USED_GALLERIES, metrics, historical))
				# Number who have used galleries.  Defined as any user who
				# has uploaded a gallery pic.
				usertypes.each_index { |i|
					query = "SELECT COUNT(*) AS thecount FROM
				 	(SELECT DISTINCT g.userid FROM gallerypics g
					 JOIN users ON g.userid = users.userid
					 JOIN useractivetime ON users.userid = useractivetime.userid
					 WHERE g.created >= #{day_from} AND g.created <= #{day_to}
					 AND g.userpic = 0 AND #{usertypes_where(date, i)})
					 AS innerquery"
					populate_type(NUM_WHO_HAVE_USED_GALLERIES, query, date, 0,
					 	usertypes[i])
				}
			end

			if (okay_to_run?(NUM_WHO_HAVE_USED_BLOGS, metrics, historical))
				# Number who have used blogs.  Defined as any user who
				# has posted a blog entry or commented on a blog entry.
				# First, pull all the userids.  Later, filter by user type
				# (all, active, plus).  This is because the forum information
				# is on a different database to the user information.
				query = "SELECT COUNT(*) AS thecount
				FROM (SELECT DISTINCT userid FROM
				(SELECT userid FROM `blog`
				 WHERE `time` >= #{day_from} AND `time` <= #{day_to}
				 UNION
				 SELECT userid FROM blogcomments
				 WHERE `time` >= #{day_from} AND `time` <= #{day_to}
				) AS innerquery) AS outerquery"
				populate_type(NUM_WHO_HAVE_USED_BLOGS, query, date, 0, 'na')
			end

			if (okay_to_run?(NUM_WHO_HAVE_USED_FORUMS, metrics, historical))
				# Number who have used forums.  Defined as anyone who has
				# read a forum post or posted to a forum.
				# First, pull all the userids.  Later, filter by user type
				# (all, active, plus).  This is because the forum information
				# is on a different database to the user information.
				query = "SELECT DISTINCT(id) FROM
				(SELECT DISTINCT userid AS id FROM forumread
				 WHERE `time` >= #{day_from} AND `time` <= #{day_to}
				UNION
				 SELECT DISTINCT authorid AS id FROM forumposts
				 WHERE `time` >= #{day_from} AND `time` <= #{day_to}
				) AS innerquery"
				populate_with_userids(NUM_WHO_HAVE_USED_FORUMS, query, date, 0,
				 	:forumdb)
			end
			
			if (okay_to_run?(NUM_WITH_GENERAL_PREFERENCES, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM users
					JOIN useractivetime ON users.userid = useractivetime.userid
					WHERE showrightblocks = 'y'"
				populate_types(NUM_WITH_GENERAL_PREFERENCES, query, date, 0)
				# query = "SELECT COUNT(*) AS thecount FROM users
				# 	JOIN useractivetime ON users.userid = useractivetime.userid
				# 	WHERE showgraffiti = 'y'"
				# populate_types(NUM_WITH_GENERAL_PREFERENCES, query, date, 1)
				query = "SELECT COUNT(*) AS thecount FROM users
					JOIN useractivetime ON users.userid = useractivetime.userid
					WHERE limitads = 'y'"
				populate_types(NUM_WITH_GENERAL_PREFERENCES, query, date, 2)
			end

			if (okay_to_run?(NUM_OF_FRIENDS_TOTAL, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM friends
					JOIN users ON friends.userid = users.userid
					JOIN useractivetime ON users.userid = useractivetime.userid"
				populate_types(NUM_OF_FRIENDS_TOTAL, query, date)
			end
			
			if (okay_to_run?(NUM_OF_MUTUAL_FRIENDS_TOTAL, metrics, historical))
				# Too expensive to run here, we'll run it through the runscript,
				# metrics-mutual.rb
			end

			if (okay_to_run?(NUM_OF_MUTUAL_FRIENDS_AVG, metrics, historical))
				# Too expensive to run here, we'll run it through the runscript,
				# metrics-mutual.rb
			end

		end
		
		def format_cell(metricid, datum)
			if (metricid == NUM_OF_MUTUAL_FRIENDS_AVG)
				return '%.3f' % (datum.to_i / 1000.0)
			else
				return datum
			end
		end

	end
end
