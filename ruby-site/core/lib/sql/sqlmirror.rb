
lib_require :Core, 'sql'

#Sql class for balancing reads to replicated servers
class SqlDBMirror < SqlBase


	#takes an array of database objects in the form:
	# databases = { :insert => [insertobj],
	#               :select => [selectobj1, selectobj2, ...],
	#             }
	# dbobj is a reference to a real connection to the db, be it SqlDBmysql or SqlDBdbi, or something else

	def initialize(name, idx, dbconfig)
		@insertdb = dbconfig.children[:insert].pop();
		if (dbconfig.children.has_key?(:select) && !dbconfig.children[:select].empty?)
			@selectdb = dbconfig.children[:select].pop()
		else
			@selectdb = @insertdb;
		end

		@in_transaction = 0;
		super(name, idx, dbconfig);
	end

	def to_s
		return "Insert: #{@insertdb.to_s}; Select: #{@selectdb.to_s}";
	end

	def connect()
		#connections are created at query time
		return;
	end

	#close the connection, commiting if needed
	def close()
		@selectdb.close();
		@insertdb.close();
	end

	#run a query with reads balanced across backend servers, preparing if needed
	def query(query, *params, &block)
		#detect transactions
		if(query[0,5].upcase == "BEGIN" || query[0,17].upcase == "START TRANSACTION")
			return start();
		elsif(query[0,6].upcase == "COMMIT")
			return commit();
		elsif(query[0,8].upcase == "ROLLBACK")
			return rollback();
		end

		if(query[0,4].upcase == "LOCK")
			@in_transaction = true;
		elsif(query[0,6].upcase == "UNLOCK")
			@in_transaction = false;
		end

		#run the query on the right db connection
		if(query[0,6].upcase == "SELECT" && @in_transaction == false)
			return @selectdb.query(query, *params, &block);
		else
			return @insertdb.query(query, *params, &block);
		end
	end

	#start a transaction
	def start()
		@in_transaction = true;
		return @insertdb.start();
	end

	#commit a transaction
	def commit()
		@in_transaction = false;
		return @insertdb.commit();
	end

	#rollback a transaction
	def rollback()
		@in_transaction = false;
		return @insertdb.rollback();
	end

	#quote a string
	def quote(str)
		return @insertdb.quote(str);
	end

	def get_seq_id(id, area, start = false)
		return @insertdb.get_seq_id(id, area, start);
	end

	#list the tables in the selected database
	def list_tables()
		return @insertdb.list_tables();
	end

	# return information about all the columns associated with a table
	def list_fields(table)
		return @insertdb.list_fields(table);
	end

	# return information about all of the indexes associated with a table
	def list_indexes(table)
		return @insertdb.list_indexes(table);
	end

	def slow_time=(time)
		@insertdb.slow_time = time;
		@selectdb.slow_time = time;
		@backupdb.slow_time = time;
	end

	#get the number of queries
	def num_queries
		return @insertdb.num_queries + @selectdb.num_queries;
	end

	#get the total query time used by this object
	def query_time
		return @insertdb.query_time + @selectdb.query_time;
	end

	private
	def choose_server(choices, priority)
		if(choices.length == 0)
			return nil;
		end

		if(priority) #are there any plus servers?
			priority = false;
			choices.each { |server|
				if(server[:priority] && server[:weight] > 0)
					priority = true;
					break;
				end
			}
		end

		valid = [];
		choices.each { |server|
			if(server[:weight] <= 0 || priority != server[:priority])
				next;
			end

			(1..server[:weight]).each { |i|
				valid.push( server );
			}
		}

		if(valid.length > 0)
			return valid[rand(valid.length)];
		end

		return nil;
	end
end
