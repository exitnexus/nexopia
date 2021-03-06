lib_require :Messages, 'message_folder', "message_header"#, 'ticket'
lib_require :Orwell, 'send_email'
lib_want :user_dump, 'archive_type', 'dumpable'

class Message
	attr_accessor(:text, :subject, :sender, :receiver, :date);#, :ticket);

	#If you edit these objects directly you will risk breaking the consistency of the Message object.
	attr_reader(:header_sender, :header_receiver, :text_sender, :text_receiver);#, :ticket);

	# Messages are included in UserDumps
	if (site_module_loaded?(:UserDump))
		extend(Dumpable)
		def self.user_dump(uid, start=0, finish=Time.now.to_i)
			archive = ArchiveType.new(ArchiveType::MESSAGE)
			dump = archive.dump_archive(uid, start, finish)
			return Dumpable.str_to_file("#{uid}-messages.txt", dump)
		end
	end

	#If no arguments are passed this can be used to create a message stored for both sender and receiver.
	#Otherwise it is just a wrapper for viewing a particular persons message.
	def initialize(header_sender=nil, text_sender=nil, header_receiver=nil, text_receiver=nil)
		#creating our own message
		if (header_sender.nil? && text_sender.nil? && header_receiver.nil? && text_receiver.nil?)
			@header_sender = MessageHeader.new;
			@header_sender.folder = MessageFolder::SENT;
			@header_receiver = MessageHeader.new;
			@header_receiver.folder = MessageFolder::INBOX;
			@text_sender = MessageText.new;
			@text_receiver = MessageText.new;
		else #using this as a wrapper on preexisting information, possibly don't have both sender and receiver
			if (!header_sender.nil?)
				@header_sender = header_sender;
				if (text_sender)
					@text_sender = text_sender;
				else
					@text_sender = @header_sender.msgtext;
				end
			end
			if (!header_receiver.nil?)
				@header_receiver = header_receiver;
				if (text_receiver)
					@text_receiver = text_receiver;
				else
					@text_receiver = @header_receiver.msgtext;
				end
			end
		end
	end

	def folder=(value)
		raise "'value' must be of length 2. [0] => receiver_folder, [1] => sender_folder" if (!value.respond_to?(:length) || value.length != 2);
		@header_receiver.folder = value[0]
		@header_sender.folder   = value[1]
	end

	def folder
		receiver_folder = @header_receiver.folder unless @header_receiver.nil?;
		sender_folder = @header_sender.folder unless @header_sender.nil?;
		return [receiver_folder, sender_folder];
	end

	def text=(string)
		@text_receiver.msg = string if (!@text_receiver.nil?);
		@text_sender.msg = string if (!@text_sender.nil?)
	end

	def text
		return @text_receiver.msg if (!@text_receiver.nil?);
		return @text_sender.msg if (!@text_sender.nil?);
		return nil;
	end

	def subject=(string)
		string = "No Subject" if (string == "")
		@header_sender.subject = string if (!@text_sender.nil?);
		@header_receiver.subject = string if (!@text_receiver.nil?);
	end

	def subject
		return @header_sender.subject if (!@header_sender.nil?);
		return @header_receiver.subject if (!@header_receiver.nil?);
		return nil;
	end

	#WARNING: only use this function when messages should becoming from no user (eg. site messages)
	def sender_name=(username)
		@header_receiver.fromname = username
		@header_receiver.from = 0
		@header_receiver.otheruserid = 0
		@text_sender = nil
		@header_sender = nil
	end
	
	def sender=(user)
		username = user
		if (user.kind_of?(String))
			user = User.get_by_name(user)
		end
		unless (user.nil?)
			@text_sender.userid = user.userid if (!@text_sender.nil?);
			if (!@header_sender.nil?)
				@header_sender.userid = user.userid;
				@header_sender.from = user.userid;
				@header_sender.fromname = user.username;

			end
			if (!@header_receiver.nil?)
				@header_receiver.otheruserid = user.userid;
				@header_receiver.from = user.userid;
				@header_receiver.fromname = user.username;
			end
		else
			@header_receiver.fromname = username
			@header_receiver.from = 0
			@header_receiver.otheruserid = 0
			@text_sender = nil
			@header_sender = nil
		end
	end

	def sender
		return @header_sender.user_from if (!@header_sender.nil?);
		return @header_receiver.user_from if (!@header_receiver.nil?);
		return nil;
	end

	def fromname
		return @header_sender.fromname if (!@header_sender.nil?);
		return @header_receiver.fromname if (!@header_receiver.nil?);
		return nil;
	end

	def toname
		return @header_sender.toname if (!@header_sender.nil?);
		return @header_receiver.toname if (!@header_receiver.nil?);
		return nil;
	end

	def receiver=(user)
		@text_receiver.userid = user.userid if (!@text_receiver.nil?);
		if (!@header_receiver.nil?)
			@header_receiver.userid = user.userid;
			@header_receiver.to = user.userid;
			@header_receiver.toname = user.username;

		end
		if (!@header_sender.nil?)
			@header_sender.otheruserid = user.userid;
			@header_sender.to = user.userid;
			@header_sender.toname = user.username;
		end
	end

	def receiver
		return @header_sender.user_to if (!@header_sender.nil?);
		return @header_receiver.user_to if (!@header_receiver.nil?);
		return nil;
	end

	def date=(integer)
		@text_receiver.date = integer if (!@text_receiver.nil?);
		@text_sender.date = integer if (!@text_sender.nil?);
		@header_receiver.date = integer if (!@header_receiver.nil?);
		@header_sender.date = integer if (!@header_sender.nil?);
	end

	def date
		return @text_sender.date if (!@text_sender.nil?);
		return @text_receiver.date if (!@text_receiver.nil?);
		return @header_sender.date if (!@header_sender.nil?);
		return @header_receiver.date if (!@header_receiver.nil?);
		return nil;
	end

	def send
		raise SiteError, "Attempted to send message with no recipient." if (@header_receiver.nil? || @text_receiver.nil?);
		self.date = Time.now.to_i;
		set_ids();
		self.store();
	end

	#returns a new message object setup as a reply to this message object
	def reply()
		self.header_sender.status = "replied" unless (self.header_sender.nil?);
		self.header_receiver.status = "replied" unless (self.header_receiver.nil?);
		message = self.class.new;
		if (!self.header_sender.nil?)
			#message.header_sender.threadid = self.header_sender.threadid;
			#message.header_sender.threaduserid = self.header_sender.threaduserid;
			message.header_sender.replyto = self.header_sender.id;
		end
		if (!self.header_receiver.nil?)
			#message.header_receiver.threadid = self.header_receiver.threadid;
			#message.header_receiver.threaduserid = self.header_receiver.threaduserid;
			message.header_receiver.replyto = self.header_receiver.id;
		end
		return message;
	end

	def read()
		self.header_sender.status = "read" unless (self.header_sender.nil?);
		self.header_receiver.status = "read" unless (self.header_receiver.nil?);
	end

	#Get the message this is a reply to if it exists, otherwise return nil.
	def parent
		hs = MessageHeader.find(self.header_sender.userid, self.header_sender.replyto) if (self.header_sender);
		hr = MessageHeader.find(self.header_receiver.userid, self.header_receiver.replyto) if (self.header_receiver);
		if (!hs.nil? || !hr.nil?)
			return self.class.new(hs, nil, hr);
		end
		return nil;
	end

	def store()
		self.header_sender.store() unless (self.header_sender.nil?);
		self.header_receiver.store() unless (self.header_receiver.nil?);
		self.text_sender.store() unless (self.text_sender.nil?);
		self.text_receiver.store() unless (self.text_receiver.nil?);
		
		self.header_receiver.user_to.increment_unread!() unless (self.header_receiver.nil?);
	end

	# This is set up so that we can forward messages between users to email properly.
	def self.forward_site_message(recipient_userid, subject, content, from)
		user = User.find(:first, recipient_userid.to_i)
			
		if( from == "Nexopia")
			from_url = $site.www_url
		else
			from_url = "#{$site.www_url}/users/#{urlencode(from)}"
		end

		msg = Orwell::SendEmail.new()
		msg.subject = subject
		msg.send(user, "site_message_plain", :html_template => "site_message", :template_module => :messages, :content => content, :from => from, :from_url => from_url)
	end

	# Let's us send messages from the PHP side through the Ruby side.
	# Takes all the key values pairs passed in and uses the key for the
	# name of the attribute to assign to and value for the value.
	def self.php_send(args = {})

		message = Message.new;

		# Receiver is a special case since there's no User object
		# on the PHP side we take in the user id, find the User object
		# and then assign that.
		if args.has_key?('receiver')
			
			user = User.find(:first, args['receiver'].to_i)
			message.__send__(:receiver=, user)
			args.delete('receiver')
		end

		# Assign all the values to their respective attributes.
		args.each_pair { |key, value|
			message.__send__(key + "=", value)
		}
		message.send()
		
	end

	#Retrieves a message by id for the user who has the current session.
	#Returns nil if the message doesn't exist.
	def self.load(message_id)
		header = MessageHeader.find(:first, PageHandler.current.session.user.userid, message_id);
		return nil if (header.nil?);
		other_header = MessageHeader.find(:first, header.otheruserid, header.othermsgid);
		if (header.userid == header.from) #we are the receiver
			return Message.new(other_header, nil, header);
		else #We are the sender
			return Message.new(header, nil, other_header);
		end
	end

	def self.delete(userid, ids)
		MessageHeader.db.query("DELETE from #{MessageHeader.table} WHERE userid = # && id IN ?", userid, ids.split(','));
        MessageText.db.query("DELETE from #{MessageText.table} WHERE userid = # && id IN ?", userid, ids.split(','));
	end

	private
	#This assumes everything is setup the way it should be, error checking needs to be done prior to this.
	def set_ids()
		receiver_id = MessageHeader.get_seq_id(self.header_receiver.userid);
		sender_id = if (self.header_sender.nil?) then 0 else MessageHeader.get_seq_id(self.header_sender.userid) end;
		sender_uid = if (self.header_sender.nil?) then 0 else self.header_sender.userid end;
		
		self.header_receiver.othermsgid = sender_id;
		self.header_receiver.id = receiver_id;
		if (Object.const_defined?('PageRequest'))
			if (PageRequest.current)
				self.header_receiver.sentip = PageRequest.current.session.ip
			end
		end
		self.text_receiver.id = receiver_id;
		unless (self.header_sender.nil?)
    		self.header_sender.othermsgid = receiver_id;
    		self.header_sender.id = sender_id;
			if (Object.const_defined?('PageRequest'))
				if (PageRequest.current)
					self.header_sender.sentip = PageRequest.current.session.ip
				end
			end
    		self.text_sender.id = sender_id;
    	end
   end
end

class User < Cacheable
	def unread
		return self.newmsgs
	end
	def msgs
		@new_msgs ||= MessageHeader.find(:conditions => ["userid = ? && status = 'new'", @userid])
		return @new_msgs
	end
	def increment_unread!()
		self.newmsgs = self.newmsgs + 1;
		self.store();
	end
	
end
