lib_require :Orwell, 'notifications_sent', 'send_email', 'register'
lib_require :Core, 'constants'
lib_require :Messages, 'message'
lib_require :Bbcode, 'bbcodemodule'

module Orwell
	# Notify the user when they have 8 or fewer days of plus remaining.  Only send the message once.
	class PlusExpiry
		extend TypeID
		orwell_constraint :matches?
		orwell_action :perform_action
		
		def self.matches?(user)
			return false if (user.frozen? || user.state == "deleted")
			
			now = Time.now.to_i()
			if ( ((user.premiumexpiry - now) < (8 * Constants::DAY_IN_SECONDS) && (user.premiumexpiry - now) > 0) )
				# Already notified user?
				result = NotificationsSent::when_sent(user.userid, self.typeid)
				return !result
			end
			return false
		end
		
		def self.perform_action(user)
			return false if (user.nil?)

			if (!NotificationsSent::add_sent(user.userid, self.typeid))
				return false
			end

			message = Message.new
			message.sender_name = "Nexopia"
			message.receiver = user
			message.subject = "Not Much Plus Left!"
			message.text = Wiki::from_address(url/:SiteText/:plus/:remindermsg).get_revision.content;
			message.send();
			
			if (user.fwsitemsgs && !user.email.nil?)
				Message.forward_site_message(user.userid, message.subject, BBCode.parse(message.text), message.fromname)
			end
		end
	end
	
end
