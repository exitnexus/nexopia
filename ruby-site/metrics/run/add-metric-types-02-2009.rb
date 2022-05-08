lib_require :Metrics, 'category_all', 'metric_lookup', 'category_friends', 'category_user_sign_up'

def populateNewMetricLookup(cat)
	cat.metrics.each { |key, metric|
		if (metric[:usertypes])
			m = Metrics::MetricLookup.new()
			m.set_values!(cat.class.typeid, key, 'all', metric[:description])
			m.store
			m = Metrics::MetricLookup.new()
			m.set_values!(cat.class.typeid, key, 'plus', metric[:description])
			m.store
			m = Metrics::MetricLookup.new()
			m.set_values!(cat.class.typeid, key, 'active', metric[:description])
			m.store
		else
			m = Metrics::MetricLookup.new()
			m.set_values!(cat.class.typeid, key, 'na', metric[:description])
			m.store
		end
	}
	
	# Added manually on Friday Feb 27, 2009.
	#m = Metrics::MetricLookup.new()
	#m.set_values!(Metrics::CategoryUserSignUp.typeid, 6, 'na', "# of Users Who Completed Join Step")
	#m.store()
end

populateNewMetricLookup(Metrics::CategoryFriends.new())

