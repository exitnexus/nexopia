lib_require :Core, 'typeid'
lib_require :Metrics, 'metric_category'

module Metrics
	class CategoryGeneral < MetricCategory
		extend TypeID
		
		metric_category
		
		ARTICLES                     = 1
		SITE_POLLS                   = 2
		
		def initialize()
			super()
			
			@metrics[ARTICLES] = {
				:description => "Articles...",
				:header => "Articles",
				:subheaders => ["Submissions", "Comments"],
				:usertypes => false,
				:allow_historical => true
			}
			@metrics[SITE_POLLS] = {
				:description => "Site Polls...",
				:header => "Site Polls",
				:subheaders => ["Submissions", "Comments"],
				:usertypes => false,
				:allow_historical => true
			}
		end
		
		def self.description()
			return "General"
		end
		
		def populate(metrics, date, historical)
			metrics = [*metrics]
			day_from, day_to = get_date_from_to(date, 1)
			date = day_from
			
			if (okay_to_run?(ARTICLES, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM articles
					WHERE submittime >= #{day_from} AND submittime <= #{day_to}"
				populate_type(ARTICLES, query, date, 0, 'na',
					:db => :articlesdb)

				query = "SELECT COUNT(*) AS thecount FROM comments
					WHERE `time` >= #{day_from} AND `time` <= #{day_to}"
				populate_type(ARTICLES, query, date, 1, 'na',
					:db => :articlesdb)
			end
			
			if (okay_to_run?(SITE_POLLS, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM polls
					WHERE `date` >= #{day_from} AND `date` <= #{day_to}"
				populate_type(SITE_POLLS, query, date, 0, 'na', :db => :polldb)
			
				query = "SELECT COUNT(*) AS thecount FROM pollcomments
					WHERE `time` >= #{day_from} AND `time` <= #{day_to}"
				populate_type(SITE_POLLS, query, date, 1, 'na',
					:db => :polldb)
			end
		end
		
	end
end
