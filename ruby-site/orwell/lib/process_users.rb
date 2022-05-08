lib_require :Orwell, 'process_users', 'register', 'inactive_user', 'plus_expiry', 'two_week', 'aniversary'

module Orwell
	# We extract a list of users and process them in chunks,
	# through various actions.
	class ProcessUsers
		def initialize(chunk_size = 128)
			@@chunk_size = chunk_size
			# For debugging, limit the maximum number of users per notification.
			# For production, set to nil.
			@@limit_users_per_notification = nil
		end

		# Start processing users.
		# We run a chunk of users through our constraints and
		# for those users who match, we execute the appropriate
		# action.  See last_active_time_60 for an example of
		# how to register constraints and actions.
		def run()
			start_id = 1
			end_id = ProcessUsers::determine_end_id
			sent_per_notification = Hash.new

			$log.info "Starting Orwell, ids #{start_id} to #{end_id}",
			 	:warning
			begin
				start_id = run_chunk(start_id, end_id, sent_per_notification)
			end until (start_id > end_id)
			$log.info "Finished Orwell run", :warning
		end # def run()
		
		
		
		#
		# This starts a run of Orwell messages
		#
		# :start_id (NOT optional) the first ID to look at
		# :end_id (optional) the last ID to look at
		# :sent_per_notification (optional) How many msgs have
		# we sent per each notification?  Used for debugging
		# to avoid spamming poor Sarah.
		# :terminate_at (optional) If we are still running at
		# this time, stop.  This prevents us running (and
		# perpetuating) for an excessive amount of time.
		def self.run_and_perpetuate(*args)
			opts = args[0] || {}

			if (!opts.has_key?(:start_id))
				raise ArgumentError.new("Did not specify :start_id")
			end

			start_id = opts[:start_id]
			end_id = opts[:end_id] || ProcessUsers::determine_end_id
			server_ids = opts[:server_ids] || Array.new
			terminate_at = opts[:terminate_at]
			if (terminate_at.nil?)
				# Default to 11 pm today
				now = Time.now
				terminate_at = Time.mktime(now.year, now.month, now.day, 23, 0, 0, 0).to_i
			end
			
			if (terminate_at < Time.now.to_i)
				$log.info "Terminating long-running Orwell, server_ids = #{server_ids.join(', ')} and start_id = #{start_id}", :critical
				return false
			end
			
			if (start_id == 1)
				$log.info "Starting Orwell, ids #{start_id} to #{end_id}", :warning
			end

			sent_per_notification = opts[:sent_per_notification] || Hash.new
			process_users = ProcessUsers.new
			
			begin
				new_start_id = process_users.run_chunk(start_id, end_id, sent_per_notification, server_ids)
				if (new_start_id <= end_id)
					# Self-perpetuate
					next_args = {
						:start_id => new_start_id,
						:end_id => end_id,
						:sent_per_notification => sent_per_notification,
						:server_ids => server_ids,
						:terminate_at => terminate_at }
					ProcessUsers::run_and_perpetuate_defer(next_args)
					return true
				else
					$log.info "Finished Orwell run, server_ids = #{server_ids.join(', ')}", :warning
				end			
			rescue DuplicateNotificationError
				# If we get a duplicate row insert we don't want to perpetuate this thread because there's
				# another thread out there processing the same chunk and is further ahead.
			end
			
			return false
		end # def run_and_perpetuate
		register_task OrwellModule, :run_and_perpetuate, :lock_time => 120
		
		# Determine the largest userid in the db
		def self.determine_end_id
			end_id = nil
			result = $site.dbs[:usersdb].query(
				"SELECT MAX(userid) AS endid FROM users")
			# May get multiple results back due to sharding.
			result.each { |row|
				id = row['endid'].to_i
				end_id = (end_id.nil? || (id > end_id)) ? id : end_id
			}
			raise "Cannot determine largest user id" if (end_id.nil?)
			return end_id
		end
		
		# Process a single chunk of users from our queue.
		# - first_id First id we are to process.
		# - last_id Last id we are to process.  Note that we will probably
		# not run to this id.
		# - sent_per_notification Used for debugging, to limit the number
		# of msgs we send.
		# Returns the next id we should process.  On our next call to
		# run_chunk we should pass that as the new first_id.
		def run_chunk(first_id, last_id, sent_per_notification, server_ids = [])
 			$log.info "processing orwell chunk first_id = #{first_id}, last_id = #{last_id}, server_ids = #{server_ids.join(', ')}", :warning
			if (first_id > last_id)
				raise ArgumentError.new("first_id > last_id")
			end
			
			user_ids = Array.new
			user_list = Array.new
			server_restriction = ""
			
			# if we don't get a list of server ids then grab a complete list from Master.
			if (server_ids.empty?)
				accounts = $site.dbs[:masterdb].query(
					"SELECT id FROM accounts
					WHERE type = #{User.typeid} AND
					id >= #{first_id} AND
					(state = #{Account::ACCOUNT_STATE_NEW} OR
					 state = #{Account::ACCOUNT_STATE_ACTIVE})
					ORDER BY id
					LIMIT #{@@chunk_size}")
			else
				accounts = $site.dbs[:masterdb].query(
					"SELECT id FROM accounts
					WHERE type = #{User.typeid} AND
					id >= #{first_id}
					AND serverid IN ? AND
					(state = #{Account::ACCOUNT_STATE_NEW} OR
					 state = #{Account::ACCOUNT_STATE_ACTIVE})
					ORDER BY id
					LIMIT #{@@chunk_size}", server_ids)
			end

			accounts.each { |row|
				user_ids << row['id'].to_i
			}
			
			unless (user_ids.empty?)
				user_list = User.find(:all, :conditions => ['userid IN #', user_ids])
			end
			
			user_list.each { |user|
				matches = ConstraintsAndActions::call_constraints(user)
				if (!@@limit_users_per_notification.nil?)
					# Limit the number of times we call_actions
					matches.map! { |match|
						if (!@@limit_users_per_notification.nil?)
							if (sent_per_notification.has_key?(match))
								sent_per_notification[match] += 1
							else
								sent_per_notification[match] = 1
							end

							if (sent_per_notification[match] <= @@limit_users_per_notification)
								match
							else
								nil
							end
						end
					}.compact!
				end
				# We could run these actions as deferred tasks.
				ConstraintsAndActions::call_actions(matches, user)
			}
			# Sleep for a while after each chunk?

			if (user_list.empty?)
				return last_id + 1
			else
				return user_list[-1].userid + 1
			end
		end
		
		# def self.test_orwell()
		# 	$log.info("Running orwell user processing", :info)
		# 	Orwell::ProcessUsers::run_and_perpetuate_defer(:start_id => 1)
		# 	$log.info("Completed user processing", :info)
		# 	return true
		# end
		
	end

end
