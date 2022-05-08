lib_require :Payment, 'basket', 'item'
lib_require :Devutils, 'quiz'

class TestBasket < Quiz
	def setup
		return;
	end

	def teardown
		return;
	end

	def test_creation
		assert_nothing_raised {b = Basket.new}
	end
	
	def test_add_item
		b = Basket.new;
		assert(b.contents.empty?);
		b.id = 666;
		item = Item.new;
		item.type = 1;
		item.subtype = 1;
		item.quantity = 1;
		b.add_item(item);
		assert_equal(666, item.basketid);
		assert(!b.contents.empty?);
		item2 = Item.new;
		item2.type = 1;
		item2.subtype = 1;
		item2.quantity = 2;
		b.add_item(item2);
		assert_equal(1, b.contents.length);
		assert_equal(3, b.contents.first.quantity);
		item3 = Item.new;
		item3.type = 2001;
		item3.subtype = 42;
		item3.quantity = 7;
		b.add_item(item3);
		assert_equal(2, b.contents.length);
	end
	
	def test_cost
		b = Basket.new;
		i = Item.new;
		i2 = Item.new;
		i.type = 2;
		i.subtype = 1;
		i.quantity = 1;
		i2.type = 1;
		i2.quantity = 6;
		b.add_item(i);
		b.add_item(i2);
		assert(20+20 < b.total_cost + 0.005 || 20+20 > b.total_cost - 0.005);
	end
	
	def test_current_basket
		if (Object.const_get('PageHandler') && PageHandler.current() && PageHandler.current.session)
			basket = Basket.current_basket;
			assert(Basket.current_basket != nil);
			assert(basket.object_id == Basket.current_basket.object_id);
		else
			puts "WARNING: Basket.current_basket was not tested because no session was found.";
		end
	end
	
end
