lib_require :Core, 'users/locs', 'users/interests', 'constants', 'typeid'
lib_require :Metrics, 'metric_category'

module Metrics
	class CategoryUserSignUp < MetricCategory
		extend TypeID
		
		metric_category
		
		NEW_USERS                    = 1
		NEW_USER_AGE                 = 2
		NEW_USER_LOCATION            = 3
		NEW_USER_SEX                 = 4
		ACTIVE_USERS                 = 5
		SIGN_PROCESS_STEP			 			 = 6
		SIGN_UP_POINT_OF_ENTRY			 = 7
		
		def initialize()
			super()
			
			@metrics[NEW_USERS] = {
				:description => "# of New Users",
				:header => "Users",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => true
			}
			@metrics[NEW_USER_AGE] = {
				:description => "# of New Users by Age",
				:header => "Users",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => true
			}
			@metrics[NEW_USER_LOCATION] = {
				:description => "# of New Users by Location",
				:header => "Users",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => true
			}
			@metrics[NEW_USER_SEX] = {
				:description => "# of New Users by Sex",
				:header => "Users",
				:subheaders => ["Male", "Female"],
				:usertypes => false,
				:allow_historical => true
			}
			@metrics[ACTIVE_USERS] = {
				:description => "# of Active Users Who Signed Up In Past...",
				:header => "Users",
				:subheaders => ["Month", "3 Months", "6 Months",
					"Year", "2 Years", "3 Years", "4 Years", "5 Years"],
				:usertypes => false,
				:allow_historical => true
			}	
			@metrics[SIGN_PROCESS_STEP] = {
				:description => "# of Users Who Completed Join Step",
				:header => "Users",
				:subheaders => ["Basic Signup", "Real Name", "Activation"],
				:usertypes => false,
				:allow_historical => false
			}
			@metrics[SIGN_UP_POINT_OF_ENTRY] = {
				:description => "# of Users Who Joined Through Method",
				:header => "Users",
				:subheaders => ["Manual", "Email", "Redirect"],
				:usertypes => false,
				:allow_historical => false
			}
		end
		
		def self.description()
			return "User Sign Up"
		end
		
		def subheaders(metric)
			if (metric == NEW_USER_AGE)
				retval = Array.new
				for i in (13...70)
					retval[i] = i
				end
			elsif (metric == NEW_USER_LOCATION)
				retval = Array.new
				locs = Locs.find(:all, :scan,
					:conditions => "collect_metrics = 'y'")
				locs.each { |loc|
					retval[loc.id] = loc.name
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
			
			if (okay_to_run?(NEW_USERS, metrics, historical))
				query = "SELECT COUNT(*) AS thecount FROM users
					WHERE jointime >= #{day_from} AND jointime <= #{day_to}"
				populate_type(NEW_USERS, query, date, 0, 'na')
			end
			
			if (okay_to_run?(NEW_USER_AGE, metrics, historical))
				query = "SELECT age, COUNT(*) AS thecount FROM users
					WHERE jointime >= #{day_from} AND jointime <= #{day_to}"
				populate_type(NEW_USER_AGE, query, date, 0, 'na',
					:group_col => 'age')
			end
			
			if (okay_to_run?(NEW_USER_LOCATION, metrics, historical))
				locs = Locs.find(:all, :scan,
					:conditions => "collect_metrics = 'y'")
				locs.map! { |loc|
					loc.id
				}
				query = "SELECT loc, COUNT(*) AS thecount FROM users
					WHERE jointime >= #{day_from} AND jointime <= #{day_to} AND
					loc IN (#{locs.join(',')})"
				populate_type(NEW_USER_LOCATION, query, date, 0, 'na',
					:group_col => 'loc')
			end
			
			if (okay_to_run?(NEW_USER_SEX, metrics, historical))
				query = "SELECT IF(sex = 'Male', 0, 1) AS sex,
					COUNT(*) AS thecount FROM users
					WHERE jointime >= #{day_from} AND jointime <= #{day_to}"
				populate_type(NEW_USER_SEX, query, date, 0, 'na',
					:group_col => 'sex')
			end

			if (okay_to_run?(ACTIVE_USERS, metrics, historical))
				jointimes = Array.new()
				jointimes << day_from - Constants::MONTH_IN_SECONDS
				jointimes << day_from - Constants::MONTH_IN_SECONDS * 3
				jointimes << day_from - Constants::YEAR_IN_SECONDS / 2
				jointimes << day_from - Constants::YEAR_IN_SECONDS
				jointimes << day_from - Constants::YEAR_IN_SECONDS * 2
				jointimes << day_from - Constants::YEAR_IN_SECONDS * 3
				jointimes << day_from - Constants::YEAR_IN_SECONDS * 4
				jointimes << day_from - Constants::YEAR_IN_SECONDS * 5
				jointimes.each_index { |i|
					query = "SELECT COUNT(*) AS thecount FROM users
						JOIN useractivetime
						ON users.userid = useractivetime.userid
						WHERE #{usertypes_where(date, 1)} AND 
						jointime >= #{jointimes[i]} AND jointime <= #{day_to}"
					populate_type(ACTIVE_USERS, query, date, i, 'na')
				}
			end
		end
		
	end
end
