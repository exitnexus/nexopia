lib_require :Payment, 'product_prices'
lib_require :Devutils, 'quiz'

class TestProductPrices < Quiz
	def setup
		return;
	end

	def teardown
		return;
	end

	def test_creation
		assert_nothing_raised {p = ProductPrices.new}
	end
	
end
