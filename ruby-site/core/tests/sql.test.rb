lib_require :Core, 'sql'
lib_require :Devutils, 'quiz'

class TestSql < Quiz
	def setup
		@working_config = SqlBase::Config.new(:options => {
		                        :host => '192.168.0.50',
								:login => 'root',
								:passwd => 'Hawaii',
								:db => 'nexopia' } );

		@broken_config = SqlBase::Config.new(:options => {
		                        :host => '192.168.0.50',
								:login => 'root',
								:passwd => 'badpass',
								:db => 'nexopia' } );


		@db = SqlDBmysql.new( @working_config );

		@dbtypes = [ SqlDBmysql,  SqlDBStripe, SqlDBMirror ];
		@resulttypes = [ SqlDBmysql::DBResultMysql, SqlDBStripe::StripeDBResult ];
	end

	def teardown
		@db.close();
	end

	def assert_class_respond_to(klass, method)
		return assert(klass.method_defined?(method))
	end

	def test_db_functions()
		@dbtypes.each{ |klass|
			assert_class_respond_to(klass, :connect);
			assert_class_respond_to(klass, :close);
			assert_class_respond_to(klass, :query);
			assert_class_respond_to(klass, :squery);
			assert_class_respond_to(klass, :prepare);
			assert_class_respond_to(klass, :start);
			assert_class_respond_to(klass, :commit);
			assert_class_respond_to(klass, :rollback);
			assert_class_respond_to(klass, :transaction);
			assert_class_respond_to(klass, :get_split_dbs);
			assert_class_respond_to(klass, :quote);
			assert_class_respond_to(klass, :get_seq_id);
			assert_class_respond_to(klass, :get_split_dbs);
			assert_class_respond_to(klass, :to_s);
			assert_class_respond_to(klass, :num_dbs);
			assert_class_respond_to(klass, :list_fields);
			assert_class_respond_to(klass, :list_fields);
		}
	end

	def test_result_functions()
		@resulttypes.each{ |klass|
			assert_class_respond_to(klass, :num_rows);
			assert_class_respond_to(klass, :total_rows);
			assert_class_respond_to(klass, :insert_id);
			assert_class_respond_to(klass, :affected_rows);
			assert_class_respond_to(klass, :fetch);
			assert_class_respond_to(klass, :fetch_array);
			assert_class_respond_to(klass, :each);
			assert_class_respond_to(klass, :fetch_set);
		}
	end

	def test_connect_mysql()
		assert_nothing_raised(Exception) {
			@gooddb = SqlDBmysql.new( @working_config );
			@gooddb.connect();
		}

		assert_raise(SqlBase::ConnectionError) {
			@baddb = SqlDBmysql.new( @broken_config );
			@baddb.connect();
		}
	end

	def test_prepare
		#check parameter count warnings
		assert_raise( SqlBase::ParamError ) { @db.prepare("SELECT * FROM table WHERE id = ?") };
		assert_raise( SqlBase::ParamError ) { @db.prepare("SELECT * FROM table WHERE id = ?", 1, 2) };

		#test the different types, and escaping them
		assert_raise( SqlBase::ParamError ) { @db.prepare("SELECT * FROM table WHERE id = ?", @db) };
		assert_equal( @db.prepare("SELECT * FROM table WHERE id = ?", 1),
						"SELECT * FROM table WHERE id = 1");
		assert_equal( @db.prepare("SELECT * FROM table WHERE str = ?", 'test'),
						"SELECT * FROM table WHERE str = 'test'");
		assert_equal( @db.prepare("SELECT * FROM table WHERE str = ?", "test'in"),
						"SELECT * FROM table WHERE str = 'test\\'in'");
		assert_equal( @db.prepare("SELECT * FROM table WHERE str = ?", "test ?"),
						"SELECT * FROM table WHERE str = 'test ?'");
		assert_equal( @db.prepare("SELECT * FROM table WHERE str = ?", "test #"),
						"SELECT * FROM table WHERE str = 'test #'");
		assert_equal( @db.prepare("SELECT * FROM table WHERE bool = ?", true),
						"SELECT * FROM table WHERE bool = 'y'");
		assert_equal( @db.prepare("SELECT * FROM table WHERE bool = ?", false),
						"SELECT * FROM table WHERE bool = 'n'");
		assert_equal( @db.prepare("SELECT * FROM table WHERE nullval = ?", nil),
						"SELECT * FROM table WHERE nullval = NULL");
		assert_equal( @db.prepare("SELECT * FROM table WHERE str = ?", :test),
						"SELECT * FROM table WHERE str = 'test'");
		assert_equal( @db.prepare("SELECT * FROM table WHERE id IN ?", [1,2]),
						"SELECT * FROM table WHERE id IN (1,2)");
		assert_equal( @db.prepare("SELECT * FROM table WHERE (userid,id) IN ?", [[1,2], [3,4]]),
						"SELECT * FROM table WHERE (userid,id) IN ((1,2),(3,4))");

		#test more complex queries.
		assert_equal( @db.prepare("SELECT * FROM table WHERE id = ? && other = ?", 1, 2),
						"SELECT * FROM table WHERE id = 1 && other = 2");
		assert_equal( @db.prepare("SELECT * FROM table WHERE id = ? && other = ?", *[1, 2]),
						"SELECT * FROM table WHERE id = 1 && other = 2");
		assert_equal( @db.prepare("SELECT * FROM table WHERE id = ? LIMIT 1", 1),
						"SELECT * FROM table WHERE id = 1 LIMIT 1");

		#test # placeholder
		assert_equal( @db.prepare("SELECT * FROM table WHERE id = #", 1),
						"SELECT * FROM table WHERE id = 1/**%: 1 :%**/");
		assert_equal( @db.prepare("SELECT * FROM table WHERE id IN #", [1,2]),
						"SELECT * FROM table WHERE id IN (1/**%: 1 :%**/,2/**%: 2 :%**/)");
		assert_equal( @db.prepare("SELECT * FROM table WHERE (userid,id) IN #", [[1,2], [3,4]]),
						"SELECT * FROM table WHERE (userid,id) IN ((1/**%: 1 :%**/,2),(3/**%: 3 :%**/,4))");
	end

	def test_servervals()
		prepared = @db.prepare("SELECT * FROM table WHERE id = ?", 1);
		assert_equal(@db.get_server_values(prepared), false); #shouldn't change the query
		assert_equal(prepared, "SELECT * FROM table WHERE id = 1");

		prepared = @db.prepare("SELECT * FROM table WHERE id = #", 1);
		assert_equal(@db.get_server_values(prepared), [1]); #strips out the comment so the next one works
		assert_equal(prepared, "SELECT * FROM table WHERE id = 1");

		prepared = @db.prepare("SELECT * FROM table WHERE id IN #", [1,2]);
		assert_equal(@db.get_server_values(prepared), [1,2]);
		assert_equal(prepared, "SELECT * FROM table WHERE id IN (1,2)");

		prepared = @db.prepare("SELECT * FROM table WHERE (userid,id) IN #", [[1,2], [3,4]])
		assert_equal(@db.get_server_values(prepared), [1,3]);
		assert_equal(prepared, "SELECT * FROM table WHERE (userid,id) IN ((1,2),(3,4))");

		prepared = @db.prepare("SELECT * FROM table WHERE foo = 'asdf' AND id = #", 1);
		assert_equal(@db.get_server_values(prepared), [1]);
		assert_equal(prepared, "SELECT * FROM table WHERE foo = 'asdf' AND id = 1");

		prepared = @db.prepare("SELECT * FROM table WHERE foo = '' AND id = #", 1);
		assert_equal(@db.get_server_values(prepared), [1]);
		assert_equal(prepared, "SELECT * FROM table WHERE foo = '' AND id = 1");

		prepared = @db.prepare("SELECT * FROM table WHERE id = # AND foo = 'asdf'", 1);
		assert_equal(@db.get_server_values(prepared), [1]);
		assert_equal(prepared, "SELECT * FROM table WHERE id = 1 AND foo = 'asdf'");

		prepared = @db.prepare("SELECT * FROM table WHERE id = # AND foo = ''", 1);
		assert_equal(@db.get_server_values(prepared), [1]);
		assert_equal(prepared, "SELECT * FROM table WHERE id = 1 AND foo = ''");

		prepared = @db.prepare("SELECT * FROM table WHERE foo = 'asdf' AND id = # AND bar = 'fdsa'", 1);
		assert_equal(@db.get_server_values(prepared), [1]);
		assert_equal(prepared, "SELECT * FROM table WHERE foo = 'asdf' AND id = 1 AND bar = 'fdsa'");

		prepared = @db.prepare("SELECT * FROM table WHERE foo = 'as\\'df' AND id = #", 1);
		assert_equal(@db.get_server_values(prepared), [1]);
		assert_equal(prepared, "SELECT * FROM table WHERE foo = 'as\\'df' AND id = 1");

		prepared = @db.prepare("SELECT * FROM table WHERE foo = 'as\\'df' AND id = # AND bar = 'asdf'", 1);
		assert_equal(@db.get_server_values(prepared), [1]);
		assert_equal(prepared, "SELECT * FROM table WHERE foo = 'as\\'df' AND id = 1 AND bar = 'asdf'");

		prepared = @db.prepare("SELECT * FROM table WHERE foo = 'as\\'df' AND id = # AND bar = 'as\\'df'", 1);
		assert_equal(@db.get_server_values(prepared), [1]);
		assert_equal(prepared, "SELECT * FROM table WHERE foo = 'as\\'df' AND id = 1 AND bar = 'as\\'df'");

		prepared = @db.prepare("SELECT * FROM table WHERE foo = 'as\\\\\\'df' AND id = #", 1);
		assert_equal(@db.get_server_values(prepared), [1]);
		assert_equal(prepared, "SELECT * FROM table WHERE foo = 'as\\\\\\'df' AND id = 1");

		prepared = @db.prepare("SELECT * FROM table WHERE foo = 'asdf\\\\' AND id = #", 1);
		assert_equal(@db.get_server_values(prepared), [1]);
		assert_equal(prepared, "SELECT * FROM table WHERE foo = 'asdf\\\\' AND id = 1");

		prepared = @db.prepare("SELECT * FROM table WHERE foo = 'asdf /**%: 2 :%**/ asdf' AND id = ?", 1);
		assert_equal(@db.get_server_values(prepared), false);
		assert_equal(prepared, "SELECT * FROM table WHERE foo = 'asdf /**%: 2 :%**/ asdf' AND id = 1");

		prepared = @db.prepare("SELECT * FROM table WHERE foo = 'asdf /**%: 2 :%**/ asdf' AND id = #", 1);
		assert_equal(@db.get_server_values(prepared), [1]);
		assert_equal(prepared, "SELECT * FROM table WHERE foo = 'asdf /**%: 2 :%**/ asdf' AND id = 1");

		prepared = @db.prepare("SELECT * FROM table WHERE foo = 'asdf' AND id = # AND bar = 'asdf'", 1);
		assert_equal(@db.get_server_values(prepared), [1]);
		assert_equal(prepared, "SELECT * FROM table WHERE foo = 'asdf' AND id = 1 AND bar = 'asdf'");
	end


	def test_simple_queries()
		run_db_test(@db);

		@mirrordb = SqlBase::Config.new( :type => SqlDBMirror, :children => { :insert => [ @db], :select => [ @db ]} ).create();
		@stripedb = SqlBase::Config.new( :type => SqlDBStripe, :children => { :stripes => [ @db ]} ).create();

		run_db_test(@mirrordb);
		run_db_test(@stripedb);
	end

	def run_db_test(db)
		#queries work at all
		assert_nothing_raised(Exception){
			db.query("SELECT VERSION()");
		}

		#can get a single result. Note the type conversion is expected.
		val = nil;
		assert_nothing_raised(Exception){
			result = db.query("SELECT ? as col", 1);
			val = result.fetch_field();
		}
		assert_equal(val, '1');

		#can get a full row as a hash
		val = nil;
		assert_nothing_raised(Exception){
			result = db.query("SELECT ? as col1, ? as col2", 1, 2);
			val = result.fetch();
		}
		assert_equal(val, {'col1' => '1', 'col2' => '2'});

		#can get a full row as an array
		val = nil;
		assert_nothing_raised(Exception){
			result = db.query("SELECT ? as col1, ? as col2", 1, 2);
			val = result.fetch_array();
		}
		assert_equal(val, ['1', '2']);

		#populate a test table
		assert_nothing_raised(Exception){
			db.query("CREATE TEMPORARY TABLE temptable (col1 INT(10) NOT NULL, col2 INT(10) NOT NULL)");
			db.query("INSERT INTO temptable (col1, col2) VALUES (1,2),(3,4),(5,6)");
		}

		#get a full row set
		val = nil;
		assert_nothing_raised(Exception){
			result = db.query("SELECT * FROM temptable");
			val = result.fetch_set();
		}

		assert_equal(val, [ {'col1' => '1', 'col2' => '2'},
		                    {'col1' => '3', 'col2' => '4'},
		                    {'col1' => '5', 'col2' => '6'}, ] );

		#get a partial row set
		val = nil;
		assert_nothing_raised(Exception){
			result = db.query("SELECT * FROM temptable LIMIT 2");
			val = result.fetch_set();
		}

		assert_equal(val, [ {'col1' => '1', 'col2' => '2'},
		                    {'col1' => '3', 'col2' => '4'}, ] );


		#get a full row set one by one
		result = nil;
		assert_nothing_raised(Exception){
			result = db.query("SELECT * FROM temptable");
		}

		assert_equal(result.fetch(), {'col1' => '1', 'col2' => '2'} );
		assert_equal(result.fetch(), {'col1' => '3', 'col2' => '4'} );
		assert_equal(result.fetch(), {'col1' => '5', 'col2' => '6'} );
		assert_equal(result.fetch(), nil );

		#get a full row set and iterate over them.
		result = nil;
		assert_nothing_raised(Exception){
			result = db.query("SELECT * FROM temptable");
		}
		num = 0;
		result.each { |col|
			num += 1;
		}
		assert_equal(num, 3 );

		#get a full row set and check num_rows
		result = nil;
		assert_nothing_raised(Exception){
			result = db.query("SELECT * FROM temptable");
		}
		assert_equal(result.num_rows(), 3);


		assert_nothing_raised(Exception){
			db.query("DROP TABLE temptable");
		}
	end
end
