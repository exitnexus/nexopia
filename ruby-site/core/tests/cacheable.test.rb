# tests Cacheable
lib_require :Core, 'storable/cacheable';
lib_require :Devutils, 'quiz'

class Foo < Cacheable
	set_cache($site.memcache);
	set_db(:test50);
	set_table("test");
	init_cacheable();

	self.db.query("DELETE FROM #{table} WHERE 1 = 1")
end

class TestCacheable < Quiz
	def setup
		Foo.db.query("DELETE FROM #{Foo.table} WHERE 1 = 1")
		Foo.db.query("INSERT INTO #{Foo.table} SET id=1, id2=2, content='some content', enumtest='bar', booltest='y'")
	end
	def teardown
		Foo.db.query("DELETE FROM #{Foo.table} WHERE 1 = 1")
	end

	def test_cache
		assert(1 == Foo.db.query("SELECT * FROM #{Foo.table}").num_rows);
		#This should cache the item
		foo = Foo.find(1,2).first
		#delete from db
		Foo.db.query("DELETE FROM #{Foo.table} WHERE 1 = 1")
		Storable.reset_internal_cache();
		#load the item from cache
		foo2 = Foo.find(1,2).first;
		assert(foo == foo2);
		assert(0 == Foo.db.query("SELECT * FROM #{Foo.table}").num_rows);
		foo.delete();
		assert(nil == Foo.find(1,2).first);
	end
end
