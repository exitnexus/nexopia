module Blogs
	class BlogCommentUnreadNotification < Cacheable
		init_storable(:usersdb, "blogcommentsunread");
	end
end
