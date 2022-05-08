require 'stringio'
lib_require :payment, 'product', 'product_prices', 'product_input_choice';
#An item is an individual instance of a product including any subtype specifications.
#Each item belongs to a single basket.  An item may have a quantity greater than 1.
class Item < Storable
	init_storable(:shopdb, 'basketcontents');
	
	def initialize(*args)
		super(*args)
		@product = nil;
		@choice = nil;
	end
	
	def product
		if (!@product)
			@product = Product.find(self.type).first;
		end
		return @product;
	end
	
	def prices
		if (!@prices)
			@prices = { 0 => self.product.unitprice }
			if (self.product.bulkpricing)
				product_prices = ProductPrices.find(:all, :conditions => ["productid = #", self.type])
				product_prices.each { |price| 
					@prices[price.minimum] = price.price;
				}
			end
		end
		return @prices;
	end
	
	
	def choice
		if (!@choice)
			@choice = ProductInputChoice.find(self.subtype, :conditions => ["productid = ?", self.type]).first;
		end
		return @choice;
	end
	
	def merge(obj)
		return unless obj.respond_to?(:quantity);
		self.quantity += obj.quantity;
	end
	
	def replace(obj)
		return unless obj.respond_to?(:quantity) && 
		              obj.respond_to?(:basketid) && 
		              obj.respond_to?(:type) &&
		              obj.respond_to?(:subtype) &&
		              obj.respond_to?(:input);
		self.quantity = obj.quantity if (self.quantity != obj.quantity);
		self.basketid = obj.basketid if (self.basketid != obj.basketid);
		self.type = obj.type if (self.type != obj.type);
		self.subtype = obj.subtype if (self.subtype != obj.subtype);
		self.input = obj.input if (self.input != obj.input);
	end
	
	def display()
		out = StringIO.new;
		case product.input
		when "mc";
			out.puts("#{product.name} #{choice.name} <input type=\"text\" name=\"quantity_#{type}_#{subtype}\" value=\"#{quantity}\"");
		when "text";
			out.puts("#{product.name} #{input} <input type=\"text\" name=\"quantity_#{type}_#{subtype}\" value=\"#{quantity}\"");
		end
		return out.string;
	end
	
	def price()
		closest_min = 0;
		best_price = product.unitprice;
		prices.each_pair { |min, price|
			if (min >= closest_min && min <= quantity)
				best_price = price;
			end
		}
		return best_price;
	end
	
	def complete
		if (product.callback != "" && self.class.private_method_defined?(product.callback))
			self.send(product.callback.to_sym)
		else
			puts "No working callback for item #{type}:#{subtype}:#{input}<br>";
		end
	end
	
	private
	
	#Callbacks for completing the purchase of an item
	def purchase_plus
		puts "This should purchase #{self.quantity} units of plus for #{self.input}, userid:#{self.subtype}<br>";
	end
end
