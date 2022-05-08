class Voucher < Storable
	init_storable(:shopdb, 'paygcards');
	
	#Marks the voucher as used, saves that information to the database, and returns the value that was used.
	def use(invoice_id, uid)
		if (usable?)
			#TODO: Make store ensure that the data on hand is consistent with the data in the database
			#when the update is done.
			self.usedate = Time.now.to_i;
			self.useuserid = uid;
			self.invoiceid = invoice_id;
			self.store(:affected_rows, :conditions => ['usedate = ? && useuserid = ? && invoiceid = ? && valid = ?', 0, 0, 0, true]);
			if (self.affected_rows && self.affected_rows > 0)
				return self.value;
			end
		end
		return 0;
	end
	
	#Checks if the Voucher can currently be used.
	def usable?()
		return (!self.secret.empty? && self.usedate == 0 && self.useuserid == 0 && self.valid);
	end
end
