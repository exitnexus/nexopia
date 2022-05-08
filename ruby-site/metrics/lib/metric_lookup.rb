module Metrics
	class MetricLookup < Cacheable
		init_storable(:masterdb, "metriclookup")
		
		def set_values!(categoryid, metric, usertype, description)
			self.categoryid = categoryid
			self.metric = metric
			self.usertype = usertype
			self.description = description
			
			return self
		end
	end
	
end