lib_require :Core, 'storable/storable'
lib_require :Plus, 'plus', 'invoice_item'

module Plus
class Invoice < Storable
	init_storable(:shopdb, 'invoice')
	
	relation :multi, :invoice_items, :id, InvoiceItem, :index => :invoiceid
	
	GST_PERCENT = 0.05
	GST_PRODUCTID = 4 # From the database table shop.products
	PLUS_PRODUCTID = 1 # From the database table shop.products
	
	# user_amounts is a hash of userids to the amounts that those users should receive 
	# charge_gst is true if we should add GST to this invoice
	def self.create(purchaser_id, user_amounts, charge_gst)
		
		invoice = Invoice.new()
		
		total = 0
		user_amounts.each_value { |value|
			total += value
		}
		
		total += (total * GST_PERCENT) if (charge_gst)
		
		invoice.userid = purchaser_id
		invoice.creationdate = Time.now().to_i
		invoice.total = total
		
		# Gets the invoice id for us.
		invoice.store()
		
		user_amounts.each { |userid, amount|
			
			item = InvoiceItem.new
			item.invoiceid = invoice.id
			item.productid = PLUS_PRODUCTID # We can only purchase plus at this point.
			item.quantity = PLUS_QUANTITIES[amount] # number of months of Plus
			item.price = amount.to_f/(PLUS_QUANTITIES[amount]).to_f # calculate the price per month 
			# I'm sure there's some explanation as to why this is called input instead of userid.  
			# Some PHP side code stores the username, but does a check later to convert it to a userid
			item.input = userid
			
			item.store
			
		}
		
		# FIXME: I'm sure there's a better way to force the Storable object to check the database for changes.
		# If we don't fetch it from the database it won't have the invoice items attached.
		return Invoice.find(:first, invoice.id)
		
	end # def self.create(purchaser_id, user_amounts, charge_gst)
	
	def self.update(invoice_id, payment_method, payment_contact, amount_paid, transaction_id, complete)
		
		invoice = Invoice.find(:first, invoice_id)

		invoice.paymentmethod = payment_method
		invoice.paymentcontact = payment_contact
		invoice.paymentdate = Time.now.to_i
		invoice.amountpaid = amount_paid		
		invoice.txnid = transaction_id
		
		invoice.store
		
		output = ""
		if(complete)
			output = self.complete(invoice_id)
		end
		
		return output
		
	end # def update(invoice_id, payment_method, payment_contact, amount_paid, transaction_id, complete)
	
	# If we set send_msg to true, we'll send a Nexopia message to each user
	# who receives plus.
	def self.complete(invoice_id, send_msg = false)
	
		# This is a terrible way to do this, but transactions aren't supported on the table
		# type that we're using right now.  The proper solution is to change the table type to InnoDB
		# and use transactions.
		
		$site.dbs[:shopdb].query("LOCK TABLES invoice WRITE")
		
		# FIXME: This may not deal properly with whatever race condition the PHP side is trying to avoid.
		invoice = Invoice.find(:first, invoice_id, :conditions => "completed = 'n'")
		
		if( invoice.nil? )
			$site.dbs[:shopdb].query("UNLOCK TABLES")			
			return "Already complete"
		end
		
		invoice.completed = true
		invoice.store

		$site.dbs[:shopdb].query("UNLOCK TABLES")			
		
		invoice.invoice_items.each { |item|
			
			userid = item.input.to_i # The input column can be a username or a userid, but for all the Ruby side invoices it's going to be an integer.
			duration = item.quantity
			fromid = invoice.userid
			trackid = invoice.id
			
			Plus.add_plus(userid, duration, fromid, trackid, 0, send_msg)
		}

	end # def complete(invoice)
	
end # class Invoice < Storable

end # module Plus
