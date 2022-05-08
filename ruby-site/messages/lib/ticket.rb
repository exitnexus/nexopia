=begin Should be in a ticket module?
lib_require :Core, 'storable/storable', 'attrs/enum_map_attr'

class Ticket < Storable
	set_enums(:status => {:open => 0, :closed => 1});
	init_storable(:usersdb, 'msgtickets');

	#relation_multi(:sender, [:userid, :threaduserid, :threadid], MessageHeader, :index => :thread)

	DEFAULT_MAILBOX = "LogicWolfe";


	def open()
		self.status = :open;
	end

	def close()
		self.status = :closed;
	end

	def initialize_notes()
		storable_class = TypeID.get_class(self.typeid);
		object = nil;
		if (storable_class.indexes[:PRIMARY].length > 1)
			object = storable_class.find(self.primaryid, self.secondaryid, :first);
		else
			object = storable_class.find(self.primaryid, :first);
		end
		self.notes = object.ticket_notes;
	end

	def threadid_struct
		t_struct = MessageHeader::ThreadID.new();
		t_struct.userid = self.userid;
		t_struct.threadid = self.threadid;
		return t_struct;
	end

	def threadid_struct=(t_struct)
		self.userid = t_struct.userid;
		self.threadid = t_struct.threadid;
	end

	def uri_info(mode = 'self')
		return [problem, url/:tickets/userid/threadid];
	end

	class << self
		#Create a new ticket given an existing message.
		def create(message_header)
			ticket = Ticket.new();
			ticket.threadid = message_header.threadid;
			ticket.status = :open;
			ticket.userid = message_header.threaduserid;
			return ticket;
		end
	end
end
=end
