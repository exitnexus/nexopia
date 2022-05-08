lib_require :Metrics, 'metric_lookup'

module Metrics
	class MetricData < Storable
		init_storable(:masterdb, "metricdata")
		
		attr :categoryid, true
		attr :metric, true
		attr :usertype, true
		
		# Convert categoryid, metric, usertype from metriclookup
		# to metricid for metricdata table.
		def before_create()
			lookup = MetricLookup.find(:first,
				:conditions => ['categoryid = ? AND metric = ? AND usertype = ?', self.categoryid, self.metric, self.usertype])
			if (lookup == nil)
				raise ArgumentError.new("Cannot find metric type for #{categoryid}, #{metric}, #{usertype}")
			end
			
			self.metricid = lookup.id
		end
		
		def set_values!(categoryid, metric, usertype, column, date, value)
			self.categoryid = categoryid
			self.metric = metric
			self.usertype = usertype
			self.col = column
			self.date = date
			self.value = value
		end

	end
end