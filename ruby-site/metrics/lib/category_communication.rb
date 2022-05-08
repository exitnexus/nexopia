lib_require :Core, 'typeid'
lib_require :Metrics, 'metric_category'
lib_require :Messages, 'message_folder'

module Metrics
	class CategoryCommunication < MetricCategory
		extend TypeID
		
		metric_category
		
		MSGS_SENT                    = 1
		NUM_WITH_PREFERENCES         = 3
		
		def initialize()
			super()
			
			@metrics[MSGS_SENT] = {
				:description => "# of Messages Sent",
				:header => "Messages",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => true
			}
			@metrics[NUM_WITH_PREFERENCES] = {
				:description => "# With Preferences...",
				:header => "Users",
				:subheaders => ["Forward msgs to email", "Ignore msgs outside of age range", "Only accept msgs from friends"],
				:usertypes => true,
				:allow_historical => false
			}
		end
		
		def self.description()
			return "Communication"
		end
		
		def populate(metrics, date, historical)
			metrics = [*metrics]
			day_from, day_to = get_date_from_to(date, 1)
			date = day_from
			
			if (okay_to_run?(MSGS_SENT, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM msgs
					JOIN users on msgs.userid = users.userid
					JOIN useractivetime ON users.userid = useractivetime.userid
					WHERE msgs.folder = #{MessageFolder::SENT} AND
					msgs.`date` >= #{day_from} AND msgs.`date` <= #{day_to}"
				populate_types(MSGS_SENT, query, date, 0)
			end
			
			if (okay_to_run?(NUM_WITH_PREFERENCES, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM users
					JOIN useractivetime ON users.userid = useractivetime.userid
					WHERE fwmsgs = 'y'"
				populate_types(NUM_WITH_PREFERENCES, query, date, 0)
				query = "SELECT COUNT(*) AS thecount FROM users
					JOIN useractivetime ON users.userid = useractivetime.userid
					WHERE (ignorebyage = 'msgs' or ignorebyage = 'both')"
				populate_types(NUM_WITH_PREFERENCES, query, date, 1)
				query = "SELECT COUNT(*) AS thecount FROM users
					JOIN useractivetime ON users.userid = useractivetime.userid
					WHERE (onlyfriends = 'msgs' or onlyfriends = 'both')"
				populate_types(NUM_WITH_PREFERENCES, query, date, 2)
			end
		end
		
	end
end
