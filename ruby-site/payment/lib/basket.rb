lib_require :Payment, 'item', 'voucher_payment'
lib_require :Core, 'pagehandler';

require 'stringio'

class Basket < Storable
	self.enums[:state] = {:open => 0, :pending => 1, :completed => 2}
	init_storable(:shopdb, 'baskets');
	attr_reader(:contents, :payments);

	def initialize(*args)
		super(*args);
		@contents = OrderedMap.new;
		@payments = OrderedMap.new;
	end

	def after_load
		@contents = Item.find(:promise, :conditions => ["basketid = ?", self.id]);
		@payments = Payment.find(:promise, :conditions => ["basketid = ?", self.id])
	end

	def before_update
		self.modification += 1;
	end

	def after_update
		store_contents();
		store_payments();
	end

	def after_create
		self.contents.each {|item|
			item.basketid = self.id;
		}
		self.payments.each {|payment|
			payment.basketid = self.id;
		}
		store_contents();
		store_payments();
	end

	def after_delete
		self.contents.each {|item| item.delete();}
	end

	def store_contents
		if (evaluated?(contents))
			contents.each { |item|
				item.store();
			}
		end
	end

	def store_payments
		if (evaluated?(payments))
			payments.each { |payment|
				payment.store();
			}
		end
	end

	# Add an item to the basket, replace any identical item in the basket.
	def add_item(item)
		item.basketid = self.id;
		self.contents.each {|content|
			if (content === item)
				content.merge(item);
				return;
			end
		}
		self.contents << item;
		return;
	end

	def display()
		out = StringIO.new;
		out.puts("<form action=/shop/update_basket>");
		self.contents.each {|item|
			out.puts(item.display());
			out.puts("<br>");
		}
		out.puts("<input type=submit value=\"Update Basket\"><br></form>");
		out.puts("Total Cost: #{self.total_cost}<br/>Amount Paid: #{self.total_paid}")
		out.puts "<form action=\"/shop/purchase\">"
		out.puts("<input type=\"hidden\" name=\"basketid\" value=\"#{self.id}\">");
		out.puts "Voucher Key: <input type=\"text\" name=\"secret\"><br/><input type=\"submit\" value=\"Complete Purchase\">"
		out.puts "<input type=\"hidden\" name=\"modification\" value=\"#{self.modification}\"></form>"
		
		puts out.string;
	end

	#return the total value of all items in the basket
	def total_cost()
		total = 0;
		self.contents.each {|item|
			total += item.price*item.quantity;
		}
		return total;
	end
	
	#return the amount of money approved on the current basket.
	def total_paid()
		paid = 0;
		payments.each {|payment|
			paid += payment.amount_approved;
		}
		return paid;
	end

	def process_payments()
		if (self.total_paid >= total_cost - 0.005)
			self.state = :completed;
			contents.each { |item|
				item.complete();
			}
			store();
		end
	end

	class << self
		#If the user has an incomplete basket return it, otherwise create a new one.
		def current_basket()
			basket = PageHandler.current[:current_basket];
			if (!basket)
				basket = self.find(:first, :conditions => ["uid = # && state = ?", PageHandler.current.session.userid, enums[:state][:open]]);
			end
			if (!basket)
				basket = Basket.new;
				basket.uid = PageHandler.current.session.userid;
			end
			PageHandler.current[:current_basket] = basket;
			return basket;
		end
		
		#Return an ordered map of all baskets in the pending state.
		def pending_baskets()
			return self.find(:conditions => ["uid = # && state = ?", PageHandler.current.session.userid, enums[:state][:pending]]);
		end
	end
end
