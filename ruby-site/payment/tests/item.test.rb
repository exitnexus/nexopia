lib_require :Payment, 'item'
lib_require :Devutils, 'quiz'

class TestItem < Quiz
	def setup
		return;
	end

	def teardown
		return;
	end

	def test_creation
		assert_nothing_raised {item = Item.new};
	end
	
	def test_product
		item = Item.new;
		assert(!item.product);
		item.type = 1;
		assert(item.product);
	end
	
	def test_choice
		item = Item.new;
		item.type = 1;
		item.subtype = 1;
		assert(!item.choice);
		item.type = 2;
		assert_equal(item.choice.name, "Red - XL");
	end
	
	def test_merge
		item = Item.new;
		item.quantity = 4;
		item2 = Item.new;
		item2.quantity = 2;
		item.merge(item2);
		assert_equal(6, item.quantity);
		assert_equal(2, item2.quantity);
	end
	
	def test_replace
		item = Item.new;
		item.basketid = 0;
		item.type = 1;
		item.subtype = 2;
		item.quantity = 3;
		item.input = "four";
		item2 = Item.new;
		item2.basketid = 5;
		item2.type = 6;
		item2.subtype = 7;
		item2.quantity = 8;
		item2.input = "nine";
		assert(!(item == item2));
		item.replace(item2);
		assert_equal(5, item.basketid);
		assert_equal(6, item.type);
		assert_equal(7, item.subtype);
		assert_equal(8, item.quantity);
		assert_equal("nine", item.input);
		assert(item == item2);
	end
	
	def test_price
		item = Item.new;
		item.type = 1;
		item.quantity = 1;
		assert_equal(5, item.price);
		item.quantity = 6;
		assert_equal(3.333, item.price)
		item.quantity = 12;
		assert_equal(2.5, item.price);
	end
end
