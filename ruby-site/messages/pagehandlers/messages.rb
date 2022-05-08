lib_require :Messages, 'message_header', 'message', 'message_folder';
lib_require :Core, 'template/template'
require 'erb'
require 'stringio'

class Messages < PageHandler

	def initialize(*args)
		super(*args);
		@dump = StringIO.new;
	end

	declare_handlers("messages") {
		area :Self
		access_level :Any
		page :GetRequest, :Full, :message_center
		page :GetRequest, :Full, :create_message, "create"
		page :GetRequest, :Full, :list_folders, "folders"
		handle :GetRequest, :xml_list, 'folders', input(Integer)
		page :PostRequest, :Full, :send_message, "send"
		page :GetRequest, :Full, :delete_messages, "delete"
		page :GetRequest, :Full, :show_message, input(Integer)
		page :GetRequest, :Full, :test_page, 'test'
	}

	def messages_default(*args)
		t = Template::instance("messages", "default_messages_page");
		puts t.display();
	end

	def delete_messages()
		ids = params['ids', String];
		Message.delete(session.user.userid, ids)
		puts "Deleted successfully.";
	end

	def show_message(messageid, *args)
		t = Template::instance("messages", "message");

		message = Message.load(messageid);
		if (!message.nil?)
			message.read();
			message.store();
			t.header = message.subject;
			t.text = message.text;
			t.messageid = messageid;
		else
			t.userid = session.user.userid;
			t.messageid = messageid;
			t.message_missing = true;
		end

		puts t.display();
	end

	def create_message(*args)
		t = Template::instance("messages", "create_message");
		puts t.display();
		puts "<div style=\"background-color:green;width:100px;height:100%;float:right\"><a href=\"/my/messages/create\" onclick=\"MessageDialog.show();return false;\">Send Message</a></div>";
	end

	def send_message(*args)
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
		message.text = params['text', String];
		message.send();
		puts "Message sent.";
	end

	def message_center(*args)
		sio = StringIO.new();
		folders = subrequest(sio, :GetRequest, "/messages/folders", {}, :Self);
		t = Template::instance("messages", "message_center");
		t.folders=sio.string;
		puts "#{t.display()}";
	end

	def xml_list(folder_id)
		messages = MessageHeader.find(:conditions => ["`userid` = # && `folder` = ?", session.user.userid, folder_id]);
		reply.headers['Content-Type'] = PageRequest::MimeType::XML;
		puts "<?xml version = \"1.0\"?>";
		puts "<message-list>"
		messages.each {|message|
			puts "\t<message>";
			puts "\t\t<id>#{message.id}</id>";
			puts "\t\t<fromname>#{message.fromname}</fromname>";
			puts "\t\t<from>#{message.from}</from>";
			puts "\t\t<subject>#{message.subject}</subject>";
			puts "\t\t<userid>#{message.userid}</userid>";
			puts "\t\t<toname>#{message.toname}</toname>";
			puts "\t\t<to>#{message.to}</to>";
			puts "\t\t<link>/my/messages/#{message.id}</link>";
			if (message.from != 0)
				puts "\t\t<fromlink>/users/#{message.fromname}/</fromlink>";
			else
				puts "\t\t<fromlink></fromlink>";
			end
			puts "\t\t<status>#{message.status}</status>";
			puts "\t</message>"
		}
		puts "</message-list>";
	end

	def list_folders()
		folders = MessageFolder.all();
		t = Template::instance("messages", "list_folders");
		t.folders = folders;
		puts t.display();
	end

	def test_page
		t = Template::instance("messages", "test_page");
		puts t.display();
	end

	private
	def escape_javascript(javascript)
		(javascript || '').gsub(/\r\n|\n|\r/, "\\n").gsub(/["']/) { |m| "\\#{m}" }
	end
end
