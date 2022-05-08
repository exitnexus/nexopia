lib_require :Core, 'storable/storable'
lib_require :Payment, 'mail_payment', 'basket'

class Payment < Storable
	init_storable(:shopdb, 'payments');

	attr_reader(:basket, :user);
	
	def initialize(*args)
		super(*args);
		@creation_time = Time.now.to_i;
	end
	
	#Takes a string, symbol or module and extends the object with it.
	#Passing in an object of type module is significantly more efficient.
	def type=(klass)
		if (!klass.instance_of?(Module))
			ObjectSpace.each_object(Module) { |mod|
				if (mod.name == klass.to_s)
					klass = mod;
				end
			}
		end
		if (klass.instance_of?(Module))
			self.extend(klass);
		end
		@type = klass.to_s;
	end

	def uid=(id)
		@uid = id;
		@user = User.find(id, :first, :promise => true)
	end

	

	def basketid=(id)
		@basketid = id;
		@basket = Basket.find(id, :first, :promise => true);
	end
	
	def to_map
		return OrderedMap.new(:type, type, :amount_pending, amount_pending, :amount_approved, amount_approved, :basketid, basketid);
	end
	
	def pay(amount)
		self.amount_pending -= amount;
		self.amount_approved += amount;
		if (amount_pending <= 0.005)
			complete_payment();
		end
	end
	
	def process_payment(*data);
		raise SiteError, "Unimplemented payment processing function called.";
	end
	
	def complete_payment
		if (self.amount_pending <= 0.005)
			self.completed = true;
			completion_time = Time.now.to_i;
			self.store();
			self.basket.process_payments();
		end
	end
	
end
