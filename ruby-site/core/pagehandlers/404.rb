# This pagehandler defines behaviour for when a page is not found, either
# because there is no handler registered for its path or because it's an attempt
# to go through a 404 handler dispatcher (for lighttpd).
class FourOhFour < PageHandler
	declare_handlers("errors") {
		area :Internal

		page :GetRequest, :Full, :not_found, "404", remain
		page :GetRequest, :Full, :access_denied, "403", remain
		handle :GetRequest, :not_changed, "304", remain
		page :GetRequest, :Full, :error_not_found, remain
	}

	# handles all urls that don't get matched elsewhere.
	def not_found(remain)
		self.status = "404";
		msg = params["message", String];
		puts(%Q{<div class="bgwhite"><h1>404 Not Found Error</h1> #{msg}</div>});
	end
	
	def access_denied(remain)
		exception = params.to_hash['exception']
		if (exception.kind_of?(PageRequest::AccessLevelError) && exception.url_to_fix)
			area = remain[0].to_sym
			if (area == :Internal && remain[1] == 'webrequest')
				referer = url("http:/")/remain[2..remain.length] # this probably doesn't belong here...
			else
				referer = $site.area_to_url([area, exception.request_user])/remain[1..remain.length]
			end
			external_redirect(exception.url_to_fix&{:referer => referer}, false)
		end
		self.status = "403";
		msg = params["message", String, "Unknown Error"];
		puts(%Q{<div class="bgwhite"><h1>403 Forbidden</h1> #{msg}</div>});
	end
	
	def not_changed(remain)
		self.status = "304"
	end

	def error_not_found(remain)
		self.status = remain[0];
		msg = params["message", String, "Unknown Error"];
		token = Time.now.strftime("%H:%M:%S:%Y-%m-%d") + ":" + PageRequest.top.token
		puts(%Q{<div class="bgwhite"><h1>#{remain[0]} #{msg}</h1> <p>#{msg}</p><p><b>When reporting this error, please include the following token: #{token}</b></p></div>});
	end
end
