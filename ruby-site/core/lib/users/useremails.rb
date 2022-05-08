
class UserEmail < Storable
	init_storable(:masterdb, "useremails");
	
	def UserEmail.by_email(email)
		return find(:first, :conditions => ["email = ?", email]);
	end
end
