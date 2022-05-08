lib_require :Core, 'typeid'
lib_require :Metrics, 'metric_category'

module Metrics
	class CategoryGalleries < MetricCategory
		extend TypeID
		
		metric_category
		
		PIC_UPLOADS                  = 8
		PROFILE_PICS_DESIGNATED      = 9
		NUM_OF_GALLERIES_CREATED     = 10
		NUM_OF_GALLERIES_TOTAL       = 11
		NUM_OF_GALLERIES_PRIVACY     = 12
		COMMENTS_ON_PICTURES         = 13
		NUM_GALLERIES_ALLOW_COMMENTS = 7
		
		def initialize()
			super()
			
			@metrics[PIC_UPLOADS] = {
				:description => "# of Gallery Pictures Uploaded",
				:header => "Pics",
				:subheaders => ['Total', 'To new gallery', 'To existing gallery'],
				:usertypes => false,
				:allow_historical => true
			}
			@metrics[PROFILE_PICS_DESIGNATED] = {
				:description => "# of Profile Pictures Designated",
				:header => "Pics",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => true
			}
			@metrics[NUM_OF_GALLERIES_CREATED] = {
				:description => "# of Galleries Created",
				:header => "Galleries",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => true
			}
			@metrics[NUM_OF_GALLERIES_TOTAL] = {
				:description => "# of Galleries Total",
				:header => "Galleries",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => false
			}
			@metrics[NUM_OF_GALLERIES_PRIVACY] = {
				:description => "# of Galleries by Privacy",
				:header => "Galleries",
				:subheaders => ['Anyone', 'Logged in', 'Friends'],
				:usertypes => false,
				:allow_historical => false
			}
			@metrics[COMMENTS_ON_PICTURES] = {
				:description => "# of Comments on Pictures",
				:header => "Comments",
				:subheaders => ["Total", "Profile pics", "Gallery pics"],
				:usertypes => false,
				:allow_historical => true
			}
			@metrics[NUM_GALLERIES_ALLOW_COMMENTS] = {
				:description => "# of Galleries with Allow Comments",
				:header => "Galleries",
				:subheaders => ['allow', 'disallow'],
				:usertypes => true,
				:allow_historical => false
			}
		end
		
		def self.description()
			return "Galleries"
		end
		
		def populate(metrics, date, historical)
			metrics = [*metrics]
			day_from, day_to = get_date_from_to(date, 1)
			date = day_from
			
			if (okay_to_run?(PIC_UPLOADS, metrics, historical))
				# Total
				query = "SELECT COUNT(*) AS thecount
					FROM gallerypics gp
					WHERE gp.created >= #{day_from}
					AND gp.created <= #{day_to}"
				populate_type(PIC_UPLOADS, query, date, 0, 'na')
				# To new gallery
				query = "SELECT COUNT(*) AS thecount
					FROM gallerypics gp
					JOIN gallery g ON gp.userid = g.ownerid
					AND gp.galleryid = g.id
					WHERE gp.created >= #{day_from}
					AND gp.created <= #{day_to}
					AND g.created >= #{day_from} AND g.created <= #{day_to}"
				populate_type(PIC_UPLOADS, query, date, 1, 'na')
				# To existing gallery
				query = "SELECT COUNT(*) AS thecount
					FROM gallerypics gp
					JOIN gallery g ON gp.userid = g.ownerid
					AND gp.galleryid = g.id
					WHERE gp.created >= #{day_from}
					AND gp.created <= #{day_to}
					AND g.created < #{day_from}"
				populate_type(PIC_UPLOADS, query, date, 2, 'na')
			end
			
			if (okay_to_run?(PROFILE_PICS_DESIGNATED, metrics, historical))
				query = "SELECT COUNT(*) AS thecount
					FROM pics p JOIN gallerypics gp
					ON p.userid = gp.userid AND p.gallerypicid = gp.id
					WHERE gp.created <= #{day_to}"
				populate_type(PROFILE_PICS_DESIGNATED, query, date, 0, 'na')
			end
			
			if (okay_to_run?(NUM_OF_GALLERIES_CREATED, metrics, historical))
				query = "SELECT COUNT(*) AS thecount
					FROM gallery g
					WHERE g.created >= #{day_from}
					AND g.created <= #{day_to}"
				populate_type(NUM_OF_GALLERIES_CREATED, query, date, 0, 'na')
			end

			if (okay_to_run?(NUM_OF_GALLERIES_TOTAL, metrics, historical))
				query = "SELECT COUNT(*) AS thecount
					FROM gallery g
					WHERE g.created <= #{day_to}"
				populate_type(NUM_OF_GALLERIES_TOTAL, query, date, 0, 'na')
			end
			
			if (okay_to_run?(NUM_OF_GALLERIES_PRIVACY, metrics, historical))
				# 'anyone'
				query = "SELECT COUNT(*) AS thecount
					FROM gallery g
					WHERE g.created <= #{day_to} AND g.permission = 'anyone'"
				populate_type(NUM_OF_GALLERIES_PRIVACY, query, date, 0, 'na')
				# 'logged in'
				query = "SELECT COUNT(*) AS thecount
					FROM gallery g
					WHERE g.created <= #{day_to}
					AND g.permission = 'loggedin'"
				populate_type(NUM_OF_GALLERIES_PRIVACY, query, date, 1, 'na')
				# 'friends'
				query = "SELECT COUNT(*) AS thecount
					FROM gallery g
					WHERE g.created <= #{day_to}
					AND g.permission = 'friends'"
				populate_type(NUM_OF_GALLERIES_PRIVACY, query, date, 2, 'na')
			end
			
			if (okay_to_run?(COMMENTS_ON_PICTURES, metrics, historical))
				query = "SELECT COUNT(*) AS thecount
					FROM gallerycomments gc
					WHERE gc.`time` >= #{day_from}
					AND gc.`time` <= #{day_to}"
				populate_type(COMMENTS_ON_PICTURES, query, date, 0, 'na')
				# Profile pics
				query = "SELECT COUNT(*) AS thecount
					FROM gallerycomments gc
					WHERE gc.`time` >= #{day_from}
					AND gc.`time` <= #{day_to}
					AND EXISTS
					(SELECT * FROM pics p WHERE
					 p.userid = gc.userid AND p.gallerypicid = gc.picid)"
				populate_type(COMMENTS_ON_PICTURES, query, date, 1, 'na')
				# Gallery pics
				query = "SELECT COUNT(*) AS thecount
					FROM gallerycomments gc
					WHERE gc.`time` >= #{day_from}
					AND gc.`time` <= #{day_to}
					AND NOT EXISTS
					(SELECT * FROM pics p WHERE
					 p.userid = gc.userid AND p.gallerypicid = gc.picid)"
				populate_type(COMMENTS_ON_PICTURES, query, date, 2, 'na')
			end
			
			if (okay_to_run?(NUM_GALLERIES_ALLOW_COMMENTS, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM gallery
					JOIN users ON gallery.ownerid = users.userid
					JOIN useractivetime ON users.userid = useractivetime.userid
					WHERE allowcomments = 'y'"
				populate_types(NUM_GALLERIES_ALLOW_COMMENTS, query, date, 0)
				query = "SELECT COUNT(*) AS thecount FROM gallery
					JOIN users ON gallery.ownerid = users.userid
					JOIN useractivetime ON users.userid = useractivetime.userid
					WHERE allowcomments = 'n'"
				populate_types(NUM_GALLERIES_ALLOW_COMMENTS, query, date, 1)
			end
		end
		
	end
end
