lib_require :Messages, 'message_header', 'message', 'message_folder', 'message_thread';
lib_require :Core, 'template/template'

require 'stringio'

class ThreadedMessages < PageHandler

	def initialize(*args)
		super(*args);
		@dump = StringIO.new
	end

	HANDLER_ROOT = "/my/messages_new";
	
	declare_handlers("messages_new") {
		area :Self
		access_level :Any
		page :GetRequest, :Full, :new_message, "create"
		page :GetRequest, :Full, :default
		page :GetRequest, :Full, :list_folder, "folder", input(Integer)
		page :GetRequest, :Full, :view_message, input(Integer)
		page :GetRequest, :Full, :view_my_thread, input(Integer), input(Integer)
		page :PostRequest, :Full, :send_message, "send"
	}
	
	def default()
		list_folder([MessageFolder::INBOX, MessageFolder::SENT]);
	end
	
	def list_folder(folder_ids)
		folder_ids = [folder_ids] unless (folder_ids.kind_of?(Array));
		
		folders = MessageFolder.all();
		
		messages = MessageHeader.find(:conditions => ["`userid` = # && `folder` IN ?", session.user.userid, folder_ids]);
		threads = {};
		
		#TODO: Merge both sides of a thread into a single thread in an intelligent manner.
		#If it can handle multidepth searches that would be ideal but not necessary.
		#It shouldn't conflict with the idea of being able to manual assign a thread to something.
		#These constraints may potentially be mutually exclusive.
		messages.each {|message|
			threads[[message.threaduserid,message.threadid]] ||= [];
			threads[[message.threaduserid,message.threadid]] << message;
		}
		threads.each_pair { |threadid, messages|
			threads[threadid] = MessageThread.new(messages);
			threads[threadid].order_by_date!;
		}
	
		t = Template::instance("messages", "threaded_messages");
		t.threads = threads;
		t.folders = folders;
		puts t.display();
	end
	
	def new_message()
		t = Template::instance("messages", "new_message");
		t.handler_root = HANDLER_ROOT;
		display = t.display();
		puts display;
	end
	
	def view_message(message_id)
		t = Template::instance("messages", "view_message");
		t.handler_root = HANDLER_ROOT;
		t.replyid = message_id;
		message = Message.load(message_id);
		if (!message.nil?)
			message.read();
			message.store();
		end
		t.body = message.text;
		t.subject = message.subject;
		t.from = message.fromname;
		t.date = message.date;
		t.new_subject = "RE:" + message.subject;
		t.new_to = message.fromname;
		puts t.display();
	end
	
	def view_my_thread(threaduserid, threadid)
		view_thread(request.session.user.userid, threaduserid, threadid);
	end
	
	def view_thread(userid, threaduserid, threadid)
		messages = MessageHeader.find(:thread, userid, threaduserid, threadid);
		thread = MessageThread.new(messages);
		
		t = Template::instance('messages', 'thread');
		t.thread = thread;
		puts t.display();
	end
	
	def send_message()
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
		message.send();
	end
end