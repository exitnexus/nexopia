lib_require :Payment, 'product', 'basket', 'payment', 'mail_payment'

class Shop < PageHandler
	declare_handlers("shop") {
		area :Public
		access_level :Any
		handle :GetRequest, :shop_front
		handle :GetRequest, :add_to_basket, "add"
		handle :GetRequest, :session_test, "session"
		handle :GetRequest, :empty_basket, "empty_basket"
		handle :GetRequest, :update_basket, "update_basket"
		handle :GetRequest, :view_pending, "view_pending"
		handle :GetRequest, :purchase, "purchase"
		handle :GetRequest, :mail_payments, "mail_payments"
		handle :GetRequest, :complete_payments, "complete_payments"
	}

	def shop_front(*args)
		puts "Welcome to Nexopia's Shop!<br>";
		puts "Your current basket:<br>"
		basket = Basket.current_basket;
		basket.display();

		puts "<form action=/shop/add>";
		Product.find(:all).each {|product|
			product.display()
		}
		puts "<input type=\"submit\" value=\"Add to Basket\">";
		puts "<input type=\"hidden\" name=\"modification\" value=\"#{basket.modification}\"></form>"
		puts "<form action=\"/shop/empty_basket\"><input type=\"submit\" value=\"Empty Basket\">"
		puts "<input type=\"hidden\" name=\"modification\" value=\"#{basket.modification}\"></form>"
		puts "<form action=\"/shop/purchase\">"
		puts "Voucher Key: <input type=\"text\" name=\"secret\"><br/><input type=\"submit\" value=\"Complete Purchase\">"
		puts "<input type=\"hidden\" name=\"modification\" value=\"#{basket.modification}\"></form>"
		
	end

	def add_to_basket(*args)
		verify_modification();
		
		quantities = Hash.new;
		inputs = Hash.new;
		params.each { |key|
			case key
			when /^quantity_(\d)+$/
				quantities[$1.to_i] = params[key, Integer, 0] if (params[key, Integer, 0] > 0);
			when /^input_(\d)+$/
				inputs[$1.to_i] = params[key, String, ""];
			end
		}

		product_array = Product.find(quantities.keys);
		products = Hash.new;
		product_array.each { |product|
			products[product.id] = product;
		}

		quantities.each_pair { |product_id, quantity|
			item = Item.new;
			item.basketid = basket.id;
			item.type = product_id;
			item.quantity = quantities[product_id];
			item.input = inputs[product_id];
			begin
				item.subtype = products[product_id].process_input(inputs[product_id]);
				basket.add_item(item);
			rescue
				puts "<strong>#{inputs[product_id]} is not a valid input for #{item.product.name}</strong><br/>";
			end
		}
		basket.store();
		puts "Basket updated!";
		shop_front(*args);
	end

	def empty_basket(*args)
		verify_modification();
		
		basket.delete();
		puts "Basket emptied!";
		shop_front(*args);
	end
	
	def update_basket(*args)
		verify_modification();
		
		basket.contents.each { |item|
			quantity = params["quantity_#{item.type}_#{item.subtype}", Integer, nil];
			if (quantity)
				if (quantity <= 0)
					basket.contents.delete(item);
				elsif (quantity > 0)
					item.quantity = quantity;
				end
			end
		}
		basket.store();
		shop_front(*args);
	end
	
	def purchase(*args)
		verify_modification();
		basket.state = :pending;
		secret = params['secret', String];
		
		#assume Mail Payment for now.
		payment = Payment.new
		payment.type = VoucherPayment;
		basket.payments << payment;
		payment.basketid = basket.id;
		payment.uid = session.userid;
		payment.amount_pending = basket.total_cost - basket.total_paid;
		payment.process_payment(:secret => secret);
	end
	
	def mail_payments(*args)
		mail = Payment.find(:conditions => ["completed = 'n'"]);
		#OrderedMap.new(:type, type, :amount_pending, amount_pending, :amount_approved, amount_approved, :basketid, basketid);
		puts "<form action=\"/shop/complete_payments\">";
		puts "<table border=\"1\"><tr><th>Type</th><th>Amount Pending</th><th>Amount Approved</th><th>Basket ID</th><th>Complete</th></tr>"
		mail.each { |payment| 
			puts "<tr>"
			payment.to_map.each { |td|
				puts "<td>#{td}</td>"
			}
			puts "<td><input type=\"checkbox\" name=\"payment_#{payment.id}\"></td>"
			puts "</tr>"
		}
		puts "</table><input type=\"submit\" value=\"Complete Payments\"></form>"
	end
	
	def complete_payments(*args)
		payments = [];
		params.each_pair(String) {|key, param|
			if (key =~ /^payment_(\d+)/ && param == "on")
				payments << $1;
			end
		}
		payments = Payment.find(*payments);
		payments.each {|payment|
			payment.pay(payment.amount_pending);
			payment.store();
		}
	end
	
	def view_pending(*args)
		baskets = Basket.pending_baskets;
		if (!baskets)
			puts "There are no pending baskets.";
		else
			baskets.each {|basket|
				basket.display();
			}
		end
	end
	
	private
	def verify_modification
		if (params['modification', Integer, 0] != basket.modification)
			puts "<strong>Basket has been changed since you last viewed it.  No action taken.</strong><br>";
			shop_front();
			throw :page_done
		end
	end
	
	def basket
		if (!@basket)
			if (!params['basketid', Integer])
				@basket = Basket.current_basket;
			else
				@basket = Basket.find(:first, params['basketid', Integer]);
			end
		end
		return @basket;
	end
end