lib_require :Core, "benchmark"

def randstr()
	str = []
	1000.times {
		str << rand(1000000).to_s
	}
	return str.join('')
end

test_num = 10000

	working_config = SqlBase::Config.new(:options => {
	                        :host => '192.168.10.50',
							:login => 'root',
							:passwd => 'Hawaii',
							:db => 'nexopia' } );

	testdb = SqlDBmysql.new( 'test', 0, working_config );


benchmark("Total"){
	benchmark("select version()"){
		test_num.times {
			res = testdb.query("SELECT VERSION()");
			while res.fetch()
			end
			
		}
	}

	benchmark("CREATE TABLE"){
		testdb.query(
			"CREATE TEMPORARY TABLE temptable (" +
				"id INT(10) NOT NULL AUTO_INCREMENT, " +
				"num INT(10) NOT NULL, " +
				"str TEXT NOT NULL, " +
				"PRIMARY KEY (id)" +
			")" );
	}

	benchmark("insert rows"){
		str = randstr();
		test_num.times {
			testdb.query("INSERT INTO temptable SET num = ?, str = ?", rand(1000000), str);
		}
	}

#	benchmark("insert rows"){
#		test_num.times {
#			testdb.query("INSERT INTO temptable SET num = #{rand(1000000)}, str = '#{randstr()}'");
#		}
#	}


	benchmark("check existence of a row"){
		test_num.times {
			res = testdb.query("SELECT id FROM temptable WHERE id = #", rand(test_num)+1);
			while res.fetch()
			end
		}
	}

	benchmark("return a full row"){
		test_num.times {
			res = testdb.query("SELECT * FROM temptable WHERE id = #", rand(test_num)+1);
			while res.fetch()
			end
		}
	}

	benchmark("return first 10 rows"){
		test_num.times {
			res = testdb.query("SELECT * FROM temptable LIMIT 10");
			while res.fetch()
			end
		}
	}

	benchmark("return random 10 rows"){
		test_num.times {
			res = testdb.query("SELECT * FROM temptable LIMIT #,10", rand(test_num/10));
			while res.fetch()
			end
		}
	}

	benchmark("return all rows 10 times"){
		10.times {
			res = testdb.query("SELECT * FROM temptable");
			while res.fetch()
			end
		}
	}

	benchmark("return all rows 10 times async"){
		10.times {
			res = testdb.query("SELECT * FROM temptable") { |row|

			}
		}
	}

	testdb.close
}