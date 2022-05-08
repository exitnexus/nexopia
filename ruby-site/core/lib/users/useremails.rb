lib_require :Core, "storable/cacheable"

class UserEmail < Cacheable
	init_storable(:masterdb, "useremails");
	set_prefix("ruby_useremail");
		
	def UserEmail.by_email(email)
		return find(:first, :conditions => ["email = ?", email]);
	end
end
