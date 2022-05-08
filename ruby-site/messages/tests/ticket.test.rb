require_gem 'mocha'
lib_require :Messages, 'ticket', 'message_header'
lib_require :Devutils, 'quiz'

class TestTicket < Quiz
	def setup
		@ticket = Ticket.new;
		@problem = "Test problem";
		@userid = 666;
		@threadid = 42;
		@ticket.problem = @problem;
		@ticket.userid = @userid;
		@ticket.threadid = @threadid;
	end
	
	def teardown
		return;
	end
	
	def test_uri_info
		correct_url = url/:tickets/@userid/@threadid;
		assert_equal(@problem, @ticket.uri_info.first);
		assert_equal(correct_url, @ticket.uri_info.last)
	end
	
	def test_create
		stub_message = stub(:threaduserid => @userid, :threadid => @threadid);
		new_ticket = Ticket.create(stub_message);
		new_ticket.problem = @problem;
		assert_equal(@ticket, new_ticket);
	end
	
	def test_open_close
		@ticket.status = :closed;
		assert_equal(:closed, @ticket.status);
		@ticket.open();
		assert_equal(:open, @ticket.status);
		@ticket.close();
		assert_equal(:closed, @ticket.status);
	end
	
	def test_threadid_struct
		tid = MessageHeader::ThreadID.new();
		tid.userid = @userid;
		tid.threadid = @threadid;
		assert_equal(tid, @ticket.threadid_struct)
	end
end