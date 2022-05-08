lib_require :Core, "storable/storable"

module Blogs
	class BlogProfile < Cacheable
		init_storable(:usersdb, "blogprofile");
		
		relation :singular, :user, [:userid], User;
	end
end
