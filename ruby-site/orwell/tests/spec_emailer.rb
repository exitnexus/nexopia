lib_require :Orwell, 'emailer'

describe Orwell do

	it "should send email" do
		lambda {
			Orwell::send_email('cthompson@nexopia.com', 'test',
		 		"hello, world", :smtp_server => 'svn.office.nexopia.com',
		 		:smtp_port => 25, :from => 'cthompson@nexopia.com')
		}.should_not raise_error
	end
	
	it "should send html email" do
		html_msg = "<h1>Hello!</h1>
		<p>This is a test message</p>
		<p>Did it work?</p>"
		
		lambda {
			Orwell::send_email('cthompson@nexopia.com', 'test',
		 		"hello, world", :smtp_server => 'svn.office.nexopia.com',
		 		:smtp_port => 25, :from => 'cthompson@nexopia.com',
				:msg_html => html_msg)
		}.should_not raise_error
	end
		
	it "should send html email with an embedded image" do
		html_msg = "<h1>Hello!</h1>
		<p>This is a test message with an image.
		<img src=\"cid:foo\">
		</p>"
		# Load sample image
		image = ''
		File.open('orwell/tests/sample.jpg', 'r') { |f|
			image = f.read()
		}
		image.should_not == nil
		image.length.should_not == 0
		
		lambda {
			Orwell::send_email('cthompson@nexopia.com', 'test',
		 		"hello, world", :smtp_server => 'svn.office.nexopia.com',
		 		:smtp_port => 25, :from => 'cthompson@nexopia.com',
				:msg_html => html_msg,
				:msg_parts => [['image/jpg', 'foo', image]])
		}.should_not raise_error
	end
		
end