lib_require :Payment, 'product_input_choice'
lib_require :Devutils, 'quiz'

class TestProductInputChoice < Quiz
	def setup
		return;
	end

	def teardown
		return;
	end

	def test_creation
		assert_nothing_raised {p = ProductInputChoice.new}
	end
	
end
