require "core/pagehandlers/404";

class DevErrors < FourOhFour # to output the default 500 error.
	declare_handlers("errors") {
		area :Internal
		handle :GetRequest, :exception, "500", remain
	}

	def exception(remain)
		error = params.to_hash['original_exception'];
		backtrace = params.to_hash['original_backtrace'];

		error_not_found([500] + remain);

		if (error && backtrace)
			puts(%Q{<div class="bgwhite"><h2>Debug Info</h2>});
			$log.to(:page) {
				$log.error(error, backtrace);
			}
			puts("</div>");
		end
	end
end
