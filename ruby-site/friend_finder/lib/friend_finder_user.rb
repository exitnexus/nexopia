lib_require	:Core, "users/user";
lib_require :FriendFinder, "email_invite";
=begin
class User < Cacheable
	self.postchain_method(:after_create, &lambda {
		user_email_obj = UserEmail.find(:first, :conditions => ["userid = # AND active = 'n'", self.userid]);
		
		if(user_email_obj.nil?())
			return;
		else
			user_email = user_email_obj.email;
		end
		
		email_invite_list = FriendFinder::EmailInvite.find(user_email, :email);
		
		email_invite_list.each{|invite|
			self.add_friend(invite.userid);
			invite.delete();
		};
	});
end
=end