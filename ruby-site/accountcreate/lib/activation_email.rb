lib_require :Core,  'users/user', 'users/useremails'

require 'rmail'
require 'net/smtp'
require 'stringio'

class ActivationEmail

	def initialize(user,key)
		@message = create(user.username,key);
		@user = user;
		@key = key;
	end


	def send()
		user_email = UserEmail.find(:first, @user.userid);

		message = RMail::Message.new;

		message.body = @message;
		message.header.to = user_email.email;
		message.header.from = "#{$site.config.site_name} <no-reply@#{$site.config.email_domain}>";
		message.header.subject = "Activate your account at #{$site.config.site_name}";

		# Make an SMTP compatible string to send
		message_text = RMail::Serialize.write("", message);

		# Send it out
		Net::SMTP.start($site.config.mail_server, $site.config.mail_port) {|smtp|
			smtp.send_message(message_text, "no-reply@#{$site.config.email_domain}", user_email.email)};
	end


	def create(username, key)
		message = source;

		message.gsub!(/\{username\}/,username);
		message.gsub!(/\{key\}/,key);
		message.gsub!(/\{wwwdomain\}/, $site.www_url);

		return message;
	end


	def source
		file = File.open("accountcreate/templates/activation_email.txt");

		return file.read;
	end
end
