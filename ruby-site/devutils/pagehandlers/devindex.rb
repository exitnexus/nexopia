class DevIndex < PageHandler
	declare_handlers("dev") {
		# TODO: Make this really require that access level.
		# access_level :Admin, DevutilsModule, :developer;
		page :GetRequest, :Full, :dev_index;
	}

	def dev_index()
		t = Template::instance("devutils", "devindex");
		puts t.display();
	end
end

