require 'csv'
lib_require :Core, 'constants', 'run-scripts'
lib_require :Metrics, 'category_all', 'metric_lookup', 'register'

module Metrics
	class MetricsPageHandler < PageHandler
		declare_handlers("metrics") {
			area :Public 
			
			access_level :Admin, MetricsModule, :metrics
			page	:GetRequest, :Full, :metrics_select
			handle	:PostRequest, :results_html, "results_html"
			handle	:PostRequest, :results_csv, "results_csv"

			access_level :Admin, MetricsModule, :populatemetrics
			handle	:PostRequest, :metricslookup, "metricslookup"
			handle	:PostRequest, :populate, "populate"
			handle	:PostRequest, :general, "general"
		}
		
		def metrics_select()
			t = Template::instance('metrics', 'metrics_select')
			t.categories = Array.new
			t.metrics_by_category = Hash.new
			Metrics::MetricCategoryList.categories().each { |category|
				t.categories << [category.typeid, category.description]
				
				t.metrics_by_category[category.typeid] = category.new.metrics.to_a.sort
			}
			t.debuginfouser = $site.debug_user?(request.session.user.id)
			
			puts t.display()
		end
		
		def metricslookup()
			$log.info "Pagehandler made us regenerate metricslookup", :warning
			
			lookups = MetricLookup.find(:all, :scan)
			lookups.each { |lookup|
				lookup.invalidate
			}
			
			Core::RunScripts::run(['populate-metriclookup'])
			
			puts '<div class="metrics" id="metrics_output">'
			puts 'Populated metriclookup, new values:<br />'
			lookups = MetricLookup.find(:all, :scan)
			lookups.each { |lookup|
				puts "#{lookup.id} #{lookup.categoryid} #{lookup.metric} #{lookup.usertype} #{lookup.description} <br />"
			}
			puts '</div>'
		end
		
		def populate()
			$log.info "Pagehandler made us populate metrics from #{params['date_from', String]} - #{params['date_to', String]}", :warning
			
			date_param = params['date_from', String].split('/')
			if (date_param.length == 3)
				from_date = Time.mktime(date_param[0], date_param[1],
				 	date_param[2], 0, 0, 0, 0).to_i()
			else
				from_date = Time.now.to_i
			end
			date_param = params['date_to', String].split('/')
			if (date_param.length == 3)
				to_date = Time.mktime(date_param[0], date_param[1],
				 	date_param[2], 0, 0, 0, 0).to_i()
			else
				to_date = Time.now.to_i
			end
			
			date = from_date
			while (date <= to_date)
				# Delete existing metricdata rows for that date
				$site.dbs[:masterdb].query("DELETE FROM metricdata
					WHERE date = ?", date)

				historical = ((Time.now.to_i - date) /
				 	Constants::DAY_IN_SECONDS) != 1
				Metrics::CategoryAll::populate_and_perpetuate_defer({
					:date => date,
					:historical => historical })
				date += Constants::DAY_IN_SECONDS
			end
			
			puts '<div class="metrics" id="metrics_output">'
			puts 'Run deferred'
			puts '</div>'
		end
		
		# Run only the new metrics, prior to expected push on 2009-03-16
		def general()
			$log.info "Pagehandler made us populate metrics from #{params['date_from', String]} - #{params['date_to', String]}", :warning
			
			date_param = params['date_from', String].split('/')
			if (date_param.length == 3)
				from_date = Time.mktime(date_param[0], date_param[1],
				 	date_param[2], 0, 0, 0, 0).to_i()
			else
				from_date = Time.now.to_i
			end
			date_param = params['date_to', String].split('/')
			if (date_param.length == 3)
				to_date = Time.mktime(date_param[0], date_param[1],
				 	date_param[2], 0, 0, 0, 0).to_i()
			else
				to_date = Time.now.to_i
			end
			
			date = from_date
			
			plus_sales = Metrics::CategoryPlusSales::typeid
			user_info = Metrics::CategoryUserInformation::typeid
			comments = Metrics::CategoryComments::typeid
			galleries = Metrics::CategoryGalleries::typeid
			blogs = Metrics::CategoryBlogs::typeid
			profiles = Metrics::CategoryProfiles::typeid
			metrics = [ [plus_sales, 1], [plus_sales, 2],
				[user_info, 12], [user_info, 13], [user_info, 14], [user_info, 15],
				[comments, 3],
				[galleries, 8], [galleries, 9], [galleries, 10], [galleries, 11],
				[galleries, 12], [galleries, 13], 
				[blogs, 13], [blogs, 14], [blogs, 15], [blogs, 16], [blogs, 17], [blogs, 18],
				[blogs, 19], [blogs, 20], [blogs, 21],
				[profiles, 8], [profiles, 9]
			]
			
			while (date <= to_date)
				# Delete old data
				metrics.each { |metric|
					$site.dbs[:masterdb].query("DELETE FROM metricdata
						WHERE metricid IN
						(SELECT id FROM metriclookup
					 	WHERE categoryid = ? AND metric = ?) AND `date` = ?",
						metric[0], metric[1], date)
				}

				historical = ((Time.now.to_i - date) /
				 	Constants::DAY_IN_SECONDS) != 1
				Metrics::CategoryAll::populate_and_perpetuate_defer({
					:date => date,
					:historical => historical,
					:metrics => metrics })
				date += Constants::DAY_IN_SECONDS
			end
			
			puts '<div class="metrics" id="metrics_output">'
			puts 'Run deferred'
			puts '</div>'
		end
		
		def results_html()
			t = Template::instance('metrics', 'metrics_output')

			t.headers, t.data = calculate()
			any_subheaders = false
			any_usertypes = false
			t.headers.each { |header|
				any_subheaders = true unless header[:subheaders].empty?
				any_usertypes = true if header[:usertypes]
			}
			t.any_subheaders = any_subheaders
			t.any_usertypes = any_usertypes
			
			puts t.display()
		end

		def results_csv()
			request.reply.headers['Content-Type'] = 'text/csv'
			request.reply.headers['Content-Disposition'] =
				'attachment; filename=metrics.csv'
			headers, data = calculate()
			any_subheaders = false
			any_usertypes = false
			headers.each { |header|
				any_subheaders = true unless header[:subheaders].empty?
				any_usertypes = true if header[:usertypes]
			}

			# Output each header
			row = ["Date"]
			headers.each { |header|
				row << header[:description]
				for i in (2..header[:header_colspan])
					row << ""
				end
			}
			puts CSV.generate_line(row)

=begin
			row = [""]
			headers.each { |header|
				row << header[:header] 
				for i in (2..header[:header_colspan])
					row << ""
				end
			}
=end

			if (any_subheaders)
				row = [""]
				headers.each { |header|
					if (header[:subheaders].empty?)
						for i in (1..header[:subheader_colspan])
							row << ""
						end
					else
						header[:subheaders].each { |subheader|
							row << subheader
							for i in (2..header[:subheader_colspan])
								row << ""
							end
						}
					end
				}
				puts CSV.generate_line(row)
			end

			if (any_usertypes)
				row = [""]
				headers.each { |header|
					if (header[:subheaders].empty?)
						if (header[:usertypes])
							row << 'all' << 'plus' << 'active'
						else
							row << ''
						end
					else
						header[:subheaders].each { |subheader|
							if (header[:usertypes])
								row << 'all' << 'plus' << 'active'
							else
								row << ''
							end
						}
					end
				}
			end

			# Output all the data
			data.each { |row|
				puts CSV.generate_line(row)
			}
		end

		private
		def calculate()
			category = MetricCategoryList.get_category(params['category',
				Integer]).new
			metrics = params['metric', Array, Array.new]
			metrics.map! { |metric|
				metric.to_i
			}
			date_param = params['date_from', String].split('/')
			if (date_param.length == 3)
				from_date = Time.mktime(date_param[0], date_param[1],
				 	date_param[2], 0, 0, 0, 0).to_i()
			else
				from_date = Time.now.to_i
			end
			date_param = params['date_to', String].split('/')
			if (date_param.length == 3)
				to_date = Time.mktime(date_param[0], date_param[1],
				 	date_param[2], 0, 0, 0, 0).to_i()
			else
				to_date = Time.now.to_i
			end

			headers = Array.new()
			metric_data = Array.new()
			metrics.each { |metricid|
				metric = category.metrics[metricid]
				desc = category.metrics[metricid][:description]
				headertext = category.metrics[metricid][:header]
				subheadertext = category.subheaders(metricid)
				usertypes = category.metrics[metricid][:usertypes]
				
				subheader_colspan = usertypes ? 3 : 1
				header_colspan = subheader_colspan
				header_colspan *= subheadertext.length if !subheadertext.empty?
				
				headers << {
					:description => desc,
					:header => headertext,
					:header_colspan => header_colspan,
					:subheaders => subheadertext,
					:subheader_colspan => subheader_colspan,
					:usertypes => usertypes
				}
				
				metric_data << category.data(metricid, from_date, to_date)
			}
			
			# We now have the metric data in an array of array of rows,
			# for each metric.  We need to convert this to a 2-dimensional
			# table by date.  So we go...
			data = Array.new()
			date = from_date
			while (date <= to_date)
				date_s = Time.at(date).strftime("%Y-%m-%d")
				date_interested = Time.at(date)
				row = [date_s]
				# For each metric
				metric_data.each { |metric|
					# For each column on that metric
					found_date = false
					metric.each { |metric_row|
						# (only care about this date)
						if (metric_row[0] == date_s)
							row += metric_row[1..-1]
							found_date = true
						end
					}
				}
				data << row
				
				date += Constants::DAY_IN_SECONDS
			end
			
			return [headers, data]
		end
		
	end
	
end
