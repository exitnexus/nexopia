lib_require :Core, 'constants'
lib_require :Metrics, 'category_all'

# Execute metrics for all dates from Timo's join date (Feb 4, 2003)
# to yesterday
start_date = Time.mktime(2003, 2, 4, 0, 0, 0, 0).to_i
end_date = Time.now.to_i - Constants::DAY_IN_SECONDS

date = start_date
while (date <= end_date)
	Metrics::CategoryAll::populate_and_perpetuate_defer({
		:date => date,
		:historical => true
	})
	date += Constants::DAY_IN_SECONDS
end

