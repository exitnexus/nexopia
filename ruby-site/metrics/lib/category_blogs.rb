lib_require :Core, 'typeid'
lib_require :Metrics, 'metric_category'

module Metrics
	class CategoryBlogs < MetricCategory
		extend TypeID
		
		metric_category
		
		BLOG_POSTS_CREATED           = 14
		BLOG_POSTS_TOTAL             = 15
		BLOG_POSTS_PRIVATE           = 16
		BLOG_POSTS_FRIENDS           = 17
		BLOG_POSTS_FOF               = 18
		BLOG_POSTS_LOGGED_IN         = 19
		BLOG_POSTS_PUBLIC            = 20
		COMMENTS_ON_BLOG_POST        = 21
		USERS_WITH_BLOG_POST_DAY     = 9
		USERS_WITH_BLOG_POST_WEEK    = 10
		USERS_WITH_BLOG_POST_MONTH   = 11
		NUM_BLOG_POSTS_ALLOW_CMTS    = 12
		NUM_USERS_HIDING_BLOG_HITS   = 13
		BLOG_POSTS_BY_TYPE           = 22
		VOTES_ON_BATTLES             = 23
		VOTES_ON_POLLS               = 24
		YOUTUBE_SEARCHES             = 25
		UPLOADING_THROUGH_UPLOADER   = 26
		COMMENTS_BY_TYPE             = 27
		BATTLES_BY_TYPE              = 28
		BLOG_POLLS                   = 29
		ABUSE_REPORTS                = 30

		def initialize()
			super()
			
			@metrics[BLOG_POSTS_CREATED] = {
				:description => "# of Blog Posts Created",
				:header => "Blog posts",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => true
			}
			@metrics[BLOG_POSTS_TOTAL] = {
				:description => "# of Blog Posts Total",
				:header => "Blog posts",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => false
			}
			@metrics[BLOG_POSTS_PRIVATE] = {
				:description => "# of Blog Posts Private",
				:header => "Blog posts",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => true
			}
			@metrics[BLOG_POSTS_FRIENDS] = {
				:description => "# of Blog Posts Friends-only",
				:header => "Blog posts",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => true
			}
			@metrics[BLOG_POSTS_FOF] = {
				:description => "# of Blog Posts Friends-of-Friends",
				:header => "Blog posts",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => true
			}
			@metrics[BLOG_POSTS_LOGGED_IN] = {
				:description => "# of Blog Posts Logged-In",
				:header => "Blog posts",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => true
			}
			@metrics[BLOG_POSTS_PUBLIC] = {
				:description => "# of Blog Posts Public",
				:header => "Blog posts",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => true
			}
			@metrics[COMMENTS_ON_BLOG_POST] = {
				:description => "# of Comments on Blog Posts",
				:header => "Comments",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => false
			}
			@metrics[USERS_WITH_BLOG_POST_DAY] = {
				:description => "# of Users with a Blog Post in Last Day",
				:header => "Blog posts",
				:subheaders => [],
				:usertypes => true,
				:allow_historical => true
			}
			@metrics[USERS_WITH_BLOG_POST_WEEK] = {
				:description => "# of Users with a Blog Post in Last Week",
				:header => "Blog posts",
				:subheaders => [],
				:usertypes => true,
				:allow_historical => true
			}
			@metrics[USERS_WITH_BLOG_POST_MONTH] = {
				:description => "# of Users with a Blog Post in Last Month",
				:header => "Blog posts",
				:subheaders => [],
				:usertypes => true,
				:allow_historical => true
			}
			@metrics[NUM_BLOG_POSTS_ALLOW_CMTS] = {
				:description => "# of Blog Posts Allowing Comments",
				:header => "Blog posts",
				:subheaders => ['Allow', 'Disallow'],
				:usertypes => false,
				:allow_historical => false
			}
			@metrics[NUM_USERS_HIDING_BLOG_HITS] = {
				:description => "# of Users Who Hide Blog Hits",
				:header => "Users",
				:subheaders => ['hide', 'show'],
				:usertypes => false,
				:allow_historical => false
			}
			@metrics[BLOG_POSTS_BY_TYPE] = {
				:description => "# of Posts (by type)",
				:header => "Posts",
				:subheaders => ['freeform', 'photo', 'video', 'battle', 'poll'],
				:usertypes => true,
				:allow_historical => true
			}
			@metrics[VOTES_ON_BATTLES] = {
				:description => "# of Votes on Battles",
				:header => "Votes",
				:subheaders => [],
				:usertypes => true,
				:allow_historical => true
			}
			@metrics[VOTES_ON_POLLS] = {
				:description => "# of Votes on Polls",
				:header => "Polls",
				:subheaders => [],
				:usertypes => true,
				:allow_historical => true
			}
			@metrics[YOUTUBE_SEARCHES] = {
				:description => "# using YouTube Search",
				:header => "Searches",
				:subheaders => [],
				:usertypes => true,
				:allow_historical => false
			}
			@metrics[UPLOADING_THROUGH_UPLOADER] = {
				:description => "# Uploading Through Blog Uploader",
				:header => "Uploads",
				:subheaders => [],
				:usertypes => true,
				:allow_historical => false
			}
			@metrics[COMMENTS_BY_TYPE] = {
				:description => "# of Comments (by type)",
				:header => "Comments",
				:subheaders => ['freeform', 'photo', 'video', 'battle', 'poll'],
				:usertypes => true,
				:allow_historical => true
			}
			@metrics[BATTLES_BY_TYPE] = {
				:description => "# of Battle Blogs",
				:header => "Battles",
				:subheaders => ['photo', 'video'],
				:usertypes => true,
				:allow_historical => true
			}
			@metrics[BLOG_POLLS] = {
				:description => "# of Poll Blogs",
				:header => "Polls",
				:subheaders => ['photo', 'no photo'],
				:usertypes => true,
				:allow_historical => true
			}
			@metrics[ABUSE_REPORTS] = {
				:description => "Abuse Reports",
				:header => "Abuse reports",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => true
			}
		end
		
		def self.description()
			return "Blogs"
		end
		
		def populate(metrics, date, historical)
			metrics = [*metrics]
			day_from, day_to = get_date_from_to(date, 1)
			date = day_from
			
			if (okay_to_run?(BLOG_POSTS_CREATED, metrics, historical))
				query = "SELECT COUNT(*) AS thecount
					FROM blog b
					WHERE b.`time` >= #{day_from} AND b.`time` <= #{day_to}"
				populate_type(BLOG_POSTS_CREATED, query, date, 0, 'na')
			end

			if (okay_to_run?(BLOG_POSTS_TOTAL, metrics, historical))
				query = "SELECT COUNT(*) AS thecount
					FROM blog b"
				populate_type(BLOG_POSTS_TOTAL, query, date, 0, 'na')
			end
			
			if (okay_to_run?(BLOG_POSTS_PRIVATE, metrics, historical))
				query = "SELECT COUNT(*) AS thecount
					FROM blog b
					WHERE b.`time` >= #{day_from} AND b.`time` <= #{day_to}
					AND b.visibility = 0"
				populate_type(BLOG_POSTS_PRIVATE, query, date, 0, 'na')
			end

			if (okay_to_run?(BLOG_POSTS_FRIENDS, metrics, historical))
				query = "SELECT COUNT(*) AS thecount
					FROM blog b
					WHERE b.`time` >= #{day_from} AND b.`time` <= #{day_to}
					AND b.visibility = 1"
				populate_type(BLOG_POSTS_FRIENDS, query, date, 0, 'na')
			end

			if (okay_to_run?(BLOG_POSTS_FOF, metrics, historical))
				query = "SELECT COUNT(*) AS thecount
					FROM blog b
					WHERE b.`time` >= #{day_from} AND b.`time` <= #{day_to}
					AND b.visibility = 2"
				populate_type(BLOG_POSTS_FOF, query, date, 0, 'na')
			end

			if (okay_to_run?(BLOG_POSTS_LOGGED_IN, metrics, historical))
				query = "SELECT COUNT(*) AS thecount
					FROM blog b
					WHERE b.`time` >= #{day_from} AND b.`time` <= #{day_to}
					AND b.visibility = 3"
				populate_type(BLOG_POSTS_LOGGED_IN, query, date, 0, 'na')
			end

			if (okay_to_run?(BLOG_POSTS_PUBLIC, metrics, historical))
				query = "SELECT COUNT(*) AS thecount
					FROM blog b
					WHERE b.`time` >= #{day_from} AND b.`time` <= #{day_to}
					AND b.visibility = 4"
				populate_type(BLOG_POSTS_PUBLIC, query, date, 0, 'na')
			end
			
			if (okay_to_run?(COMMENTS_ON_BLOG_POST, metrics, historical))
				query = "SELECT COUNT(*) AS thecount
					FROM blogcomments bc"
				populate_type(COMMENTS_ON_BLOG_POST, query, date, 0, 'na')
			end
			
			if (okay_to_run?(USERS_WITH_BLOG_POST_DAY, metrics, historical))
				usertypes.each_index { |i|
					query = "SELECT COUNT(*) AS thecount FROM 
						(SELECT DISTINCT blog.userid FROM blog
				 		JOIN users ON blog.userid = users.userid
						JOIN useractivetime
						ON users.userid = useractivetime.userid
				 		WHERE blog.`time` >= #{day_from}
				 		AND blog.`time` <= #{day_to}
				 		AND #{usertypes_where(date, i)}
						) AS innerquery"
					populate_type(USERS_WITH_BLOG_POST_DAY, query, date, 0,
					 	usertypes[i])
				}
			end

			if (okay_to_run?(USERS_WITH_BLOG_POST_WEEK, metrics, historical))
				week_from, week_to = get_date_from_to(date, 7)
				usertypes.each_index { |i|
					query = "SELECT COUNT(*) AS thecount FROM 
						(SELECT DISTINCT blog.userid FROM blog
					 	JOIN users ON blog.userid = users.userid
						JOIN useractivetime
						ON users.userid = useractivetime.userid
					 	WHERE blog.`time` >= #{week_from}
					 	AND blog.`time` <= #{week_to}
					 	AND #{usertypes_where(date, i)}
						) AS innerquery"
					populate_type(USERS_WITH_BLOG_POST_WEEK, query, date, 0,
					 	usertypes[i])
				}
			end
			
			if (okay_to_run?(USERS_WITH_BLOG_POST_MONTH, metrics, historical))
				month_from, month_to = get_date_from_to(date, 30)
				usertypes.each_index { |i|
					query = "SELECT COUNT(*) AS thecount FROM 
						(SELECT DISTINCT blog.userid FROM blog
					 	JOIN users ON blog.userid = users.userid
						JOIN useractivetime
						ON users.userid = useractivetime.userid
					 	WHERE blog.`time` >= #{month_from}
					 	AND blog.`time` <= #{month_to}
					 	AND #{usertypes_where(date, i)}
						) AS innerquery"
					populate_type(USERS_WITH_BLOG_POST_MONTH, query, date, 0,
					 	usertypes[i])
				}
			end

			if (okay_to_run?(NUM_BLOG_POSTS_ALLOW_CMTS, metrics, historical))
				query = "SELECT IF(allowcomments = 'y', 0, 1) AS allowcomments,
					COUNT(*) AS thecount FROM blog"
				populate_type(NUM_BLOG_POSTS_ALLOW_CMTS, query, date, 0, 'na',
					:group_col => 'allowcomments')
			end

			if (okay_to_run?(NUM_USERS_HIDING_BLOG_HITS, metrics, historical))
				query = "SELECT IF(showhits = 'y', 1, 0) AS showhits,
					COUNT(*) AS thecount FROM blogprofile"
				populate_type(NUM_USERS_HIDING_BLOG_HITS, query, date, 0, 'na',
					:group_col => 'showhits')
			end

			if (okay_to_run?(BLOG_POSTS_BY_TYPE, metrics, historical))
				query = "SELECT 
				CASE blog.`typeid`
				WHEN 0 THEN 0
				WHEN #{Blogs::PhotoBlog::typeid} THEN 1
				WHEN #{Blogs::VideoBlog::typeid} THEN 2
				WHEN #{Blogs::BattleBlog::typeid} THEN 3
				WHEN #{Blogs::PollBlog::typeid} THEN 4
				ELSE 0
				END AS typeid,
				COUNT(*) AS thecount FROM blog
				JOIN useractivetime ON blog.userid = useractivetime.userid
				JOIN users ON blog.userid = users.userid
				WHERE blog.`time` >= #{day_from} AND blog.`time` <= #{day_to}"
				populate_types(BLOG_POSTS_BY_TYPE, query, date, 0,
					:group_col => 'typeid')
			end

			if (okay_to_run?(VOTES_ON_BATTLES, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM userpollvotes
				JOIN useractivetime
				ON userpollvotes.userid = useractivetime.userid
				JOIN users ON userpollvotes.userid = users.userid
				WHERE typeid = #{Blogs::BattleBlog::typeid} AND
				userpollvotes.`time` >= #{day_from} AND
				userpollvotes.`time` <= #{day_to}"
				populate_types(VOTES_ON_BATTLES, query, date, 0)
			end

			if (okay_to_run?(VOTES_ON_POLLS, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM userpollvotes
				JOIN useractivetime
				ON userpollvotes.userid = useractivetime.userid
				JOIN users ON userpollvotes.userid = users.userid
				WHERE typeid = #{Blogs::PollBlog::typeid} AND
				userpollvotes.`time` >= #{day_from} AND
				userpollvotes.`time` <= #{day_to}"
				populate_types(VOTES_ON_POLLS, query, date, 0)
			end

			if (okay_to_run?(YOUTUBE_SEARCHES, metrics, historical))
				# This is actually updated by the youtube search
				# code itself
			end

			if (okay_to_run?(UPLOADING_THROUGH_UPLOADER, metrics, historical))
				# This is actually updated by the youtube search
				# code itself
			end

			if (okay_to_run?(COMMENTS_BY_TYPE, metrics, historical))
				query = "SELECT
				CASE blog.`typeid`
				WHEN 0 THEN 0
				WHEN #{Blogs::PhotoBlog::typeid} THEN 1
				WHEN #{Blogs::VideoBlog::typeid} THEN 2
				WHEN #{Blogs::BattleBlog::typeid} THEN 3
				WHEN #{Blogs::PollBlog::typeid} THEN 4
				ELSE 0
				END AS typeid,
				COUNT(*) AS thecount FROM blogcomments
				JOIN blog ON blog.userid = blogcomments.bloguserid
				AND blog.id = blogcomments.blogid
				JOIN useractivetime ON blog.userid = useractivetime.userid
				JOIN users ON blog.userid = users.userid
				WHERE blogcomments.`time` >= #{day_from} AND
				blogcomments.`time` <= #{day_to}"
				populate_types(COMMENTS_BY_TYPE, query, date, 0,
					:group_col => 'typeid')
			end

			if (okay_to_run?(BATTLES_BY_TYPE, metrics, historical))
				query = "SELECT IF(battletype = 'photo', 0, 1) AS battletype,
				COUNT(*) AS thecount FROM blogtype_battle
				JOIN blog ON blogtype_battle.userid = blog.userid
				AND blogtype_battle.blogid = blog.id
				JOIN useractivetime ON blog.userid = useractivetime.userid
				JOIN users ON blog.userid = users.userid
				WHERE blog.`time` >= #{day_from} AND blog.`time` <= #{day_to}"
				populate_types(BATTLES_BY_TYPE, query, date, 0,
					:group_col => 'battletype')
			end

			if (okay_to_run?(BLOG_POLLS, metrics, historical))
				query = "SELECT IF(link != '', 0, 1) AS link,
				COUNT(*) AS thecount FROM blogtype_photo
				JOIN blog ON blogtype_photo.userid = blog.userid
				AND blogtype_photo.blogid = blog.id
				JOIN useractivetime ON blog.userid = useractivetime.userid
				JOIN users ON blog.userid = users.userid
				WHERE blog.`time` >= #{day_from} AND blog.`time` <= #{day_to}"
				populate_types(BLOG_POLLS, query, date, 0,
					:group_col => 'link')
			end

			if (okay_to_run?(ABUSE_REPORTS, metrics, historical))
				query = "SELECT COUNT(*) AS thecount
				FROM abuse
				WHERE `type` = #{Blogs::BlogPost::typeid}
				AND `time` >= #{day_from} AND `time` <= #{day_to}"
				populate_type(ABUSE_REPORTS, query, date, 0, 'na',
					:db => :db)
			end

		end
		
	end
end
