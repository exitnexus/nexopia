lib_require :Core, 'data_structures/ordered_map','users/user';
lib_require :Devutils, 'quiz'

class TestOrderedMap < Quiz
	def test_basic
		map = OrderedMap.new({:zero => "00", :one => "01"});
		assert_equal(map.index(:zero), 0);
		assert_equal(map.at(0), "00");
		assert_equal(map[:zero], "00");
		map[:zero] = "something new";
		assert_equal(map.index(:zero), 0);
		assert_equal(map.at(0), "something new");
		assert_equal(map[:zero], "something new");
		i = 0;
		map.each_with_index{|value, index|
			assert_equal(index, i);
			assert_equal("something new", value) if (i == 0);
			assert_equal("01", value) if (i == 1);
			i += 1;
		}
		i = 0;
		map.each_pair{|key, value|
			assert_equal([:zero], key) if (i == 0);
			assert_equal("something new", value) if (i == 0);
			assert_equal([:one], key) if (i == 1);
			assert_equal("01", value) if (i == 1);
			i += 1;
		}
		map2 = OrderedMap.new({:jack => :jill});
		assert_equal(map2.at(0), :jill);
		map.merge(map2);
		assert_equal(map.at(2), :jill);
		assert_equal(map.index(:jack), 2);
		assert_equal(map[:jack], :jill);

		assert(!map.empty?);
		map.clear();
		assert(map.empty?);

	end
	#
	#
	# If an this ordered map is begin used and two datatypes
	# hash and array are being used and a remove is done on
	# the ordered map then the underlying array indexes can be changed
	#
	# this is here to point towards discussion
	# Nathan says: I discussed this with Curtis and we came to agreement that
	# an ordered map should behave like an array when something is removed, which
	# means indexes can change.
	#
	#def test_at_remove_bug
	#			u1 = User.get_by_id(211)
	#			u2 = User.get_by_id(203)			
	#			map = OrderedMap.new();
	#			map.add(u1,u2)			
	#			u2_index = map.index(u2)					
	#			map.remove(u1)
	#			assert_equal(u2,map.at(u2_index))		
	#end
	def test_add_remove
		u1 = User.new
		u1.userid = 1234
		u2 = User.new
		u2.userid = 5678
		map = OrderedMap.new();
		map.add(u1,u2)			
		assert_equal(2,map.length)
		map.remove(u1,u2)
		assert_equal(0,map.length)
	end
	
	def test_find
		u1 = User.new
		u1.userid = 1234
		u2 = User.new
		u2.userid = 5678
		map = OrderedMap.new();
		map = OrderedMap.new();
		map.add(u1,u2)			
		user_found = map.find() {|u|
			u.userid == 1234
		}
		assert(user_found.nil? == false);
		assert_equal(user_found.userid, 1234);
	end

end
