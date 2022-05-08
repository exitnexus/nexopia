lib_require :Core, 'constants'
lib_require :Metrics, 'category_user_information', 'metric_data'

start_time = Time.now.to_i

# Get start of day yesterday
d = Time.at(Time.now.to_i - Constants::DAY_IN_SECONDS)
yesterday = Time.mktime(d.year, d.month, d.day, 0, 0, 0, 0).to_i
month_ago = yesterday - Constants::DAY_IN_SECONDS * 30

# Get list of users to retrieve
res = $site.dbs[:masterdb].query(
	"SELECT id FROM accounts
	WHERE type = #{User.typeid} AND
	(state = #{Account::ACCOUNT_STATE_NEW} OR
	 state = #{Account::ACCOUNT_STATE_ACTIVE})")
accounts = Array.new
res.each { |row|
	accounts << row['id'].to_i
}
res = nil # For garbage collection
accounts.sort!

all_accounts = 0
all_mutual_friends = 0
plus_accounts = 0
plus_mutual_friends = 0
active_accounts = 0
active_mutual_friends = 0

# Retrieve each user and figure out the number of mutual friends
accounts.each { |userid|
	user = User::get_by_id(userid)
	demand(user.friends_ids)
	demand(user.friends_of_ids)
	unless (user.friends_ids.empty?)
		# Swap order around so we can later perform an intersect
		friends_of = user.friends_of_ids.map { |friends_of|
			friends_of[0], friends_of[1] = friends_of[1], friends_of[0]
		}
		mutual_count = (user.friends_ids & friends_of).length

		all_accounts += 1
		all_mutual_friends += mutual_count
		if (user.premiumexpiry >= yesterday)
			plus_accounts += 1
			plus_mutual_friends += mutual_count
		end
		if (user.activetime >= month_ago)
			active_accounts += 1
			active_mutual_friends += mutual_count
		end

		if ((all_accounts % 1000) == 0)
			puts "Processed #{all_accounts} accounts, on userid #{user.userid}"
		end
	end
}
accounts = nil # For garbage collection

# Store the results
m = Metrics::MetricData.new()
m.categoryid = Metrics::CategoryUserInformation::typeid
m.metric = Metrics::CategoryUserInformation::NUM_OF_MUTUAL_FRIENDS_TOTAL
m.usertype = 'all'
m.col = 0
m.date = yesterday
m.value = all_mutual_friends
m.store(:ignore)

m = Metrics::MetricData.new()
m.categoryid = Metrics::CategoryUserInformation::typeid
m.metric = Metrics::CategoryUserInformation::NUM_OF_MUTUAL_FRIENDS_TOTAL
m.usertype = 'plus'
m.col = 0
m.date = yesterday
m.value = plus_mutual_friends
m.store(:ignore)

m = Metrics::MetricData.new()
m.categoryid = Metrics::CategoryUserInformation::typeid
m.metric = Metrics::CategoryUserInformation::NUM_OF_MUTUAL_FRIENDS_TOTAL
m.usertype = 'active'
m.col = 0
m.date = yesterday
m.value = active_mutual_friends
m.store(:ignore)


m = Metrics::MetricData.new()
m.categoryid = Metrics::CategoryUserInformation::typeid
m.metric = Metrics::CategoryUserInformation::NUM_OF_MUTUAL_FRIENDS_AVG
m.usertype = 'all'
m.col = 0
m.date = yesterday
m.value = all_mutual_friends * 1000.0 / all_accounts
m.store(:ignore)

m = Metrics::MetricData.new()
m.categoryid = Metrics::CategoryUserInformation::typeid
m.metric = Metrics::CategoryUserInformation::NUM_OF_MUTUAL_FRIENDS_AVG
m.usertype = 'plus'
m.col = 0
m.date = yesterday
m.value = plus_mutual_friends * 1000.0 / plus_accounts
m.store(:ignore)

m = Metrics::MetricData.new()
m.categoryid = Metrics::CategoryUserInformation::typeid
m.metric = Metrics::CategoryUserInformation::NUM_OF_MUTUAL_FRIENDS_AVG
m.usertype = 'active'
m.col = 0
m.date = yesterday
m.value = active_mutual_friends * 1000.0 / active_accounts
m.store(:ignore)

puts "Processing #{all_accounts} accounts took #{Time.now.to_i - start_time} seconds"