lib_require :Core, 'typeid'
lib_require :Metrics, 'metric_category'

module Metrics
	class CategoryFriends < MetricCategory
		extend TypeID
		
		metric_category
		
		FRIEND_FINDER_NUM_INVITES_SENT			= 1
		FRIEND_FINDER_NUM_FRIENDS_ADDED			= 2
		ANONYMOUS_FRIEND_FINDER_SEARCHES		= 3
		ANONYMOUS_FRIEND_FINDER_RESULTS			= 4
		FRIEND_FINDER_SEARCHES_PERFORMED		= 5
		
		def initialize()
			super()
			
			@metrics[FRIEND_FINDER_NUM_INVITES_SENT] = {
				:description => "# of Invites Sent via Friend Finder",
				:header => "Users",
				:subheaders => ['new', 'existing'],
				:usertypes => false,
				:allow_historical => false
			}
			@metrics[FRIEND_FINDER_NUM_FRIENDS_ADDED] = {
				:description => "# of Friends Added by Friend Finder",
				:header => "Users",
				:subheaders => ['new', 'existing'],
				:usertypes => false,
				:allow_historical => false
			}
			@metrics[ANONYMOUS_FRIEND_FINDER_SEARCHES] = {
					:description => "# of Anonymous Friend Finder Searches",
					:header => "Users",
					:subheaders => ['count'],
					:usertypes => false,
					:allow_historical => false
			}
			@metrics[ANONYMOUS_FRIEND_FINDER_RESULTS] = {
					:description => "# of Results for Anonymous Friend Finder Searches",
					:header => "Users",
					:subheaders => ['count'],
					:usertypes => false,
					:allow_historical => false
			}
			@metrics[FRIEND_FINDER_SEARCHES_PERFORMED] = {
				:description => "# of Searches Performed By Friend Finder",
				:header => "Users",
				:subheaders => ['new', 'existing'],
				:usertypes => false,
				:allow_historical => false
			}
		end
		
		def self.description()
			return "Friends"
		end
		
		def populate(metrics, date, historical)
			metrics = [*metrics]
			day_from, day_to = get_date_from_to(date, 1)
			date = day_from
			
		end		
	end
end
