lib_require :Payment, 'voucher'
module VoucherPayment
	def process_payment(data)
		voucher = Voucher.find(:first, :conditions => ["secret = ?", data[:secret]]);
		if (voucher && voucher.usable?)
			if (self.id == 0)
				self.store();
			end
			value = voucher.use(self.id, 200);
			self.amount_approved += value;
			#the amount isn't going to change, if it wasn't right the basket will need another payment created
			self.amount_pending = 0;
			self.store();
		end	
		complete_payment();
	end
end
