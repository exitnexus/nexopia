
require 'dbi'
lib_require :Core, 'sql'

# Generic dbi implementation of the sql stuff
class SqlDBdbi < SqlBase

	#takes options in the form:
	# options = { :driver => 'Mysql', :host => 'localhost', :login => 'test', :passwd => 'test', :db => 'test',
	#               :transactions => false, :seqtable => 'usercounter' }
	#   options can also include debug options as specified in SqlBase
	# priority is for compatability with the mirror implemenation, and isn't used
	def initialize(name, idx, dbconfig)
		options = dbconfig.options;
		@driver   = options[:driver] || 'Mysql';
		@server   = options[:host];
		@user     = options[:login];
		@password = options[:passwd];
		@dbname   = options[:db];

		@persistency = options[:persistency] || false;

		@transactions = options[:transactions] || false;
		@seqtable =  options[:seqtable] || false;

		@timeout = 10; # used to tell if the connection needs to be pinged

		@db = nil;

		super(name, idx, dbconfig);
	end

	def to_s
		return @server + ':' + @dbname;
	end

	#Connect to the db if needed. Connections are generally created at first use
	def connect()
		#check if it's already connected
		if(@db)
			if(Time.now.to_i - @last_query_time > @timeout) #if the connection hasn't been used in a while, ping it
				return @db.ping();
			end
			return true; #assume it's still active
		end

		#connect if needed
		begin
			@db = DBI.connect("dbi:#@driver:#@dbname:#@server", @user, @password);
		rescue DBI::DatabaseError => e
			raise ConnectionError, "Failed to Connect to Database: #{@dbname} Error Code #{e.err}: #{e.errstr}";
		end

		@connection_creation_time = Time.now.to_f;
	end

	#close the connection, commiting if needed
	def close()
		if(!@db)
			return false;
		end

		if(@in_transaction)
			commit();
		end

		@db.disconnect();
		@db = nil;

		@connection_time += Time.now.to_f - @connection_creation_time;
		@connection_creation_time = 0;
	end

	#run a query. Prepare it with the parameters if there are any
	def query(query, *params)
		connect();

		#prepare if needed
		if(params.length > 0)
			prepared = prepare(query, *params);
		else
			prepared = query;
		end


		start_time = Time.now.to_f;

		#run the query
		begin
			result = @db.execute(prepared);
		rescue DBI::DatabaseError => e
			#disconnected since the last query, try again once
			if(false) #e.err == 2013 || e.errno == 2006)
				if(retried)
					raise ConnectionError, "Reconnect: SQL Error: #{e.err}, #{e.errstr}. Query: #{prepared}";
				else
					retried = true;
					retry;
				end
			end

			#other error that we don't know how to recover from
			raise QueryError, "SQL Error: #{e.err}, #{e.errstr}. Query: #{prepared}";
		end

		end_time = Time.now.to_f;

		#debug book keeping
		query_time = end_time - start_time;
		@time += query_time;
		@last_query_time = end_time.to_i;
		@num_queries += 1;
		debug(prepared, query_time);


		# does the query run SQL_CALC_FOUND_ROWS?
		calcfound = (query =~ /^SELECT\s+(DISTINCT\s+)?SQL_CALC_FOUND_ROWS/);
		if(calcfound)
			calcfound_result = @db.execute("SELECT FOUND_ROWS()");
			calcfound = calcfound_result.fetch_array()[0].to_i;
		end

		#return the result object
		return DBResultDBI.new(self, result, calcfound);
	end

	#start a transaction, assuming this database supports it
	def start()
		if(@transactions)
			@in_transaction = true;
			return query("START TRANSACTION");
		end

		return false;
	end

	#commit the transaction, assuming this database supports it, and one was open
	def commit()
		# don't commit if no connection exists
		if(@transactions && @in_transaction && @db)
			@in_transaction = false;
			return query("COMMIT");
		end

		return false;
	end

	#rollback the transaction, assuming this database supports it, and one was open
	def rollback()
		# Don't rollback if no connection exists.
		# Don't need to be in a transaction to roll back, in case it was left over from a different process
		if(@transactions && @db)
			@in_transaction = false;
			return query("ROLLBACK");
		end

		return false;
	end

	#quote a value
	def quote(val)
		connect();
		return @db.quote(val).match(/^'(.*)'$/)[1];
	end

	#TODO:
	def get_seq_id(userid, area, start = false)
	end

	#return information about the tables
	def list_tables()
		return query("SHOW TABLE STATUS");
	end

	# return information about all the columns associated with a table
	def list_fields(table)
		return query("SHOW FIELDS FROM `#{table}`");
	end

	# return information about all of the indexes associated with a table
	def list_indexes(table)
		return query("SHOW INDEXES FROM `#{table}`");
	end

	# the result of a SELECT query from the mysql implementation of the SqlDB class
	class DBResultDBI
		def initialize(db, result, total_rows)
			@db = db;
			@result = result; #the result object
			@total_rows = total_rows; #if the query had SQL_CALC_FOUND_ROWS, this is the result of that
		end

		def empty?
			return @result.empty?
		end
		
		# number of rows in the result set. Equivalent to fetch_set.length
		# possibly should be avoided, as most dbs don't have this function
		def num_rows()
			return @result.rows();
		end

		# if the query had SQL_CALC_FOUND_ROWS, this is the result of that, otherwise just num_rows
		def total_rows()
			if(@total_rows)
				return @total_rows;
			else
				return num_rows();
			end
		end

		#number of rows affected by the last query. If another query was run since this one, this will be wrong!
		def affected_rows()
			return num_rows();
		end

		#insert id of the last query. If another query was run since this one, this will be wrong!
		def insert_id()
			raise SiteError, "Is this even implemented in dbi?";
			return @db.db.insert_id();
		end

		# return one result at a time as a hash
		def fetch
			return @result.fetch_hash();
		end

		# return one result at a time as an array
		# generally only useful for: col1, col2 = fetch_array()
		def fetch_array
			return @result.fetch_array();
		end

		# loop through the associated code block with each row as a hash as the parameter
		def each
			while(line = @result.fetch_hash())
				yield line;
			end
		end

		# return an array of all the rows as hashes
		def fetch_set()
			results = [];

			while(line = @result.fetch_hash())
				results.push(line);
			end

			return results;
		end

		# return a single field
		# generally only useful for queries that always return exactly one row with one column
		def fetch_field()
			return fetch_array()[0];
		end
	end
end
