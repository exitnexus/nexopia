lib_require :Profile, "profile_block_query_mediator", "profile_block_visibility";

class NullSkeletonPages < PageHandler
	declare_handlers("NullSkeleton") {
		area :Skeleton
		handle :GetRequest, :full_page, "skin", "Full", input(String), remain
		
		handle :GetRequest, :default, remain
	}
	

	
	def full_page(area, path)
		if(area.to_s == "User")
			rewrite(request.method, url/:Nexoskel/:user/:header/:users/request.user.username/path, nil, :Skeleton);
		else
			rewrite(request.method, (url/path).to_s + ":Body", nil, area.to_sym)
		end
	end
	
	# push it off to nexoskel.
	def default(remain)
		rewrite(request.method, url/:Nexoskel/remain, nil, :Skeleton)
	end
	

end
