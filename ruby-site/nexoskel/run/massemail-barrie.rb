if(false)

lib_require :Core, "users/user"
require 'rmail';
require 'net/smtp';

text = IO.read("nexoskel/templates/massemail-barrie.txt");
html = IO.read("nexoskel/templates/massemail-barrie.html");


	barrie = 395;
	users = User.find(:all, :conditions => ["loc = ?", barrie]);

#	users = User.find(:all, :conditions => ["userid IN #", [182, 6871, 6831]]);	

	users.each {|user|
		message = RMail::Message.new;
		message.header.add('Content-Type',"multipart/alternative");
		message.header.to = "#{user.username} <#{user.email}>";
		message.header.from = "#{$site.config.site_name} Contests <contests@nexopia.com>";
		message.header.subject = "#{$site.config.site_name} Friending Contest";
		
		plain_text_message = RMail::Message.new();
		plain_text_message.header.add("Content-Type", "text/plain");
		plain_text_message.body = text.gsub("{username}", user.username);
		message.add_part(plain_text_message);

		html_message = RMail::Message.new();
		html_message.header.add("Content-Type", "text/html");
		html_message.body = html.gsub("{username}", user.username);
		message.add_part(html_message);

		# Make an SMTP compatible string to send
		message_text = RMail::Serialize.write("", message);

		# Send it out
		Net::SMTP.start($site.config.mail_server, $site.config.mail_port) {|smtp|
			smtp.send_message(message_text, message.header.from, user.email);
		}
	}
end
