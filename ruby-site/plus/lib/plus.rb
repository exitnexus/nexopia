lib_require :Core, "users/user", "constants"
lib_require :Plus, "plus_log"
lib_require :Wiki, "wiki"

# This is a port of a portion of the Plus code from the PHP side.
# It currently only does a few things so that we can do a couple of Plus
# related things without porting all the Plus code over.

module Plus

	FREE_PLUS = 2

	# Mapping of the price to the number of months of Plus it's worth.
	PLUS_QUANTITIES = {
		2  => (7.00/31), # Any of the free 'week' of Plus cards is worth $2.
		2.99 => (14.00/31), # U.S. funds
		3  => (14.00/31), # Mobile payments fixed at $3, buys two weeks
		4.95 => 1, # Paymentpin 1-900
		5  => 1,
		9.95 => 2, # Paymentpin 1-900
		10 => 2,
		15 => 3,
		20 => 6,
		30 => 12,
	}	
	
class Plus
	
	PLUS_MONTH = 31
	
	def self.generate_batch(batch_size, dollar_value)
		 
	end # def self.generate_batch(batch_size, dollar_value)
	
	# Generate a new Plus code
	def self.genKey()
		
		validchars = "ABCDEFGHJKLMNPQRSTUVWXYZ"
		validnums = "123456789"
		
		key = ""

		# Each Plus code is made up of 3 sections.
		# Each section has four characters starting with a capital letter 
		# and ending with 3 digits.		
		3.times { |i| 
			key += "-" if (i == 0)
			key += validchars[rand(validchars.length)]
			3.times { key += validnums[rand(validnums.length)] }
		}
		
		return key
		
	end # def self.genKey()
	
	def assign_batch(batches, store, invoice)
		
	end # def assign_batch(batches, store, invoice)
	
	def activate_batch(batches)
		
	end # def activate_batch(batches)
	
	# for the original code see include/plus.php
	def self.add_plus(userid, duration, fromid, trackid, adminid = 0,
		send_msg = false)
		
		duration_seconds = (duration * (Constants::DAY_IN_SECONDS * PLUS_MONTH)).floor

		# We're doing this by hand to avoid the condition where we get the user object, update premiumexpiry, then save it,
		# but in the mean time some other process has gone and updated the expiry as well.  The store would then overwrite the
		# other value.
		current_time = Time.now().to_i
		User.db.query("UPDATE users SET premiumexpiry = GREATEST(premiumexpiry, ?) + ? WHERE userid = #", current_time, duration_seconds, userid)
		
		user = User.find(:first, userid)
		user.invalidate
		
		log = PlusLog.new
		
		log.userid = userid
		log.time = current_time
		log.from = fromid
		log.to = userid
		log.admin = 0 # This function doesn't ever have to deal with admins adding Plus
		log.duration = duration_seconds
		log.trackid = trackid

		log.store
		
		if (send_msg)
			content = Wiki::from_address(
				url/:SiteText/:plus/:addmsg).get_revision.content
			content.gsub!(/%duration%/, "#{(duration * 31.to_i)}")

			message = Message.new
			message.sender_name = "Nexopia"
			message.receiver = user
			message.subject = "You've got Plus!"
			message.text = content
			message.send()
		end
	end # class Plus
	
	# This is changed from the Php side to return two values.
	# The first is the formatted 'secret' value, the second is
	# the value, or false if there is no value.
	# Call it as secret, value = Plus::cardValue(secret)
	def self.cardValue(secret)
		secret = secret.to_s.strip
		secret = secret.upcase
		secret.gsub!(/[^A-Z0-9-]/, '')
		secret.gsub!(/^([A-Z0-9]{4})-?([A-Z0-9]{4})-?([A-Z0-9]{4}).*$/,
		 	'\\1-\\2-\\3')

		unless secret =~ /^[A-Z][0-9]{3}-[A-Z][0-9]{3}-[A-Z][0-9]{3}$/
			return nil, false
		end

		res = $site.dbs[:shopdb].query("SELECT secret, paygcards.value
			FROM paygcards, paygbatches
			WHERE paygcards.batchid = paygbatches.id && secret = ?
	 		&& usedate = 0 && activatedate > 0 && assigndate > 0
	 		&& valid='y' LIMIT 1", secret)

		res.each { |line|
			return line['secret'], line['value']
		}

		return nil, false
	end # def self.cardValue(secret)

end # class Plus

end # module Plus

# Original code from PHP
# function generateBatch($num, $value){
# 
# 	$time = time();
# 
# 	$this->db->begin();
# 
# 	$this->db->prepare_query("INSERT INTO paygbatches SET gendate = #, value = ?, num = #", $time, $value, $num);
# 
# 	$batchid = $this->db->insertid();
# 
# 	$parts = array();
# 
# 	for($i=0; $i < $num; $i++){
# 		$code = $this->genKey();
# 
# 		$parts[] = $this->db->prepare("(?, #, ?)", $code, $batchid, $value);
# 	}
# 
# 	$this->db->query("INSERT INTO paygcards (secret, batchid, value) VALUES " . implode(", ", $parts) );
# 
# 	$this->db->commit();
# 
# 	return $batchid;
# }
# 
# function genKey(){
# 	$str = "";
# 
# 	for($i=0; $i < 3; $i++){
# 		if($i)
# 			$str .= "-";
# 		$str .= $this->validchars[rand(0,strlen($this->validchars)-1)];
# 		for($j=0; $j<3; $j++)
# 			$str .= $this->validnums[rand(0,strlen($this->validnums)-1)];
# 	}
# 	return $str;
# }
# 
# function assignBatch($batches, $store, $invoice){
# 
# 	$this->db->begin();
# 
# 	$this->db->prepare_query("UPDATE paygbatches SET storeid = #, assigndate = #, storeinvoiceid = ? WHERE id IN (#) && assigndate = 0", $store, time(),  $invoice, $batches);
# 
# 	if($this->db->affectedrows() != count($batches)){
# 		$this->db->rollback();
# 		return false;
# 	}
# 
# 	$this->db->commit();
# 	return true;
# }
# 
# function activateBatch($batches){
# 	$this->db->prepare_query("UPDATE paygbatches SET activatedate = # WHERE id IN (#) && activatedate = 0", time(),  $batches);
# }
# 
#function cardValue(& $secret){ //pass by reference to fix it
#
#	$secret = trim($secret);
#	$secret = strtoupper($secret);
#	$secret = preg_replace("/[^A-Z0-9-]/", "", $secret);
#	$secret = preg_replace("/^([A-Z0-9]{4})-?([A-Z0-9]{4})-?([A-Z0-9]{4}).*$/", "\\1-\\2-\\3", $secret);
#
#	if(!ereg('^[A-Z][0-9]{3}-[A-Z][0-9]{3}-[A-Z][0-9]{3}$', $secret, $regs))
#		return false;
#
#	$res = $this->db->prepare_query("SELECT secret, paygcards.value FROM paygcards, paygbatches WHERE paygcards.batchid = paygbatches.id && secret = ? && usedate = 0 && activatedate > 0 && assigndate > 0 && valid='y'", $secret);
#
#	$line = $res->fetchrow();
#	if($line){
#		$secret = $line['secret'];
#
#		return $line['value'];
#	}
#
#	return false;
#}
