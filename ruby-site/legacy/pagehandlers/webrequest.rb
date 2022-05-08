module Legacy
	class WebRequestHandler < PageHandler
		declare_handlers("webrequest") {
			area :Internal
			handle :GetRequest, :legacy, $site.config.legacy_domain, remain
			handle :GetRequest, :legacy_plus, "plus.#{$site.config.www_url[0]}", remain

			# Handle specific areas that should be forwarded on to the legacy module.
			handle :GetRequest, :legacy_static, $site.config.legacy_static_domain, input('smilies'), input(/^.*\.gif$/)
			handle :GetRequest, :legacy_static, $site.config.legacy_static_domain, input('images'), input('music_section'), input(/^.*\.png$/)
		}

		def legacy_static(*remain)
			remain = remain.collect {|i|
				if (i.kind_of? MatchData)
					i[0]
				else
					i
				end
			}
			rewrite(request.method, url/:webrequest/$site.config.static_url/$site.static_number/:files/:Legacy/remain, nil, :Internal)
		end

		def legacy_plus(remain)
			if (request.method == :PostRequest)
				rewrite(request.method, url/:webrequest/$site.config.www_url/remain, nil, :Internal)
			else
				external_redirect(url("http:/")/$site.config.www_url/remain)
			end
		end

		def legacy(remain)
			# TODO: Make it pass on headers and cookies.
			out = StringIO.new();
			
			req = subrequest(out, request.method, (url/:webrequest/$site.config.www_url/remain).to_s + ":Page", nil, :Internal);
			
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
