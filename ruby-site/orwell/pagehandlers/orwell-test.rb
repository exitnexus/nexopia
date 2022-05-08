lib_require :Core, 'constants', 'run-scripts'
lib_require :Orwell, 'process_users'

module Orwell
	class OrwellPageHandler < PageHandler
		declare_handlers("orwell") {
			area :Public 
			access_level :DebugInfo
			page	:GetRequest, :Full, :orwell_test, "test"
			page	:GetRequest, :Full, :clear_test, "cleartest"
			page	:GetRequest, :Full, :stats, "stats"
		}
		
		def stats()
			$log.info "Running Orwell stats", :warning

			now = Time.now.to_i()
			
			# Aniversary emails
			latest_join_time = now - (365 * Constants::DAY_IN_SECONDS)
			earliest_join_time = now - (372 * Constants::DAY_IN_SECONDS)

			aniversary_count = User.find(:count, :conditions => ["(jointime >= ?) AND (jointime < ?)", earliest_join_time, latest_join_time])

			# Two week emails
			latest_join_time = now - (14 * Constants::DAY_IN_SECONDS)
			earliest_join_time = now - (21 * Constants::DAY_IN_SECONDS)

			two_week_count = User.find(:count, :conditions => ["(activetime < ?) AND (jointime >= ?) AND (jointime < ?)", latest_join_time, earliest_join_time, latest_join_time])
			
			# Plus Expiry messages
			plus_count = User.find(:count, :conditions => ["(premiumexpiry < ?) AND (premiumexpiry > ?)", now - (8 * Constants::DAY_IN_SECONDS), now])
			
			# Inactive user emails
			min_active_time = now - (21 * Constants::DAY_IN_SECONDS)
			max_active_time = now - (14 * Constants::DAY_IN_SECONDS)

			inactive_count = User.find(:count, :conditions => ["(activetime > ?) AND (activetime < ?)", min_active_time, max_active_time])
			
			puts "Nexiversary Emails = #{aniversary_count}<br/>"
			puts "Two Week Emails = #{two_week_count}<br/>"
			puts "Plus Expiry Messages = #{plus_count}<br/>"
			puts "Inactive User Emails = #{inactive_count}<br/>"
			puts "Total Emails = #{aniversary_count.to_i + two_week_count.to_i + inactive_count.to_i}<br/>"
			
			$log.info "Done Orwell stats"
		end
		
		def clear_test()			
			$log.info "Running an Orwell test", :warning

			stats()

			# Delete existing  rows for that date
			$log.info "Truncating notifications_sent"
			$site.dbs[:usersdb].query("TRUNCATE notifications_sent")
			
			$log.info "Running Orwell"
			Core::RunScripts::run('orwell-ruby')
			
			puts "Done."
			$log.info "Orwell Success!"
		end

		def orwell_test()
			$log.info "Running an Orwell test", :warning

			stats()

			Core::RunScripts::run('orwell-ruby')
			
			puts "Done."
			$log.info "Orwell Success!"
		end

	end
end
