class JSONData < PageHandler
	declare_handlers("data") {
		area :Internal
		handle :GetRequest, :data, "data"
	}
	
	def data()
		t = Template::instance("json", "data")
		
		t.data = {
			"jsonPageOwner" => request.user,
			"jsonPageViewer" => request.session.user
		}
		
		puts t.display
	end
end
