module Legacy
	class WebRequestHandler < PageHandler
		declare_handlers("webrequest") {
			area :Internal
			handle :GetRequest, :legacy, $site.config.legacy_domain, remain
			handle :GetRequest, :legacy_static, $site.config.legacy_static_domain, remain
			handle :GetRequest, :legacy_plus, "plus.#{$site.config.www_url[0]}", remain
		}

		def legacy_static(remain)
			rewrite(request.method, url/:webrequest/$site.config.static_url/$site.static_number/:files/:Legacy/remain, nil, :Internal)
		end

		def legacy_plus(remain)
			rewrite(request.method, url/:webrequest/$site.config.www_url/remain, nil, :Internal)
		end

		def legacy(remain)
			# TODO: Make it pass on headers and cookies.
			out = StringIO.new();
			req = subrequest(out, request.method, (url/:webrequest/$site.config.www_url/remain).to_s + ":Body", nil, :Internal);
			req.reply.headers.each{|key,val|
				reply.headers[key] = val;
			}
			
			#mod_locations = PageHandler.modules.collect{|mod| mod.directory_name }
			needed_modules = PageHandler.modules.collect{|mod| mod.javascript_dependencies + [mod] }.flatten.uniq
			mod_locations = needed_modules.collect{|mod| mod.directory_name }
						
			reply.headers["X-modules"] = mod_locations.join("/")
			reply.headers["X-skeleton"] = request.skeleton

			puts out.string;
		end
	end
end
