lib_require :Core, 'typeid'

module Orwell

	class DuplicateNotificationError < Exception
	end
	
	# We use this to keep track of notifications we have sent
	# so as to ensure we don't keep on notifying the same
	# users over and over again.
	class NotificationsSent < Storable
		init_storable(:usersdb, "notifications_sent")
		
		def self.when_sent(userid, typeid)
			notification = NotificationsSent.find(:first, [userid, typeid],
				:order => 'date DESC')
			if (notification == nil)
				return nil
			else
				return notification.date
			end
		end
		
		# Return value is true if the insert succeeded (that is,
		# it's okay to send a notification), false otherwise.
		# Side effect: Inserts a row into the table.
		def self.add_sent(userid, typeid)
			n = NotificationsSent.new
			n.userid = userid
			n.moduleid = typeid
			d = Time.now
			n.date = Time.utc(d.year, d.month, d.day).to_i
			n.store(:ignore, :affected_rows)
			
			if (n.affected_rows == 0)
				raise DuplicateNotificationError.new
			end
			
			return n.affected_rows >= 1 # Should always be 0 or 1
		end

	end
	
end