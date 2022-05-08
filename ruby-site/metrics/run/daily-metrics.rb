lib_require :Core, 'constants'
lib_require :Metrics, 'category_all'

# Execute metrics for yesterday.
yesterday = Time.now.to_i - Constants::DAY_IN_SECONDS
Metrics::CategoryAll::populate_and_perpetuate_defer({
	:date => yesterday,
	:historical => false })
