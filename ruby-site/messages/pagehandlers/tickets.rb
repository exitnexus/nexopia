lib_require :Core, 'template/template'
require 'stringio'

class Messages < PageHandler

	def initialize(*args)
		super(*args);
		@dump = StringIO.new;
	end

	declare_handlers("tickets") {
		area :Self
		access_level :Any
		
		#create/TypeID/PrimaryID/SecondaryID
		page :GetRequest, :Full, :create_ticket, "create"
		page :GetRequest, :Full, :create_ticket, "create", input(Integer), input(Integer), input(Integer)
		page :PostRequest, :Full, :send_ticket, "send"
		
		area :Public #TODO: Some sort of access level here or in view ticket
		#page :GetRequest, :Full, :view_ticket, input(Integer), input(Integer)
		page :GetRequest, :Full, :list_tickets, "list"
		page :GetRequest, :Full, :edit_ticket, input(Integer), input(Integer)		
		page :PostRequest, :Full, :update_ticket, "update"
	}
	
	def create_ticket(type_id=0, primary_id=0, secondary_id=0)
		t = Template::instance("messages", "create_ticket");
		t.to = Ticket::DEFAULT_MAILBOX;
		t.handler_root = "/my/tickets";
		t.typeid = type_id;
		t.primaryid = primary_id;
		t.secondaryid = secondary_id;
		puts t.display;
	end
	
	def view_ticket(userid, threadid)
		ticket = Ticket.find(:first, userid, threadid);
		puts "<div style=\"background-color:white\">";
		ticket.html_dump(@dump);
		puts @dump.string;
		puts "</div>";
	end
	
	def edit_ticket(userid, threadid)
		ticket = Ticket.find(:first, userid, threadid);
		t = Template::instance("messages", "edit_ticket");
		t.ticket = ticket;
		ticket.status.html_dump(@dump);
		t.status_open = (ticket.status == :open);
		t.status_closed = (ticket.status != :open);
		ticket.html_dump(@dump);
		#t.dump = @dump.string;
		t.handler_root = "/tickets";
		puts t.display();
	end
	
	def update_ticket()
		ticket = Ticket.find(:first, params['userid', Integer], params['threadid', Integer]);
		if (!ticket.nil?)
			ticket.notes = params['notes', String];
			ticket.problem = params['problem', String];
			ticket.status = params['status', Symbol];
			ticket.store();
			edit_ticket(ticket.userid, ticket.threadid);
		end
	end
	
	def send_ticket
		message = nil;
		replyid = params['replyid', Integer, 0];
		if (replyid != 0)
			original = Message.load(replyid);
			message = original.reply();
			original.store();
		else
			message = Message.new;
		end
		to = User.get_by_name(params['to', String]);
		message.sender = PageHandler.current.session.user;
		message.receiver = to;
		message.subject = params['subject', String];
		message.text = params['body', String];
		ticket = message.open_ticket();
		ticket.typeid = params['typeid', Integer];
		ticket.primaryid = params['primaryid', Integer];
		ticket.secondaryid = params['secondaryid', Integer];
		ticket.initialize_notes();
		ticket.problem = params['subject', String];
				
		message.send();
		puts "<div style=\"background-color:white\">";
		ticket.html_dump(@dump);
		message.html_dump(@dump);
		puts @dump.string;
		puts "</div>";
	end
	
	def list_tickets
		tickets = Ticket.find(:all);
		t = Template::instance('messages', 'list_tickets');
		t.tickets = tickets;
		puts t.display();
	end
end
