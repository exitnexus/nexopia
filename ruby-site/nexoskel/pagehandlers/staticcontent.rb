lib_require :Wiki, "wiki"

class StaticContent < PageHandler
	declare_handlers("content") {
		area :Public

		page :GetRequest, :Full, :content, remain
	}

	def content(remain)

	#try a plus version of the page
		if(request.session.user.plus?)
			page = Wiki::from_address("/SiteText/content/#{remain}plus").get_revision();
		end

	#fall back to the normal version
		if(!page)
			page = Wiki::from_address("/SiteText/content/#{remain}").get_revision();
		end

	#if neither found, 404
		if(!page)
			raise PageError.new(404), "Page not found"
		end

	#display!
		t = Template::instance("nexoskel", "staticcontent");
		t.text = page.content;
		puts t.display;
	end
end
