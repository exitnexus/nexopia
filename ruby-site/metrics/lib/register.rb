# Allow metrics to be registered as part of a specific category.
module Metrics
	class MetricCategoryList
		# Register a metric.
		def self.register_category(klass)
			$log.info "Adding metric category #{klass.class}", :debug, :worker
			@@categories = Array.new() if !defined?(@@categories)
			@@categories << klass
		end

		# Get all the metric categories.
		# Return an array of [category_klass, ...].
		def self.categories()
			@@categories = Array.new() if !defined?(@@categories)
			return @@categories
		end

		# Get a specific category
		def self.get_category(category_id)
			@@categories = Array.new() if !defined?(@@categories)
			@@categories.each { |category|
				return category if (category.typeid == category_id)
			}
			return nil
		end
		
	end
	
end

module Kernel
	def metric_category()
		Metrics::MetricCategoryList.register_category(self)
	end

end
