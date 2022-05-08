lib_require :Metrics, 'metric_lookup'

module Metrics
	class IncrementalMetricData < MetricData
		init_storable(:masterdb, "metricdata")
		
		# Convert categoryid, metric, usertype from metriclookup
		# to metricid for metricdata table.
		def before_update()
			lookup = MetricLookup.find(:first,
				:conditions => ['categoryid = # AND metric = # AND usertype = ?', self.categoryid, self.metric, self.usertype])
			if (lookup == nil)
				raise ArgumentError.new("Cannot find metric type for #{categoryid}, #{metric}, #{usertype}")
			end
			
			self.metricid = lookup.id
		end
	end
end