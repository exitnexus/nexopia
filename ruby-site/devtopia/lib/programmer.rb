module Devtopia
	class Programmer < Storable
		set_db(:devtaskdb);
		set_table("programmer");
		init_storable();

		def after_load()
			@user = User.find(:first, self.userid);
		end
		
		def after_create()
			@user = User.find(:first, self.userid);
		end

		def username
			if (@user)
				return @user.username;
			else
				return "";
			end
		end
	
	
		def filter_programmer
			if (self.filterid == -1)
				return nil;
			else
				return Devtopia::Programmer.find(:first, self.filterid);
			end
		end
	
	end	
end