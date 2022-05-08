lib_require :Core, "storable/storable";
module Blogs
	class BlogLastReadFriends < Cacheable
		attr_reader :username

		init_storable(:usersdb, "bloglastreadfriends");
	end
end
