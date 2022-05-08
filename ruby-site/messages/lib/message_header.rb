lib_require :Core, 'storable/storable'
lib_require :Messages, 'message_text'#, 'ticket'

class MessageHeader < Storable
	#ThreadID = Struct.new("ThreadID", :userid, :threadid);

	init_storable(:usersdb, 'msgs');

	attr(:msgtext);
	attr(:user_from);
	attr(:user_to);

	relation_singular :msgtext, [:userid, :id], MessageText;
	relation_singular :user_from, :from, User;
	relation_singular :user_to, :to, User;
	#relation_singular :ticket, [:threaduserid, :threadid], Ticket;

	def MessageHeader.getStatus(user, status)
        return MessageHeader.find(:all, :conditions => ["userid = # AND folder = 1 AND status = ?", user.userid, status])
	end

	def uri_info(mode = 'self')
		if (mode == 'self')
			return [subject, url/:my/:messages_new/id];
		elsif (mode == 'admin')
			return [subject, "/admin/user/#{userid}/messages/#{id}"];
		end
	end

	def formatted_date()
		return Time.at(self.date).strftime('%b %d @%I:%M%p');
	end
=begin
	def threadid_struct()
		return ThreadID.new(self.threaduserid, self.threadid);
	end

	def threadid_struct=(new_threadid_struct)
		self.threaduserid = new_threadid_struct.userid;
		self.threadid = new_thnew_threadid_struct.threadid;
	end
=end
end
