lib_require :core,  'users/user', 'users/useremails'
lib_require :core, 'authorization'

require 'rmail'
require 'net/smtp'
require 'stringio'

class InviteEmail

	def initialize(user,my_name,personal_message=nil)
		@user = user;
		@my_name = my_name;
		@personal_message = personal_message;
	end


	def send(friend_name, friend_email)
		user_email = UserEmail.find(:first, @user.userid);

		message = RMail::Message.new;

		message.body = create(friend_name, friend_email);
		message.header.to = "#{friend_name} <#{friend_email}>";
		message.header.from = "#{@my_name} <#{user_email.email}>";
		message.header.subject = "Invitation to #{$site.config.site_name}";

		# Make an SMTP compatible string to send
		message_text = RMail::Serialize.write("", message);

		# Send it out
		Net::SMTP.start($site.config.mail_server, $site.config.mail_port) {|smtp|
			smtp.send_message(message_text, user_email.email, friend_email)};
	end


	def send_test(friend_name, friend_email)
		user_email = UserEmail.find(:first, @user.userid);

		message = RMail::Message.new;

		message.body = create(friend_name, friend_email);
		message.header.to = "#{friend_name} <#{friend_email}>";
		message.header.from = "#{@my_name} <#{@user.email}>";
		message.header.subject = "Invitation to #{$site.config.site_name}";

		# Make an SMTP compatible string to send
		message_text = RMail::Serialize.write("", message);

		return message_text;
	end


	def create(friend_name, friend_email)
		message = source;

		message.gsub!(/\{my_name\}/, @my_name);
		message.gsub!(/\{my_user_name\}/, "#{CGI::escape(@user.username)}");
		message.gsub!(/\{friend_name\}/, friend_name);
		message.gsub!(/\{friend_email\}/,friend_email);
		message.gsub!(/\{friend_key\}/, Authorization.instance.make_key(friend_email,-1));
		message.gsub!(/\{wwwdomain\}/, $site.www_url);
		message.gsub!(/\{personal_message\}/, create_personal_message);

		return message;
	end


	def create_personal_message
		text = (@personal_message.nil? || @personal_message == "") ? "" : 
			"\n#{@my_name} says: \"#{@personal_message}\"\n"; 
		
		return text;
	end


	def source
		file = File.open("account/templates/invite_email.txt");

		return file.read;
	end
end
