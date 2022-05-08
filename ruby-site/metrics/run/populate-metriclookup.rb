lib_require :Metrics, 'category_all', 'metric_lookup'

# Populate the metriclookup table with all the categories and metrics
# we know about.

$site.dbs[:masterdb].query("DELETE FROM metriclookup")
def populateMetricLookup(cat)
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
end

Metrics::MetricCategoryList.categories().each { |category|
	populateMetricLookup(category.new())
}
