# This pagehandler handles the initial mapping of domain names to
# areas, as well as some other internal handling.
lib_require :core, "template/css/css_trans", 'skin_mediator'

class WebRequestHandler < PageHandler
	declare_handlers("webrequest") {
		area :Internal

		access_level :Any
		handle :GetRequest, :user, $site.config.user_url, input(String), remain

		# translates requests from full urls to the area they belong in.
		handle :GetRequest, :area_dispatch, remain

		handle :GetRequest, :ajax_dispatcher, input(String), "fetch-page", remain

		access_level :LoggedIn
		handle :GetRequest, :self, $site.config.self_url, remain

		handle :GetRequest, :admin, $site.config.admin_url, remain

		access_level :Admin, CoreModule, :impersonate
		handle :GetRequest, :admin_self, $site.config.admin_self_url, remain		
	}

	declare_handlers('/') {
		area :Public
		handle :GetRequest, :index, "index"
		handle :GetRequest, :favicon, "favicon.ico"

		access_level [:Admin, :impersonate]
		area :User
		handle :GetRequest, :admin_self, "my", remain # passes in to self area with target user changed.
	}

	def area_dispatch(orig_remain)
		domain = orig_remain[0]
		area, remain = $site.url_to_area(orig_remain)
		if (remain.length == 0) # this is something like http://blah/, translate it to http://blah/index.
			remain = ["index"]
		end
		if (area)
			$log.info("Dispatching request to: #{area}#{remain}", :spam, :pagehandler)
			rewrite(request.method, remain, nil, area)
		else
			force_server = request.cookies['forceserver'].first
			force_server = force_server && force_server != 0
			force_server = force_server || orig_remain[1] =~ /forceserver/
			path = orig_remain[1..-1]
			if (!force_server)
				$log.info("Accessed unknown domain #{domain || 'No Domain'}, redirecting to #{url($site.www_url)/path}.", :debug, :pagehandler)
				external_redirect(url($site.www_url)/path, :Public)
			else
				$log.info("Accessed unknown domain #{domain || 'No Domain'} with forceserver, rewriting to #{url/:webrequest/$site.config.www_url/path}", :debug, :pagehandler)
				rewrite(request.method, url/:webrequest/$site.config.www_url/path, params.to_hash, :Internal)
			end
		end
	end

	def self(remain)
		remain = remain.collect {|component| urlencode(component) }
		$log.info("Handling self request for /#{remain.join('/')}", :debug, :pagehandler);
		rewrite(request.method, "/#{remain.join('/')}", nil, [:Self, session.user]);
	end

	def user(username, remain)
		remain = remain.collect {|component| urlencode(component) }
		if (/^[0-9]+$/.match(username))
			userobj = User.find(username.to_i, :first)
			if (userobj)
				site_redirect("/", [:User, userobj])
			end
		else
			userobj = User.get_by_name(username);
		end
		if (userobj)
				if(!userobj.visible?(request.session.user) && !request.session.has_priv?(CoreModule, "listusers"))
					if(request.session.user.anonymous?())
						$log.info("User(#{username}) requires viewer be logged in", :debug, :pagehandler);
						redirect_url = url / :users / username / remain;
						site_redirect(url / :account / :join & {:referer => redirect_url});
					elsif(userobj.frozen?)
						$log.info("User(#{username}) is frozen and attempted to view by #{request.session.user.username}", :debug, :pagehandler);
						rewrite(:GetRequest, url / :profile / :frozen, nil, :Public);
					else
						$log.info("User(#{username}) has blocked viewer(#{request.session.user.username})", :debug, :pagehandler);
						rewrite(:GetRequest, url / :profile / :blocked, nil, :Public);
					end
				end
			
			$log.info("Handling user(#{username}) request for /#{remain.join('/')}", :debug, :pagehandler);
			rewrite(request.method, "/#{remain.join('/')}", nil, [:User, userobj]);
		else
			raise PageError.new(404), "User #{username} Not Found";
		end
	end

	def user_domain(regex, remain)
		user(regex[1], remain);
	end

	def admin_self(remain)
		username = remain.shift
		user = User.get_by_name(username)
		if (!user)
			raise PageError.new(404), "User #{username} Not Found"
		end
		$log.log_minlevel_lower(:admin, :info) {
			$log.info("Handling admin impersonation request for /#{remain.join('/')} by admin #{session.user.username} to user #{username}", :debug, :pagehandler);
			$log.info(["impersonation", "Handling admin impersonation request for /#{remain.join('/')} by admin #{session.user.username} to user #{username}"], :debug, :admin);
			begin
				remain = [:index] if (remain.empty?)	
				rewrite(request.method, url/remain, nil, [:Self, user]);
			ensure
				$log.info(["impersonation", "Done admin impersonation request for /#{remain.join('/')} by admin #{session.user.username} to user #{username}"], :debug, :admin);
				request.log[:admin] && request.log[:admin].each {|logitem|
					logstr = logitem.realstr
					if (!logstr.kind_of? Array)
						action = remain.join('/')
					else
						action, logstr = logstr
					end
					$site.dbs[:moddb].query("INSERT INTO adminlog SET userid = ?, ip = ?, time = ?, page = ?, action = ?, description = ?", session.user.userid, request.get_ip_as_int, Time.now().to_i, url("/my")/remain, action, logstr)
				}
			end
		}
	end
	
	def admin(remain)
		$log.log_minlevel_lower(:admin, :info) {
			$log.info("User #{session.user.username} entering admin page #{remain.join('/')}", :debug, :pagehandler)
			$log.info(["admin", "User #{session.user.username} entering admin page #{remain.join('/')}"], :debug, :admin)
			begin
				remain = [:index] if (remain.empty?)	
				rewrite(request.method, url/remain, nil, :Admin)
			ensure
				$log.info(["admin", "User #{session.user.username} done admin page #{remain.join('/')}"], :debug, :admin)
				request.log[:admin] && request.log[:admin].each {|logitem|
					logstr = logitem.realstr
					if (!logstr.kind_of? Array)
						action = remain.join('/')
					else
						action, logstr = logstr
					end
					$site.dbs[:moddb].query("INSERT INTO adminlog SET userid = ?, ip = ?, time = ?, page = ?, action = ?, description = ?", session.user.userid, request.get_ip_as_int, Time.now().to_i, url("/admin")/remain, action, logstr)
				}
			end
		}
	end

	def index()
		# rewrite the index to the right skeleton.
		rewrite(request.method, url/:current/:index, nil, :Skeleton)
	end
	
	def favicon()
		# rewrite to the skeleton's copy of favicon.ico.
		rewrite(request.method, url/0/:files/request.skeleton.name/"favicon.ico", nil, :Static)
	end

	def complete_subrequest_with_timeline(subreq)
			reply.headers["Content-Type"] = PageRequest::MimeType::XML;
			puts(%Q{<?xml version="1.0"?>
			<subreq xmlns="http://www.nexopia.com/dev/pagehandler">
				});
				subreq.reply.headers.each {|name, value|
					puts(%Q{<header name="#{CGI.escapeHTML(name)}">#{CGI.escapeHTML(value)}</header>});
				}
				case subreq.reply.headers["Content-Type"]
				when "text/xml"
					puts("<body>#{subreq.reply.out.string}</body>");
				when /html/
					puts(%Q{<body xmlns="http://www.w3.org/1999/xhtml">#{subreq.reply.out.string}</body>});
				else
					puts(%Q{<body><![CDATA[#{subreq.reply.out.string}]]></body>});
				end

				timeline = subrequest(StringIO.new(), :GetRequest, "/log/timeline/xml", {}, :Internal);
				if (timeline.reply.ok?)
					puts(timeline.reply.out.string);
				end

			puts("</subreq>");
	end
	def redirect_after_subrequest(host,subreq)
		if (matches = /^http:\/\/#{host}(.+)$/.match(subreq.reply.headers["Location"]))
			redirect_to = "http://#{host}/fetch-page#{matches[1]}";
			reply.headers["Status"] = subreq.reply.headers["Status"];
			redirect_path = "http://#{host}/fetch-page#{matches[1]}";
			reply.headers["Location"] = "#{redirect_path}";
			puts(%Q{Redirecting to <a href="#{redirect_path}">#{redirect_path}</a>});
		else
			raise SiteError, "Attempted off-site redirect in ajax-dispatcher.";
		end
	end
	# fetch-page fetches a page as an xmlhttp request and includes header
	# information and some logging info.
	def ajax_dispatcher(host, remain)
		remain = remain.collect {|component| urlencode(component) }

		subreq = subrequest(StringIO.new(), request.method, "/webrequest/#{host}/#{remain.join '/'}",
		                    nil, :Internal);
		#
		# Copy Cookies into current reply context
		reply.merge_cookies(subreq.reply)

		#
		# Dispatch Terminating condition. Either
		#
		if (/^30/.match(subreq.reply.headers["Status"].to_s))
		 		 self.redirect_after_subrequest(host,subreq)
		else
				 self.complete_subrequest_with_timeline(subreq)
		end
	end
end
