
lib_require :Core, 'sql', 'sql/mysql-native/mysql'

# mysql implementation of the sql stuff
class SqlDBmysql < SqlBase
	attr(:db);
	attr :connection_time

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

		@timeout = 10;     # number of seconds a connection may be inactive before being reset
		@max_retries = 10; # number of attempts to connect to a db before giving up

		@db = nil;
		@in_transaction = false;
		@pid = nil

		@last_query_time = nil;
		@connection_creation_time = nil;
		@connection_time = 0;

		super(name, idx, dbconfig);
	end

	def to_s
		return @server + ':' + @dbname;
	end

	#Connect to the db if needed. Connections are generally created at first use
	def connect()
		#check if it's already connected, recently used, and from this process
		if(@db && (Time.now.to_f - @last_query_time) < @timeout && @pid && Process.pid == @pid)
			return true; #assume it's still active
		end

		#if there is no connection, or if the connection hasn't been used in a while, close it and reconnect
		close()

		retries = 0
		begin
			@db = Mysql.real_connect(@server, @user, @password, @dbname, @port)
			@pid = Process.pid
		rescue MysqlError => e
			if (@all_options[:bootstrap] && e.error =~ /Unknown database/ && retries == 0)
				self.bootstrap(*@all_options[:bootstrap])
				retry;
			elsif(retries == @max_retries)
				raise ConnectionError, "Failed to Connect to Database: #{@dbname} Error Code #{e.errno}: #{e.error}";
			else
				$log.info("Failed to connect to mysql server, reconnecting.", :info, :sql)

				sleep((retries + 1) * ((rand(200)+50)/1000.0)); #wait between 50 and 250ms (going up each retry)

				retries += 1;
				retry;
			end
		end

		#set last_query_time here, as it is used to tell if a connection has timed out
		@last_query_time = @connection_creation_time = Time.now.to_f;

		begin
			query("SET collation_connection = 'latin1_swedish_ci'");
		rescue
		end

		$log.info("Connected to #{@server}:#{@dbname}", :debug, :sql)
	end

	#This creates the database and creates its tables based on those from the config and database specified.
	def bootstrap(config_name, db)
		$log.info "Creating database #{@dbname}."
		config = ConfigBase.load_config(config_name);
		db_creator = Mysql.real_connect(@server, @user, @password, nil, @port)
		db_creator.create_db(@dbname)
		db_creator.select_db(@dbname)
		bootstrap_db = config.class.get_dbconfigs(config.class.config_name) { |name, idx, dbconf|
			if (name == db)
				dbconf.create(name, idx);
			else
				nil
			end
		}[db]
		bootstrap_db.list_tables.each {|row|
			db_creator.query(bootstrap_db.get_split_dbs[0].query("SHOW CREATE TABLE `#{row['Name']}`").fetch['Create Table'])
		}
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
		@last_query_time = 0;
	end

	def internal_query(prepared, &block)
		#run the query
		retried = false;
		begin
			connect();

			return @db.query(prepared, &block);

		rescue MysqlError => e
			#disconnected since the last query, reconnect and retry once
			if(e.errno == 2013 || e.errno == 2006)
				close()

				if(retried)
					raise ConnectionError, "Reconnect: SQL Error: #{e.errno}, #{e.error}. Query: #{prepared}";
				else
					$log.info("Disconnected from server #{@server}:#{@dbname} during query, reconnecting.", :info, :sql)
					retried = true;
					retry;
				end
			end

			if (e.errno == 1032)
				raise CannotFindRowError.new(e.errno, e.error), "SQL Error: #{e.errno}, #{e.error}. Query: #{prepared}";
			elsif ((e.errno == 1205) || (e.errno == 1213))
				raise DeadlockError.new(e.errno, e.error), "SQL Error: #{e.errno}, #{e.error}. Query: #{prepared}";
			else
				#other error that we don't know how to recover from
				raise QueryError.new(e.errno, e.error), "SQL Error: #{e.errno}, #{e.error}. Query: #{prepared}";
			end
		end
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
		@last_query_time = end_time;
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

		def free
			@result.free()
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
		
		def collect
			out = []
			while(line = @result.fetch_hash())
				out.push(yield(line))
			end
			out
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
