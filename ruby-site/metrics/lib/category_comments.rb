lib_require :Core, 'typeid'
lib_require :Metrics, 'metric_category'

module Metrics
	class CategoryComments < MetricCategory
		extend TypeID
		
		metric_category
		
		COMMENTS                     = 3
		NUM_WITH_PREFERENCES         = 2
		
		def initialize()
			super()
			
			@metrics[COMMENTS] = {
				:description => "# of Comments Posted",
				:header => "Comments",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => true
			}
			@metrics[NUM_WITH_PREFERENCES] = {
				:description => "# With Preferences...",
				:header => "Users",
				:subheaders => ["Allow comments", "Ignore comments outside of age range", "Only accept comments from friends"],
				:usertypes => true,
				:allow_historical => false
			}
		end
		
		def self.description()
			return "Comments"
		end
		
		def populate(metrics, date, historical)
			metrics = [*metrics]
			day_from, day_to = get_date_from_to(date, 1)
			date = day_from
			
			if (okay_to_run?(COMMENTS, metrics, historical))
				query = "SELECT COUNT(*) AS thecount
					FROM usercomments uc
					WHERE uc.`time` >= #{day_from}
					AND uc.`time` <= #{day_to}"
				populate_type(COMMENTS, query, date, 0, 'na')
			end
			
			if (okay_to_run?(NUM_WITH_PREFERENCES, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM users
					JOIN useractivetime ON users.userid = useractivetime.userid
					WHERE enablecomments = 'y'"
				populate_types(NUM_WITH_PREFERENCES, query, date, 0)
				query = "SELECT COUNT(*) AS thecount FROM users
					JOIN useractivetime ON users.userid = useractivetime.userid
					WHERE ignorebyage = 'comments' or ignorebyage = 'both'"
				populate_types(NUM_WITH_PREFERENCES, query, date, 1)
				query = "SELECT COUNT(*) AS thecount FROM users
					JOIN useractivetime ON users.userid = useractivetime.userid
					WHERE onlyfriends = 'comments' or onlyfriends = 'both'"
				populate_types(NUM_WITH_PREFERENCES, query, date, 2)
			end
		end
		
	end
end
