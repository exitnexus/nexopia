lib_require :Payment, 'product'
lib_require :Devutils, 'quiz'

class TestProduct < Quiz
	def setup
		return;
	end

	def teardown
		return;
	end

	def test_creation
		assert_nothing_raised {p = Product.new}
	end
	
	def test_process_input
		p = Product.new;
		p.validinput = "";
		assert_equal(1234, p.process_input("1234"));
		p.validinput = "get_uid";
		assert_equal(200, p.process_input("LogicWolfe"));
		assert_raise(SiteError) {p.process_input("this.is.not.a.valid.user1L9aj0ou93J)(Jas");}
	end
	
end
