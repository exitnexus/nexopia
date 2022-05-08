lib_require :Metrics, 'category_user_information', 'category_user_sign_up', 'category_communication', 'category_comments', 'category_general', 'category_galleries', 'category_blogs', 'category_forums', 'category_profiles', 'category_user_notifications', 'category_friends', 'category_plus_sales'
lib_require :Metrics, 'register'

module Metrics
	class CategoryAll
		def self.populate_all(date, historical)
			$log.info "Starting Metrics for #{Time.at(date)}",
			 	:warning
			MetricCategoryList.categories.each { |category|
				start = Time.now.to_f
				cat = category.new()
				metrics = Array.new
				cat.metrics.each { |key, value|
					metrics << key
				}
				cat.populate(metrics, date, historical)
				duration = '%.4f' % (finish - start)
				$log.info "Metric category #{category.typeid} took #{duration} seconds", :info
			}
			$log.info "Completed Metrics run for #{Time.at(date)}",
				:warning
		end
		
		def self.populate_and_perpetuate(*args)
			opts = args[0] || {}
			date = opts[:date]
			raise ArgumentError.new("Did not specify :date") if (date.nil?)
			historical = opts[:historical]
			if (historical.nil?)
				raise ArgumentError.new("Did not specify :historical")
			end
			metrics = opts[:metrics]
			if (metrics.nil?)
				# Get all the categories and metrics
				metrics = Array.new
				MetricCategoryList.categories.each { |category|
					cat = category.new()
					cat.metrics.each { |key, value|
						metrics << [ category.typeid, key ]
					}
				}
				$log.info "Starting Metrics for #{Time.at(date)}",
				 	:warning
			end
			
			# Take the first metric and run it
			unless (metrics.empty?)
				cat_id, metric_id = metrics.first
				metrics.delete_at(0)
				unless (cat_id.nil? || metric_id.nil?)
					# Already have this data?  If so, don't repopulate
					d = Time.at(date.to_i)
					d = Time.mktime(d.year, d.month, d.day, 0, 0, 0, 0).to_i
					cache_key = "metric-in-progress-#{cat_id}-#{metric_id}-#{d}"
					in_progress = $site.memcache.get(cache_key)
					$site.memcache.set(cache_key, 1) unless in_progress
					
					unless (in_progress)
						cat = MetricCategoryList.get_category(cat_id).new
						$log.info "Starting metric (#{cat_id} - #{cat.class.description}) #{metric_id} - '#{cat.metrics[metric_id][:description]}'...", :warning
						start = Time.now.to_f
						cat.populate(metric_id, date, historical)
						$site.memcache.delete(cache_key)
						duration = Time.now.to_f - start
						if (duration > 60)
							$log.info "LONG RUNNING METRIC (#{cat_id} - #{cat.class.description}) #{metric_id} - '#{cat.metrics[metric_id][:description]}' took #{'%.4f' % duration} seconds", :critical
						else
							$log.info "Metric (#{cat_id} - #{cat.class.description}) #{metric_id} - '#{cat.metrics[metric_id][:description]}' took #{'%.4f' % duration} seconds", :warning
						end
					end
				end
			end
			
			# self-perpetuate
			if (!metrics.empty?)
				CategoryAll::populate_and_perpetuate_defer({
					:date => date,
					:historical => historical,
					:metrics => metrics
				})
				return true
			else
				$log.info "Completed Metrics run for #{Time.at(date)}",
					:warning
				return false
			end
		end
		register_task MetricsModule, :populate_and_perpetuate, :lock_time => 1200
		
	end
end
