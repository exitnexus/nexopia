# tests Authentication/Sessions
lib_require :Core, "users/user", "session";
lib_require :Devutils, 'quiz'

class TestSession < Quiz
	def setup
		@username = "Thomahawk";
		@password = "secret";
		@id = 203;
	end

	def test_validate()
		assert_equal(@id, User.get_by_name(@username).userid);
		assert_equal(true, Password.check_password(@password, @id));
		session = nil;
 		assert_nothing_raised { session = Session.build('127.0.0.1', @id, 'n'); }
		key, userid = session.cookie.value.first.scan(/[^:]+/);
		assert_equal(userid, @id.to_s);
	end

end
