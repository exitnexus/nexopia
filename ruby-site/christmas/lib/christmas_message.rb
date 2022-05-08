lib_require :Core, 'storable/storable', 'users/user', 'constants'
lib_require :Messages, 'message'
lib_require :Plus, 'plus_voucher'

class ChristmasMessage < Storable
	init_storable(:contestdb, 'christmas')
	
	relation_singular :user, [:userid], User
	relation_singular :voucher, [:code], PlusVoucher

	def send_message
		begin
			self.store
			message = Message.new;
			message.sender_name = "The Nex Team"
			message.receiver = user
			message.subject = "You Got Plus!"
			text = "Hey #{user.username},\n\n"
			text += "You just got Plus for Xmas!\n\n"
			text += "Yep, you're one of 1000 lucky Nexopia members who got today's special giveaway of one week of free Plus for the 12 Days of Nexmas. \n\n"
			text += "Your Exclusive Plus Pin is: #{voucher.secret}\n\n"
			text += "Enter this Plus Pin in the \"Redeem Your Free Plus\" section at the bottom of the Nexopia Plus page to activate your free week of Plus.\n\n"
			text += "Happy Holidays!\n"
			text += "-- The Nex Team"
			message.text = text
			message.send()
		rescue Object => o
			$log.info o, :error
		end
	end
	
	def csv
		activated = self.voucher.usedate.zero? ? false : Time.at(self.voucher.usedate).to_s
		first_plus_purchase = "N/A"
		months_of_plus_purchased = "N/A"
		if (activated)
			pl = PlusLog.find(self.userid, :userid, :conditions => ["time > ?", self.voucher.usedate], :order => "time ASC")
			if (!pl.empty?)
				first_plus_purchase = (pl.first.time - self.voucher.usedate)/Constants::DAY_IN_SECONDS
				plus_purchased = 0
				pl.each {|log|
					plus_purchased += log.duration
				}
				months_of_plus_purchased = plus_purchased/Constants::MONTH_IN_SECONDS
			end
		end
		
		location = self.user.nil? ? "Unavailable" : self.user.location
		age = self.user.nil? ? "Unavailable" : self.user.age
		sex = self.user.nil? ? "Unavailable" : self.user.sex
		
		return [self.batchid, self.userid, location, age, sex, self.plus, self.active, activated, first_plus_purchase, months_of_plus_purchased].join(',')
	end
	
	class << self
		def csv_headers
			return ["Batch ID", "User ID", "Location", "Age", "Sex", "Had Plus", "Was Active", "Used Voucher", "Days Until First Purchase", "Months Purchased"].join(",")
		end
	end
end