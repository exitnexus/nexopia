lib_require :Core, "user_time"

module Comments
	class Comment < Cacheable
		init_storable(:usersdb, "usercomments");
		
		relation_singular :user, :userid, User, true
		relation_singular :author, :authorid, User, true
		
		user_content :nmsg
		
		def date
			UserTime.new(time)
		end
	end
end

class User < Cacheable
	relation_multi_cached :first_five_comments, :userid, Comments::Comment,
		"first_five_comments", {:limit => 5, :order => "time DESC"}
	relation_paged :comments, :userid, Comments::Comment
end
