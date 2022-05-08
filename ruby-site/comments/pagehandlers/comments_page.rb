lib_require :Comments, "comments"

module Comments
	class Page < PageHandler
		declare_handlers("comments") {
			area :User
			
			page :GetRequest, :Full, :page
		}
		
		def page()
		end
	end
end
