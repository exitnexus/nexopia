module Akamai
	class RewriteHandler < PageHandler
		declare_handlers("webrequest") {
			area :Internal
			rewrite(:GetRequest, "origin-#{$site.config.static_url.first}", remain) {|remain| url/:webrequest/$site.config.static_url.first/remain }
			rewrite(:GetRequest, "origin-#{$site.config.image_url.first}", remain) {|remain| url/:webrequest/$site.config.image_url.first/remain }
		}
	end
end