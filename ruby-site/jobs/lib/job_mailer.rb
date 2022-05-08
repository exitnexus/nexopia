module Jobs
	class JobMailer
		class << self
			def send_notification(recipient, subject, message, author)
				mail_message = RMail::Message.new();
				
				mail_message.header.to = recipient;
				mail_message.header.from = author;
				mail_message.header.subject = subject;
				mail_message.header.add('Content-Type', 'text/html');
				mail_message.body = message;
				
				mail_message_text = RMail::Serialize.write("", mail_message);
				Net::SMTP.start($site.config.mail_server, $site.config.mail_port){|smtp|
					smtp.send_message(mail_message_text, author, recipient);
				};
			end
		end
	end
end
