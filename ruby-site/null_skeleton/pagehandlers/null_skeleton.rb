class NullSkeletonPages < PageHandler
	declare_handlers("NullSkeleton") {
		area :Skeleton
		handle :GetRequest, :full_page, "skin", "Full", input(String), remain
		
		handle :GetRequest, :default, remain
	}
		
	def full_page(area, path)
		rewrite(request.method, (url/path).to_s + ":Body", request.params.to_hash, area.to_sym)
	end
	
	# push it off to nexoskel.
	def default(remain)
		rewrite(request.method, url/:Nexoskel/remain, params.to_hash, :Skeleton)
	end
end
