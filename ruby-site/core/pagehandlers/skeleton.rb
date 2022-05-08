module Core
	class Skeleton < PageHandler
		declare_handlers("/") {
			area :Skeleton
			
			handle :GetRequest, :current, "current", remain
			handle :GetRequest, :default, "default", remain
		}
		
		# this passes requests to the area Skeleton into the appropriate
		# skeleton for the current request
		def current(remain)
			rewrite(request.method, url/request.skeleton/remain, nil, :Skeleton)
		end
		
		def default(remain)
			rewrite(request.method, url/$site.default_skeleton/remain, nil, :Skeleton)
		end
	end
end
