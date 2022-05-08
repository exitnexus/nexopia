module Observations
	class StatusSidebar < PageHandler
		declare_handlers("status/sidebar") {
			area :Self
			page :GetRequest, :Full, :sidebar
		}
		
		SIDEBAR_RESULT_LENGTH = 10
		
		def sidebar
			t = Template.instance('observations', 'status_sidebar')
			t.types = StatusPage.get_types
			friends = request.user.friends.map {|friend| friend.user}
			t.types.each {|type|
				type.statuses = Status.active(friends, type.symbol, SIDEBAR_RESULT_LENGTH)
			}
			puts t.display
		end
	end
end