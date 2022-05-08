require "core/lib/template/template"

class Index < PageHandler
	declare_handlers("/") {
		area :Public
		access_level :Any

		handle :GetRequest, :console, "console"
	}

	def console()
		t = Template::instance("debug", "console");
		puts t.display();
	end
end
