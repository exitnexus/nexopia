
lib_require :Core, 'sql', 'sql/mysql-native/mysql'

# mysql implementation of the sql stuff
class SqlDBmysql < SqlBase
	attr(:db);

	#takes options in the form:
	# options = { :host => 'localhost', :login => 'test', :passwd => 'test', :db => 'test',
	#               :transactions => false, :seqtable => 'usercounter' }
	#   options can also include debug options as specified in SqlBase
	def initialize(name, idx, dbconfig)
		options = dbconfig.options;
		@server   = options[:host];
		@port     = options[:port];
		@user     = options[:login];
		@password = options[:passwd];
		@dbname   = options[:db];
		@seq_table = options[:seqtable];

		@persistency = options[:persistency] || false;

		@transactions = options[:transactions] || false;
		@seqtable =  options[:seqtable] || false;

		@timeout = 10; # used to tell if the connection needs to be pinged

		@db = nil;
		@in_transaction = false;

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
				#return @db.ping();
			end
			return true; #assume it's still active
		end

		#connect if needed
		begin
			@db = Mysql.real_connect(@server, @user, @password, @dbname, @port)
		rescue MysqlError => e
			raise ConnectionError, "Failed to Connect to Database: #{@dbname} Error Code #{e.errno}: #{e.error}";
		end

		begin
			query("SET collation_connection = 'latin1_swedish_ci'");
		rescue
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

		closed_db = @db;
		@db = nil;

		begin
			closed_db.close();
		rescue MysqlError => e
			# ignore errors in attempt to close.
		end

		@connection_time += Time.now.to_f - @connection_creation_time;
		@connection_creation_time = 0;
	end

	def internal_query(prepared, &block)
		#run the query
		retried = false;
		connect();
		return @db.query(prepared, &block);
		
	rescue MysqlError => e
		#disconnected since the last query, try again once
		if(e.errno == 2013 || e.errno == 2006)
			@db = nil;
			if(retried)
				raise ConnectionError, "Reconnect: SQL Error: #{e.errno}, #{e.error}. Query: #{prepared}";
			else
				$log.info("Disconnected from server, reconnecting.", :info)
				retried = true;
				retry;
			end
		end

		#other error that we don't know how to recover from
		raise QueryError, "SQL Error: #{e.errno}, #{e.error}. Query: #{prepared}";

	end
	
	#run a query. Prepare it with the parameters if there are any
	def query(query, *params, &block)
		#prepare if needed
		if(params.length > 0)
			prepared = prepare(query, *params);
		else
			prepared = query;
		end


		start_time = Time.now.to_f;

		result = internal_query(prepared, &block)
		
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
			calcfound_result = internal_query("SELECT FOUND_ROWS()");
			calcfound = calcfound_result.fetch_row()[0].to_i;
		end

		#return the result object
		return DBResultMysql.new(self, result, calcfound);
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
		return Mysql.quote(val);
	end

	def get_seq_id(primary_id, area, start = 1)
        if (@seq_table.nil?)
            raise SiteError, "get_seq_id(#{primary_id}) called with no sequence table defined.";
		end

		result = query("UPDATE #{@seq_table} SET max = LAST_INSERT_ID(max+1) WHERE id = ? && area = ?", primary_id, area);
		seq_id = result.insert_id;

		if (seq_id > 0)
			return seq_id;
		end
		result = query("INSERT IGNORE INTO #{@seq_table} SET max = ?, id = ?, area = ?", start, primary_id, area);
		if (result.affected_rows > 0)
			return start;
		else
            return self.get_seq_id(primary_id, area, start);
        end
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
	class DBResultMysql
		def initialize(db, result, total_rows)
			@db = db; #the db object
			@result = result; #the result object
			@total_rows = total_rows; #if the query had SQL_CALC_FOUND_ROWS, this is the result of that
		end

		def empty?
			return @result.empty?
		end
		
		# number of rows in the result set. Equivalent to fetch_set.length
		# possibly should be avoided, as most dbs don't have this function
		def num_rows()
			return @result.num_rows();
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
			return @db.db.affected_rows();
		end

		#insert id of the last query. If another query was run since this one, this will be wrong!
		def insert_id()
			return @db.db.insert_id();
		end

		# return one result at a time as a hash
		def fetch
			return @result.fetch_hash();
		end

		# return one result at a time as an array
		# generally only useful for: col1, col2 = fetch_array()
		def fetch_array
			return @result.fetch_row();
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
