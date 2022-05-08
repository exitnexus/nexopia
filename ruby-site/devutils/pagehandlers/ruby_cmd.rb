class RubyConsole < PageHandler
	declare_handlers("rubyconsole") {
		page :GetRequest, :Full, :test_page;
	}

	def test_page
		code = params['cmd', String]
		if (code)
			code.gsub!('%3F', '?')
			code.gsub!('%20', ' ')
			eval(code)
		end
	end

end