lib_require :Core, 'constants'
lib_require :Metrics, 'metric_data', 'register'

module Metrics
	# A metric category represents a set of metrics.
	# For example, the User Information category represents
	# the metrics for age, sex, location, interests, etc.
	# This is a base class.  Actual categories derive from
	# this class.  This provides a means for generating
	# multiple metrics with a single query or with a smaller
	# set of queries, at least.  More importantly, it means
	# we have only a few classes to handle metrics, rather
	# than a class for each instance.
	# Each derived class should extend TypeID to provide for
	# a category id and should register themselves by calling
	# metric_category.
	class MetricCategory
		attr :metrics, true
		
		# By convention, we store metric data as follows.
		# The @metrics hash table is indexed by metric number,
		# a constant defined by the derived classes.  It stores
		# a number of hash tables.
		# These hash tables contain:
		# { :description => Description of this metric,
		#   :header => The single header, such as 'Sex',
		#   :subheaders => An array (which may be empty),
		#    for example, 'Male', 'Female',
		#   :usertypes => Do we want to populate three
		#    columns, one for all users, one for active
		#    users, and one for plus users?
		#   :allow_historical => true or false depending
		#    on whether it makes sense to collect historical
		#    data. }
		# The @metrics variable must be initialized during
		# instantiation (i.e. by derived class's initialize
		# method).
		def initialize()
			@metrics = Hash.new()
		end
		
		def usertypes()
			return [ 'all', 'active', 'plus']
		end
		
		def usertypes_where(date, i)
			if (i == 1)
				return " (useractivetime.activetime > #{date - 30 * Constants::DAY_IN_SECONDS}) "
			elsif (i == 2)
				return " (users.premiumexpiry > #{date}) "
			else
				return ' (1 = 1) '
			end
		end
		
		# Return a human-readable description of category
		def self.description()
			raise "not implemented"
		end
		
		# Get the list of subheaders for a given metric.
		# This may be overridden to take into account metrics
		# such as those based on location, which need to
		# return a list of locations, determined at runtime.
		def subheaders(metric)
			if (!@metrics.has_key?(metric))
				raise ArgumentError.new("Unknown metric #{metric} for category #{self.class.typeid}")
			end
			# May want to override the following.
			return @metrics[metric][:subheaders]
		end
		
		# How many columns does a given metric have?
		# metrics are the metrics we are interested in.
		def column_count(metrics)
			metrics = [*metrics]
			total = 0
			metrics.each { |metric|
				if (!@metrics.has_key?(metric))
					raise ArgumentError.new("Unknown metric #{metric} for category #{self.class.typeid}")
				end
				count = subheaders(metric).length
				count = 1 if count == 0
				count *= 3 if @metrics[metric][:usertypes]
				total += count
			}
			
			return total
		end
		
		# Pull back all of the data for a set of metrics.
		# - metrics - one or more metrics we are interested in.
		# - from - from date for the data
		# - to - to date for the data
		# Return a 2-dimensional array of metric data.
		# This is [row, row, row] where each row is [date, cell, cell]
		# Note that some of the cells may have nil values if there was
		# no data in the db.
		def data(metrics, from, to)
			metrics = [*metrics]
			from = start_of_day(from)
			to = start_of_day(to)
			
 			# This is the 2D array of dates and values.  The first column is
			# the date.  The rest are left nil for now.
			total_columns = column_count(metrics)
			output = Array.new()
			date = from
			while (date <= to)
				output << [Time.at(date).strftime("%Y-%m-%d"),
					*Array.new(total_columns)]
				date += Constants::DAY_IN_SECONDS
			end
			
			output_col = 1
			metrics.each { |metric_id|
				metric_data = $site.dbs[:masterdb].query("SELECT md.`date`,
				 	md.col,	ml.usertype, md.`value`
					FROM metriclookup ml JOIN metricdata md
					ON ml.id = md.metricid
					WHERE ml.categoryid = # AND ml.metric = # AND
					md.`date` >= # AND md.`date` <= #",
					self.class.typeid, metric_id, from, to)
				metric_data.each { |metric_row|
					date = metric_row['date'].to_i
					row = (date - from) / Constants::DAY_IN_SECONDS
					if ((row < 0) || (row >= output.length()))
						raise "Confused date #{date} not between #{from} and #{to}"
					end
					if (metric_row['usertype'] == 'all')
						col = output_col + metric_row['col'].to_i * 3
					elsif (metric_row['usertype'] == 'plus')
						col = output_col + metric_row['col'].to_i * 3 + 1
					elsif (metric_row['usertype'] == 'active')
						col = output_col + metric_row['col'].to_i * 3 + 2
					else
						col = output_col + metric_row['col'].to_i
					end
					if ((col >= 0) && (col < output[row].length()))
						datum = format_cell(metric_id, metric_row['value'].to_i)
						output[row][col] = datum
					else
						# If we have an invalid column, just don't output it
					end
				}
				
				output_col += column_count(metric_id)
			}
			
			return output			
		end

		# Populate data for given date.
		# This will populate the metricdata table for all metrics.
		# - metrics is the list of metrics we want to populate.
		# - date is the date of interest.
		# - historical, if true, means we are collecting historical data.
		def populate(metrics, date, historical)
			raise "not implemented"
		end
		
		def start_of_day(date)
			return self.class.get_start_of_day(date)
		end
		
		# Return date converted to the beginning of the specified day.
		def self.get_start_of_day(date)
			d = Time.at(date.to_i)
			return Time.mktime(d.year, d.month, d.day, 0, 0, 0, 0).to_i
		end
		
		private
		
		# Figure out the from and to dates.
		# - date is the date to base the calculations on.  This is passed as
		# an integer such as Time.now.to_i.
		# Return [ date_from, date_to ] where date_to is the end of the
		# day of the date passed in.  date_from is date_to minus
		# number_of_days.  For example, if you pass in a time belonging
		# to 2009-01-08, and 1 for number_of_days, you'll get back
		# [ 2009-01-08 0:0:0, 2009-01-09 0:0:0 ] (though as integers).
		def get_date_from_to(date, number_of_days)
			# Beginning of specified day
			day_to = start_of_day(date) + Constants::DAY_IN_SECONDS
			day_from = day_to - Constants::DAY_IN_SECONDS * number_of_days
			
			return [ day_from, day_to ]
		end
		
		# Unshard a result set.
		# - results is the result set.
		# - count_column is the column name to sum up.
		# - group_column (optional) is a single GROUP BY column.
		# For example, SELECT COUNT(*) AS thecount FROM users,
		# we'd call unshard(results, 'thecount') and the
		# return value would be a single number (say, 500000)
		# Another example, SELECT sex, COUNT(*) AS thecount FROM users
		# GROUP BY sex
		# we'd call unshard(results, 'thecount', 'sex') and
		# the return value would be a hash table,
		# { "Male" => 200000, "Female" => 300000}
		def unshard(results, count_column, group_column = nil)
			if (group_column == nil)
				count = 0
				results.each { |result|
					count += result[count_column].to_i
				}
			else
				count = Hash.new()
				results.each { |result|
					key = result[group_column]
					if (!count.has_key?(key))
						count[key] = 0
					end
					count[key] += result[count_column].to_i
				}
			end
			return count
		end

		# Execute a query for a specific usertype, and store the result.
		# Params can take the following symbols:
		# :group_col The column for the GROUP BY clause.
		# :db        The database for this query.
		def populate_type(metricid, query, date, column, usertype, params = {})
			if (!@metrics.has_key?(metricid))
				raise "Cannot find metric #{metricid}"
			end

			if (params.has_key?(:group_col))
				group_by = " GROUP BY #{params[:group_col]}"
				group_col = params[:group_col]
			else
				group_by = ""
				group_col = nil
			end
			
			db = params[:db] || :usersdb
			
			results = $site.dbs[db].query(query + group_by)
			count = unshard(results, 'thecount', group_col)
			# If we get back a hash, it's because we grouped the results.
			# Could also check if group_by == nil, I suppose.
			if (count.kind_of?(Hash))
				count.each { |key, value|
					m = MetricData.new()
					m.categoryid = self.class.typeid
					m.metric = metricid
					m.usertype = usertype
					m.col = key
					m.date = date
					m.value = value
					m.store(:ignore)
				}
			else
				m = MetricData.new()
				m.categoryid = self.class.typeid
				m.metric = metricid
				m.usertype = usertype
				m.col = column
				m.date = date
				m.value = count
				m.store(:ignore)
			end
		end

		# Execute the query once for each of the three types of
		# users (all, active, plus), storing the MetricData.
		# Params can take the following symbols:
		# :group_col The column for the GROUP BY clause.
		# :db        The database for this query.
		def populate_types(metricid, query, date, column = 0, params = {})
			if (!params.kind_of? Hash)
				raise ArgumentError.new("params is not a hash table")
			end
			if (!@metrics.has_key?(metricid))
				raise "Cannot find metric #{metricid}"
			end

			if (params.has_key?(:group_col))
				group_by = " GROUP BY #{params[:group_col]}"
				group_col = params[:group_col]
			else
				group_by = ""
				group_col = nil
			end
			
			if (params.has_key?(:db))
				db = params[:db]
			else
				db = :usersdb
			end

			queries = Array.new()
			if (@metrics[metricid][:usertypes])
				usertypes.each_index { |i|
					if (query.upcase =~ /WHERE/)
						q = "#{query} AND #{usertypes_where(date, i)} 
							#{group_by}"
					else
						q = "#{query} WHERE #{usertypes_where(date, i)}
						 	#{group_by}"
					end
					queries << [ q, usertypes[i] ]
				}
			else
				queries = [ ["#{query} #{group_by}", 'na'] ]
			end

			queries.each { |q|
				results = $site.dbs[db].query(q[0])
				count = unshard(results, 'thecount', group_col)

				# If we get back a hash, it's because we grouped the results.
				# Could also check if group_by == nil, I suppose.
				if (count.kind_of?(Hash))
					count.each { |key, value|
						raise "Confused key for #{q[0]}" if (key == nil)
						m = MetricData.new()
						m.categoryid = self.class.typeid
						m.metric = metricid
						m.usertype = q[1]
						m.col = key
						m.date = date
						m.value = value
						m.store(:ignore)
					}
				else
					m = MetricData.new()
					m.categoryid = self.class.typeid
					m.metric = metricid
					m.usertype = q[1]
					m.col = column
					m.date = date
					m.value = count
					m.store(:ignore)
				end
			}
		end

		# Execute a query that is to create an average value.
		# We expect the columns of the dividend and the divisor
		# to be named as such.  We multiply the result by
		# 1000 and truncate.
		def populate_avg(metricid, query, date, usertype, col = 0)
			if (!@metrics.has_key?(metricid))
				raise "Cannot find metric #{metricid}"
			end
			results = $site.dbs[:usersdb].query(query)
			dividend = 0
			divisor = 0
			results.each { |result|
				dividend += result['dividend'].to_i
				divisor += result['divisor'].to_i
			}
			m = MetricData.new()
			m.categoryid = self.class.typeid
			m.metric = metricid
			m.usertype = usertype
			m.col = col
			m.date = date
			if (dividend == 0)
				m.value = 0
			else
				m.value = dividend * 1000 / divisor
			end
			m.store(:ignore)
		end
		
		# Execute a given query which pulls a list of userids.
		# The id column must be named `id`.
		# Store metricdata representing a count of those userids
		# in each type of user (all, active, plus).
		# - db is the database on which to issue the initial query
		def populate_with_userids(metricid, query, date, col, db = :usersdb)
			results = $site.dbs[db].query(query)
			userids = Hash.new()
			results.each { |row|
				userids[row['id']] = true
			}
			userids_as_s = userids.keys.join(',')
			if (userids_as_s != "")
				usertypes.each_index { |i|
					q = "SELECT COUNT(*) AS thecount FROM users
					JOIN useractivetime ON users.userid = useractivetime.userid
					WHERE users.userid IN (#{userids_as_s}) AND
					#{usertypes_where(date, i)}"
					populate_type(metricid, q, date, col, usertypes[i])
				}
			end
		end
		
		def user_is_all(user)
			return true
		end
		
		def user_is_active(user, date)
			return user.activetime >= date - Constants::DAY_IN_SECONDS * 30
		end
		
		def user_is_plus(user, date)
			return user.premiumexpiry >= date
		end
		
		def okay_to_run?(metricid, metrics_to_run, historical)
			return ((metrics_to_run.include? metricid) &&
				(@metrics[metricid][:allow_historical] ||
				 !historical))
		end
		
		# Format a single cell for output.  In most cases, we just dump
		# the cell's data unchanged but sometimes we need to override
		# this method to do some special formatting.  An example would
		# be the 'average' columns which store their data as value * 1000
		# but which should be displayed using the correct decimals.
		def format_cell(metricid, datum)
			return datum
		end
		
	end
end

