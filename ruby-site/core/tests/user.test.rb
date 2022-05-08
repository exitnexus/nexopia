# tests User
lib_require :Core, "users/user";
lib_require :Devutils, 'quiz'

class TestUser < Quiz
	def setup
		@id = 203;
		@username = "thomahawk";
		@testuser = User.get_by_id(@id);
	end

	def test_validate()		
		assert_equal(@testuser.username, @username);
		assert_equal(@id, User.get_by_name(@username).userid);
	end

	def test_uri
		expected_uri = "/users/#{@username}"
		assert_equal(expected_uri,@testuser.to_uri)
	end
	def test_uri_info
		expected_uri_info = [@username,"/users/#{@username}"]
		assert_equal(expected_uri_info,@testuser.uri_info)
	end

	#
	# This test relies on the User and Friend Object. 
	def test_friend_add_remove
		Storable.use_test_db {
			Friend.db.query("TRUNCATE TABLE #{Friend.table}")
			User.db.query("TRUNCATE TABLE #{User.table}")
			users = [User.new, User.new]
			users[0].userid = 213
			users[1].userid = 211
			users[0].store
			users[1].store
			me = User.new
			me.userid = 215
			me.store
			#
			# Ensure they are gone
			#
			Friend.db.query("DELETE FROM #{Friend.table} WHERE userid = #",me.userid)		
			# Verify Through Raw Query
			#
			assert_equal(0, Friend.db.query("SELECT * FROM #{Friend.table} WHERE userid = #",me.userid).num_rows, "Result should be Empty");
			#
			users.each {|u|
				assert_nothing_raised {
					me.remove_friend(u.userid)
				}
			}
			#
			# Verify Through Raw Query
			#
			assert_equal(0, Friend.db.query("SELECT * FROM #{Friend.table} WHERE userid = #",me.userid).num_rows, "Result should be Empty");
			#
			# then add them
			#
			users.each {|u|
				assert_nothing_raised {
					me.add_friend(u.userid)
				}
			}
	
			assert_equal(2, Friend.db.query("SELECT * FROM #{Friend.table} WHERE userid = #", me.userid).num_rows, "Result should be Size 2");
			#
			# then remove them again
			#
			users.each {|u|
				assert_nothing_raised {
					me.remove_friend(u.userid)
				}
			}
			assert_equal(0, Friend.db.query("SELECT * FROM #{Friend.table} WHERE userid = #",me.userid).num_rows, "Result should be Empty");
			Friend.db.query("TRUNCATE TABLE #{Friend.table}")
			User.db.query("TRUNCATE TABLE #{User.table}")
		}
	end
end

