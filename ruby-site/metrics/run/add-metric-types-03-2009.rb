lib_require :Metrics, 'category_all', 'metric_lookup'

plus_sales = Metrics::CategoryPlusSales.new
user_info = Metrics::CategoryUserInformation.new
comments = Metrics::CategoryComments.new
galleries = Metrics::CategoryGalleries.new
blogs = Metrics::CategoryBlogs.new
profiles = Metrics::CategoryProfiles.new
metrics = [ [plus_sales, 1], [plus_sales, 2],
	        [user_info, 12], [user_info, 13], [user_info, 14], [user_info, 15],
	        [comments, 3],
	        [galleries, 8], [galleries, 9], [galleries, 10], [galleries, 11],
	        [galleries, 12], [galleries, 13], 
	        [blogs, 14], [blogs, 15], [blogs, 16], [blogs, 17], [blogs, 18],
	        [blogs, 19], [blogs, 20], [blogs, 21],
	        [profiles, 8], [profiles, 9]
	]

metrics.each { |m|
	cat = m[0]
	metric_id = m[1]
	
	metric = cat.metrics[metric_id]
	if (metric[:usertypes])
		m = Metrics::MetricLookup.new()
		m.set_values!(cat.class.typeid, metric_id, 'all', metric[:description])
		m.store
		m = Metrics::MetricLookup.new()
		m.set_values!(cat.class.typeid, metric_id, 'plus', metric[:description])
		m.store
		m = Metrics::MetricLookup.new()
		m.set_values!(cat.class.typeid, metric_id, 'active', metric[:description])
		m.store(:ignore)
	else
		m = Metrics::MetricLookup.new()
		m.set_values!(cat.class.typeid, metric_id, 'na', metric[:description])
		m.store(:ignore)
	end
}
