lib_require :Core, 'constants'
lib_require :Metrics, 'category_all'

# Execute metrics for yesterday.
yesterday = Time.now.to_i - Constants::DAY_IN_SECONDS
metrics = Array.new
cat = Metrics::CategoryUserNotifications.new()
cat.metrics.each { |key, value|
	metrics << [ Metrics::CategoryUserNotifications.typeid, key ]
}
$log.info "Starting User Notification metrics for #{Time.at(yesterday)}",
 	:warning

Metrics::CategoryAll::populate_and_perpetuate_defer({
	:date => yesterday,
	:historical => false,
	:metrics => metrics })

