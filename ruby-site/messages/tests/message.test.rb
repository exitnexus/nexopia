require_gem 'mocha'
require 'stubba'
require 'mocha'

lib_require :Messages, 'message'
lib_require :Devutils, 'quiz'

class TestMessage < Quiz
	def setup
		return;
	end
	
	def teardown
		return;
	end
	
	def test_new_from_scratch
		message = Message.new;
		assert(message.header_sender.kind_of?(MessageHeader));
		assert_equal(MessageFolder::SENT, message.header_sender.folder)
		assert(message.header_receiver.kind_of?(MessageHeader));
		assert_equal(MessageFolder::INBOX, message.header_receiver.folder)
		assert(message.text_sender.kind_of?(MessageText));
		assert(message.text_sender.kind_of?(MessageText));
		assert(message.ticket.nil?);
	end
	
	def test_new_from_existing
		msg_text_stub = stub();
		msg_text_stub2 = stub();
		ticket_stub = stub();
		assert_not_equal(ticket_stub, msg_text_stub); #just assuring that mocha stubs are working as intended
		header_receiver_stub = stub(:msgtext => msg_text_stub, :ticket => ticket_stub);
		header_sender_stub = stub(:msgtext => msg_text_stub2, :ticket => ticket_stub);
		message = Message.new(nil, nil, header_receiver_stub, nil);
		assert_equal(ticket_stub, message.ticket);
		assert_equal(msg_text_stub, message.text_receiver);
		message2 = Message.new(header_sender_stub, nil, header_receiver_stub, nil);
		assert_equal(ticket_stub, message2.ticket);
		assert_equal(msg_text_stub2, message2.text_sender);
		assert_equal(msg_text_stub, message2.text_receiver);
	end
	
	def test_attrs
		User.use_test_db {
			@folder = [123, 456];
			@text = "Stop poking me!";
			@subject = "Who'd joo want me kill?";
			@date = "123456789";
			@from_uid = 200;
			@to_uid = 203;
			@to_name = "joe";
			@from_name = "bob";
			@from_user = stub(:userid => @from_uid, :username => @from_name); 
			@to_user = stub(:userid => @to_uid, :username => @to_name);
			u=User.new
			u.userid = @from_uid
			u.store
			u=User.new
			u.userid = @to_uid
			u.store
			
			message = Message.new;
			
			assert_not_equal(@folder, message.folder);
			message.folder = @folder
			assert_equal(@folder, message.folder);
			assert_raise(RuntimeError) {message.folder = nil}
			
			assert_not_equal(@text, message.text);
			message.text = @text;
			assert_equal(@text, message.text);
			assert_equal(@text, message.text_sender.msg);
			assert_equal(@text, message.text_receiver.msg);
			
			assert_not_equal(@subject, message.subject);
			message.subject = @subject;
			assert_equal(@subject, message.subject);
			assert_equal(@subject, message.header_sender.subject);
			assert_equal(@subject, message.header_receiver.subject);
			
			assert_not_equal(@date, message.date);
			message.date = @date;
			assert_equal(@date, message.date);
			assert_equal(@date, message.header_sender.date);
			assert_equal(@date, message.header_receiver.date);
			assert_equal(@date, message.text_sender.date);
			assert_equal(@date, message.text_receiver.date);
			
			assert_not_equal(message.header_sender.userid, @from_uid);
			message.sender=(@from_user);
			assert_equal(message.header_sender.userid, @from_uid);
		
			assert_not_equal(message.header_receiver.userid, @to_uid);
			message.receiver=(@to_user);
			assert_equal(message.header_receiver.userid, @to_uid);
		
			assert_equal(@from_uid, message.header_sender.from)
			assert_equal(message.header_sender.userid, message.header_sender.from)
			assert_equal(message.header_sender.userid, message.header_receiver.from)
			
			assert_equal(@to_uid, message.header_receiver.to)
			assert_equal(message.header_receiver.userid, message.header_receiver.to)
			assert_equal(message.header_receiver.userid, message.header_sender.to)
			
			assert_equal(@to_name, message.header_receiver.toname);
			assert_equal(@to_name, message.header_sender.toname);
			assert_equal(@from_name, message.header_receiver.fromname);		
			assert_equal(@from_name, message.header_sender.fromname);
			User.db.query("TRUNCATE TABLE #{User.table}")
		}
	end
	
	def test_reply
		@threadid = 42;
		@threaduserid = 666;
		@sender_uid = 1024;
		@receiver_uid = 2048;
		@sender_msgid = 1;
		@receiver_msgid = 2;
		
		original = Message.new;
		original.header_sender.threadid = @threadid
		original.header_receiver.threadid = @threadid
		original.header_sender.threaduserid = @threaduserid
		original.header_receiver.threaduserid = @threaduserid
		original.header_sender.userid = @sender_uid
		original.header_receiver.userid = @receiver_uid
		original.header_sender.id = @sender_msgid
		original.header_receiver.id = @receiver_msgid
	
		assert_not_equal("replied", original.header_sender.status);
		assert_not_equal("replied", original.header_receiver.status);
		reply = original.reply();
		assert_equal("replied", original.header_sender.status);
		assert_equal("replied", original.header_receiver.status);
		
		assert_equal(@threadid, reply.threadid.threadid);
		assert_equal(@threaduserid, reply.threadid.userid);
		assert_equal(@threadid, reply.header_sender.threadid);
		assert_equal(@threaduserid, reply.header_sender.threaduserid);
		assert_equal(@threadid, reply.header_receiver.threadid);
		assert_equal(@threaduserid, reply.header_receiver.threaduserid);
		assert_equal(@sender_msgid, reply.header_sender.replyto);
		assert_equal(@receiver_msgid, reply.header_receiver.replyto);
	end
	
	def test_set_ids
		@receiver_id, @sender_id = 123, 456;
		@threadid = 666;
		@threaduserid = 777;
		@user_id = 42;
		
		MessageHeader.expects(:get_seq_id).returns(@receiver_id, @sender_id).times(2);
		message = Message.new();
		message.header_sender.expects(:userid).returns(@user_id).at_least_once;
		message.header_receiver.expects(:userid).returns(1234).at_least_once;
		message.header_sender.expects(:store).never();
		message.expects(:store).times(1);
		message.open_ticket();
		message.send();
		assert_equal(@receiver_id, message.header_receiver.id);
		assert_equal(@receiver_id, message.text_receiver.id);
		assert_equal(@sender_id, message.header_receiver.othermsgid);
		assert_equal(@sender_id, message.header_sender.id);
		assert_equal(@sender_id, message.text_sender.id);
		assert_equal(@receiver_id, message.header_sender.othermsgid);
		assert_equal(@user_id, message.ticket.userid);
		assert_equal(@sender_id, message.ticket.threadid);
	end
	
	def test_read
		message = Message.new;
		assert_not_equal("read", message.header_sender.status);
		assert_not_equal("read", message.header_receiver.status);
		message.read();
		assert_equal("read", message.header_sender.status);
		assert_equal("read", message.header_receiver.status);
	end
	
	def test_store
		mock_storable = mock();
		mock_storable.expects(:store).times(5); #call store once for header_sender, header_receiver, text_sender, text_receiver, ticket
		mock_storable.expects(:ticket).returns(mock_storable); #this is how the ticket gets set
		
		message = Message.new(mock_storable,mock_storable,mock_storable,mock_storable);
		
		message.store();
	end
	
	def test_delete
		@userid = 123456;
		@splitids = ['1000', '2000', '3000', '4000'];
		@ids = "1000,2000,3000,4000";
		
		header_db = MessageHeader.db;
		text_db = MessageText.db;
		mock_db1 = mock();
		mock_db1.expects(:query).with("DELETE from #{MessageHeader.table} WHERE userid = # && id IN ?", @userid, @splitids);
		mock_db2 = mock();
		mock_db2.expects(:query).with("DELETE from #{MessageText.table} WHERE userid = # && id IN ?", @userid, @splitids);
		
		MessageHeader.db=mock_db1;
		MessageText.db=mock_db2;
		
		Message.delete(@userid, @ids);
		
		MessageHeader.db = header_db;
		MessageText.db = text_db;
	end
end