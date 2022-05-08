
require 'lazy/future'
lib_require :Core, 'collect_hash'

#Sql class for balancing queries to distributed servers
class SqlDBStripe < SqlBase

	attr :dbs
#side effects:
#-ORDER BY is by server, so could return: 0,2,4,1,3
#-LIMIT is by server, so could return up to (numservers * limit), and of course the offset is useless
#-GROUP BY won't group across servers
#-count(*) type queries (agregates) will return one result per server if it is sent to more than one server


	#takes options in the form:
	# databases = [ { :dbobj => SqlDBmysql, .... },
	#               { :dbobj => SqlDBMirror, ... },
	#                .... ]
	#      dbobj is a reference to a lower level db type, be it a real connection
	#      to the db (SqlDBmysql or SqlDBdbi), or SqlDBMirror
	# splitfunc is a function that takes the results of get_server_values and
	#      translates them to server ids, ie indexes in the @dbs array.
	def initialize(name, idx, dbconfig)
		@dbs = dbconfig.children.collect_hash {|serverid, server| [serverid, server[0]] };
		@id_func = dbconfig.options[:id_func] || :hash;

		super(name, idx, dbconfig);
	end

	def map_servers(ids, writeop)
		return send("map_servers_#{@id_func}", ids.uniq, writeop).flatten.uniq;
	end

	def map_servers_hash(ids, writeop)
		serverids = @dbs.keys.sort;
		return ids.map {|id|
			serverids[id.to_i % serverids.length];
		}
	end

	def map_servers_all(ids, writeop)
		return ids.map {|id|
			@dbs.keys;
		}
	end

	def to_s
		dbnames = @dbs.map {|id, db| db.to_s }
		return dbnames.join(',');
	end

	def connect()
	 	#empty because connections are created as needed at query time
	 	return;
	end

	#close the connection, commiting if needed
	def close()
		@dbs.each { |id, db| db.close() }
	end

	# return an array of the underlying db handles.
	def get_split_dbs()
		return @dbs.values;
	end

	def num_dbs()
		return @dbs.length;
	end

	# split version of query figures out how to split, then passes it to squery, which actually runs it
	def query(query, *params, &block)
		prepared = prepare(query, *params);

		keys = get_server_values(prepared);

		return squery(keys, prepared, &block);
	end

	#run a query on the servers mapped to by the specified keys.
	# Prepare it with the parameters if there are any
	def squery(keys, query, *params, &block)

		#prepare if needed. Don't accept balance keys through the params
		if(params.length > 0)
			prepared = prepare(query, *params);
			prepkeys = get_server_values(prepared);

			if (prepkeys)
				raise ParamError, "Don't accept balance keys within the query to squery."
			end
		else
			prepared = query;
		end

		writeop = (prepared[0,6].upcase != "SELECT")

		#map keys to servers
		if(!keys) #do for all
			if(prepared[0,6].upcase == "INSERT" && prepared.match("SELECT")) #allow select, insert ... select, update, delete, analyze, optomize, alter table, etc, but not single row changing ops
				raise QueryError, "Cannot INSERT to all dbs: #{prepared}";
			end

			ids = map_servers_all([0], writeop).flatten.uniq;

			prepared << " /**: all :**/";
		else
			ids = map_servers([*keys], writeop);

			if(writeop)
				prepared << " /**: writeop :**/";
			end
		end

		results = [];

		if(ids.length == 0)
			$log.info("Query doesn't map to a server: #{prepared}", :debug);
		end
	futures = true;
if (!futures)
		threads = [];
		require 'thread'
		semaphore = Mutex.new();

		ids.each{ |serverid|
			db = @dbs[serverid];
			if (!db)
				$log.info("Query attempted to use split server ##{serverid} on #{self}, which doesn't exist.", :warning);
				nil
			else
				if (ids.length > 1)
					threads << Thread.new(serverid){|id|
						result = @dbs[id].query(prepared);
						semaphore.synchronize {
							results.push(result);
						}
					}
				else
					results << @dbs[serverid].query(prepared);				
				end
			end
		}

		threads.each { |thread|
			thread.join();
		}

else
		#run the queries
		results = ids.collect { |id|
			db = @dbs[id];
			if (!db)
				$log.info("Query attempted to use split server ##{id} on #{self}, which doesn't exist.", :warning);
				nil
			else
				if (false && ids.length > 1)
					future { @dbs[id].query(prepared); }
				else
					@dbs[id].query(prepared, &block);
				end
			end
		}
end
		return StripeDBResult.new(results);
	end

	#start a transaction
	def start()
		@dbs.each { |id, db| db.start(); }
	end

	#commit a transaction
	def commit()
		@dbs.each { |id, db| db.commit(); }
	end

	#rollback a transaction
	def rollback()
		@dbs.each { |id, db| db.rollback(); }
	end

	#escape a value for this db.
	def quote(str)
		return @dbs.any.quote(str);
	end

	def get_seq_id(id, area, start = false)
		serverid = map_servers([id], true).pop;
		if(serverid)
			return @dbs[serverid].get_seq_id(id, area, start);
		else
			return false;
		end
	end

	#list the tables in this db
	def list_tables()
		return @dbs.any.list_tables();
	end

	# return information about all the columns associated with a table
	def list_fields(table)
		return @dbs.any.list_fields(table);
	end

	# return information about all of the indexes associated with a table
	def list_indexes(table)
		return @dbs.any.list_indexes(table);
	end

	def slow_time=(time)
		@dbs.each { |id, db| db.slow_time = time; }
	end

	def num_queries
		num = 0;

		@dbs.each { |id, db|
			num += db.num_queries;
		}

		return num;
	end

	def query_time
		time = 0;

		@dbs.each { |id, db|
			time += db.query_time;
		}

		return time;
	end

	class StripeDBResult
		def initialize(results)
			@results = results;
		end
		
		def empty?
			return @results.empty?
		end

		# number of rows in the result set. Equivalent to fetch_set.length
		# possibly should be avoided, as most dbs don't have this function
		def num_rows()
			num = 0;
			@results.each { |result|
				num += result.num_rows();
			}
			return num;
		end

		# if the query had SQL_CALC_FOUND_ROWS, this is the result of that, otherwise just num_rows
		def total_rows()
			num = 0;
			@results.each { |result|
				num += result.total_rows();
			}

			return num;
		end

		#number of rows affected by the last query. If another query was run since this one, this will be wrong!
		def affected_rows()
			num = 0;
			@results.each { |result|
				num += result.affected_rows();
			}

			return num;
		end

		#insert id of the last query. If another query was run since this one, this will be wrong!
		def insert_id()
			if(@results.length != 1)
				raise SiteError, "Cannot insert_id on a multi-server query";
			end

			return @results[0].insert_id();
		end

		# return one result at a time as a hash
		def fetch
			@results.each { |result|
				ret = result.fetch();
				if(ret)
					return ret;
				end
			}

			return nil;
		end

		# return one result at a time as an array
		# generally only useful for: col1, col2 = fetch_array()
		def fetch_array
			@results.each { |result|
				ret = result.fetch_array();
				if(ret)
					return ret;
				end
			}

			return false;
		end

		# loop through the associated code block with each row as a hash as the parameter
		def each
			@results.each { |result|
				while(line = result.fetch())
					yield line;
				end
			}
		end

		# return an array of all the rows as hashes
		def fetch_set()
			results = [];

			@results.each { |result|
				while(line = result.fetch())
					results.push(line);
				end
			}

			return results;
		end

		# return a single field
		# generally only useful for queries that always return exactly one row with one column
		def fetch_field()
			if(@results.length != 1)
				raise SiteError, "Cannot fetchfield on a multi-db query";
			end

			return @results[0].fetch_field();
		end
	end
end
