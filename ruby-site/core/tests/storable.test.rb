# tests Storable
lib_require :Core, "storable/storable", "var_dump",'users/user','pagehandler', 'attrs/enum_map_attr';
lib_require :Devutils, 'quiz'

class TestStorableFoo < Storable
	set_db(:test50);
	set_table("test");
	set_enums(:enummaptest => {:zero => 0, :one => 1, :two => 2});
	
	init_storable();
	
	register_selection(:test_select, :id, :booltest);
	register_selection(:test_select2, :id, :id2, :booltest);
	def after_create
		throw :created;
	end

	def after_update
		throw :updated;
	end

	def after_delete
		throw :deleted;
	end

end

class TestStorable < Quiz
	def setup
		TestStorableFoo.db.query("DELETE FROM #{TestStorableFoo.table} WHERE 1 = 1")
		TestStorableFoo.db.query("INSERT INTO #{TestStorableFoo.table} SET id=1, id2=2, content='some content', enumtest='bar', booltest='y'")
	end
	def teardown
		TestStorableFoo.db.query("DELETE FROM #{TestStorableFoo.table} WHERE 1 = 1")
	end

	def test_partial_key
		TestStorableFoo.db.query("INSERT INTO #{TestStorableFoo.table} SET id=5, id2=2, content='some content', enumtest='bar', booltest='y'")
		TestStorableFoo.db.query("INSERT INTO #{TestStorableFoo.table} SET id=5, id2=1, content='some content', enumtest='bar', booltest='y'")
		results = TestStorableFoo.find(1);
		assert(results.length == 1);
		results2 = TestStorableFoo.find(5);
		assert(results2.length == 2);
		assert(results.at(0).id == 1);
		assert(results2.at(0).id == 5);
		assert(results2.at(1).id == 5);
	end

	def test_partial_key_and_condition
		assert_nothing_raised {
			arr = [1];
			hash = {:conditions => ['content = ?', 'some content']};
			results = TestStorableFoo.find(hash, *arr);
			assert(results.length == 1);
		}
	end

	def test_accessors
		foo = TestStorableFoo.new;
		assert_nothing_raised {foo.id}
		assert_nothing_raised {foo.get_primary_key}
	end

	def test_create
		assert(1 == TestStorableFoo.db.query("SELECT * FROM #{TestStorableFoo.table}").num_rows);
		foo = TestStorableFoo.new;
		foo.id = 42;
		foo.id2 = 1;
		assert_throws(:created) {foo.store()};
		assert_throws(:updated) {foo.store()};
		assert(2 == TestStorableFoo.db.query("SELECT * FROM #{TestStorableFoo.table}").num_rows);
		assert(foo ==  TestStorableFoo.find(42,1).first);
	end

	def test_update
		assert(1 == TestStorableFoo.db.query("SELECT * FROM #{TestStorableFoo.table}").num_rows);
		foo = TestStorableFoo.find(1,2).first;
		assert(foo.booltest);
		foo.booltest=false;
		assert(!foo.booltest)
		assert(foo.equal?(TestStorableFoo.find(1,2).first));
		assert_throws(:updated) {foo.store()};
		assert(foo.equal?(TestStorableFoo.find(1,2).first));
	end
	
	def test_delete
		assert(1 == TestStorableFoo.db.query("SELECT * FROM #{TestStorableFoo.table}").num_rows);
		foo = TestStorableFoo.find(1,2).first;
		assert_throws(:deleted) {foo.delete()};
		assert(0 == TestStorableFoo.db.query("SELECT * FROM #{TestStorableFoo.table}").num_rows);
		TestStorableFoo.db.query("INSERT INTO #{TestStorableFoo.table} SET id=5, id2=2, content='some content', enumtest='bar', booltest='y'")
		TestStorableFoo.db.query("INSERT INTO #{TestStorableFoo.table} SET id=5, id2=1, content='some content', enumtest='bar', booltest='y'")
		results = TestStorableFoo.find(5);
		assert_equal(results.length, 2);
		results.each {|result|
			assert_throws(:deleted) {result.delete()};
		}
		assert_equal(TestStorableFoo.find(5).length, 0)
		assert_equal(0, TestStorableFoo.db.query("SELECT * FROM #{TestStorableFoo.table}").num_rows);
	end

	def test_find
		foo = TestStorableFoo.new;
		foo.id = 42;
		foo.id2 = 54;
		foo.booltest = false;
		foo.enumtest = 'foo';
		assert_throws(:created) {foo.store()};
		bar = TestStorableFoo.new;
		bar.id = 31;
		bar.id2 = 13;
		bar.booltest = false;
		bar.enumtest = 'bar';
		assert_throws(:created) {bar.store()};
		baz = TestStorableFoo.new;
		baz.id = 9;
		baz.id2 = 11;
		baz.booltest = true;
		baz.enumtest = 'fubar';
		assert_throws(:created) {baz.store()};
		assert(4 == TestStorableFoo.db.query("SELECT * FROM #{TestStorableFoo.table}").num_rows);
		assert(TestStorableFoo.find(:all, :scan).length == 4)
		assert(TestStorableFoo.find(:all, :conditions => ["enumtest = ?", :bar]).length == 2)
		assert(TestStorableFoo.find(:all, :conditions => "enumtest = 'bar'").length == 2)
		assert(TestStorableFoo.find(:first, :conditions => ["id = ? && id2 = ?", 42, 54]) == foo)
		assert(TestStorableFoo.find(:all, :scan, :order => "id DESC").first == foo)
		assert(TestStorableFoo.find(:all, :conditions => "id != 42", :order => "id DESC").first == bar)
		assert(TestStorableFoo.find(:all, :scan, :group => "enumtest").length == 3)
		assert(TestStorableFoo.find(:all, :limit => 3).length == 3);
		assert(TestStorableFoo.find(:first, baz.id, baz.id2) == baz)
		
		qux = TestStorableFoo.new;
		assert_throws(:created) { qux.store; }
		assert(qux.booltest == true);
		assert(qux.booltest2 == false);
	end

	def test_internal_cache
		assert(1 == TestStorableFoo.db.query("SELECT * FROM #{TestStorableFoo.table}").num_rows);
		foo = TestStorableFoo.find(1,2).first;
		assert(foo.booltest);
		foo.booltest=false;
		assert(!foo.booltest)
		assert(foo.equal?(TestStorableFoo.find(1,2).first));
		Storable.reset_internal_cache();
		assert(!foo.equal?(TestStorableFoo.find(1,2).first));
	end

	def test_promises
		foo = TestStorableFoo.find(1,2, :promise, :first);
		assert(!evaluated?(foo));
		bar = TestStorableFoo.find(2,2, :promise, :first);
		assert(!evaluated?(foo));
		foo = demand(foo);
		assert(evaluated?(foo));
		bar = demand(bar);
		assert(!bar);
		stuff = TestStorableFoo.find(1,2,2,2, :promise);
		assert(!evaluated?(stuff));
		demand(stuff);
		assert(evaluated?(stuff));
	end

	def test_selections
		TestStorableFoo.db.query("INSERT INTO #{TestStorableFoo.table} SET id=1, id2=3, content='some content', enumtest='bar', booltest='y'");
		results = TestStorableFoo.find(1,2,1,3,:refresh, :selection => :test_select)
		assert_equal(2, results.length);
		foo = results.first
		assert_raise(SiteError) {foo.id2};
		assert_raise(SiteError) {foo.enumtest};
		assert_nothing_raised {foo.id}
		assert_nothing_raised {foo.booltest}
		results = TestStorableFoo.find(1,2)
		foo = results.first
		assert_nothing_raised {foo.id2};
		assert_nothing_raised {foo.enumtest};
		assert_nothing_raised {foo.id}
		assert_nothing_raised {foo.booltest}
		results = TestStorableFoo.find(1,2,:refresh, :selection => :test_select2)
		assert_equal(2, results[0].id2);
	end
	
	def test_auto_increment
		ts = nil;
		catch :created do
			ts = TestStorableFoo.new
		end
		ts.id = 10981230;
		
		catch :created do
			ts.store();
		end
		assert_not_equal(10981230, ts.id2);
	end
	
	def test_multiple_partials
		TestStorableFoo.db.query("INSERT INTO #{TestStorableFoo.table} SET id=5, id2=2, content='some content', enumtest='bar', booltest='y'")
		TestStorableFoo.db.query("INSERT INTO #{TestStorableFoo.table} SET id=5, id2=1, content='some content', enumtest='bar', booltest='y'")
		results = TestStorableFoo.find([1],[5])
		assert_equal(3, results.length)
	end
	
	def test_non_primary_key_caching
		foo = TestStorableFoo.find(:id2, [2]).first
		assert !foo.nil?
		begin
			original_db = TestStorableFoo.db
			TestStorableFoo.send(:set_db, nil)
			foo2 = nil
			assert_nothing_raised {
				foo2 = TestStorableFoo.find(:id2, [2]).first
			}
			assert foo.equal?(foo2)
		ensure
			TestStorableFoo.send(:set_db,original_db)
		end
	end
	
	def test_partial_key_caching
		foo = TestStorableFoo.find([1]).first
		assert !foo.nil?
		begin
			original_db = TestStorableFoo.db
			TestStorableFoo.send(:set_db, nil)
			foo2 = nil
			assert_nothing_raised {
				foo2 = TestStorableFoo.find([1]).first
			}
			assert foo.equal?(foo2)
		ensure
			TestStorableFoo.send(:set_db,original_db)
		end
	end
	
	def test_non_constrained_query
		assert_equal(0, Foo.find(:all).length)
		assert_not_equal(nil, Foo.find(:first))
		assert_equal(1, Foo.find(:all, :scan).length)
	end
end