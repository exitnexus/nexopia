lib_require :Core,  'users/user', 'users/useremails';
lib_require :Core, 'authorization';

require 'rmail';
require 'net/smtp';
require 'stringio';

module FriendFinder
	class InviteEmail
		attr_accessor :user_name, :user_real_name, :user_email, :user_id, :personalized_message, :friend_name, :friend_email;
	
	
		def send()
			message = RMail::Message.new;
			message.header.add('Content-Type',"multipart/alternative");
			message.header.to = "#{self.friend_name} <#{self.friend_email}>";
			message.header.from = "#{$site.config.site_name} <no-reply@nexopia.com>";
			message.header.reply_to = "#{self.user_name} <#{self.user_email}>";
			message.header.subject = "Invitation to #{$site.config.site_name}";
			
			plain_text_message = RMail::Message.new();
			html_message = RMail::Message.new();
			
			html_message.header.add("Content-Type", "text/html");
			plain_text_message.header.add("Content-Type", "text/plain");
			t = Template.instance("friend_finder", "invite_email");
			t.my_name = self.user_name;
			t.my_userid = self.user_id.to_s();
			t.friend_name = self.friend_name;
			t.friend_email = self.friend_email;
			t.friend_key = Authorization.instance.make_key(self.friend_email, -1);
			t.wwwdomain = $site.www_url;
			t.personal_message = personalized_message || "";
			
			html_message.body = t.display();
			plain_text_message.body = create();
			
			message.add_part(plain_text_message);
			message.add_part(html_message);
			# Make an SMTP compatible string to send
			message_text = RMail::Serialize.write("", message);

			# Send it out
			Net::SMTP.start($site.config.mail_server, $site.config.mail_port) {|smtp|
				smtp.send_message(message_text, message.header.from, self.friend_email)};
		end
	
	
		def send_test()
			message = RMail::Message.new;
	
			message.body = create(self.friend_name, self.friend_email);
			message.header.to = "#{self.friend_name} <#{self.friend_email}>";
			message.header.from = "#{self.my_name} <#{self.user_email}>";
			message.header.subject = "Invitation to #{$site.config.site_name}";
	
			# Make an SMTP compatible string to send
			message_text = RMail::Serialize.write("", message);
	
			return message_text;
		end
	
	
		def create()
			message = source;
			
			message.gsub!(/\{my_name\}/, self.user_name);
			message.gsub!(/\{my_userid\}/, self.user_id.to_s());
			message.gsub!(/\{friend_name\}/, self.friend_name);
			message.gsub!(/\{friend_email\}/,self.friend_email);
			message.gsub!(/\{friend_key\}/, Authorization.instance.make_key(self.friend_email,-1));
			message.gsub!(/\{wwwdomain\}/, $site.www_url);
			message.gsub!(/\{personal_message\}/, create_personal_message());
	
			return message;
		end
	
	
		def create_personal_message
			text = (self.personalized_message.nil? || self.personalized_message == "") ? "" : 
				"\n#{self.user_name} says: \"#{self.personalized_message}\"\n"; 
			
			return text;
		end
	
	
		def source
			file = File.open("friend_finder/templates/invite_email.txt");
	
			return file.read;
		end
	end

end
