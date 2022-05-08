class PhpSkeletonPages < PageHandler
	declare_handlers("PhpSkeleton") {
		area :Skeleton
		handle :GetRequest, :full_page, "skin", "Full", input(String), remain
		handle :GetRequest, :php_page, "skin", "Php", input(String), remain
		
		handle :GetRequest, :default, remain
	}
	
	def enable_timeline_log()
		if ($site.debug_user?(request.session.userid))
			$log.log_minlevel_lower(:pagehandler, :debug) {
				$log.log_minlevel_lower(:sql, :debug) {
					$log.log_minlevel_lower(:memcache, :debug) {
						$log.to(:request_buffer_timeline) {
							yield
						}
					}
				}
			}
		else
			yield
		end
	end
		
	def full_page(area, real_path)
		enable_timeline_log {
			if(!request.session.user.anonymous?())
				active_time = UserActiveTime.new();
				active_time.userid = request.session.user.userid;
				active_time.hits = 1;
			
				active_time.store(:duplicate, :increment => [:hits, 1]);
			end
		
			obj = Object.new
			def obj.init(handler,request, real_path, area)
				@handler=handler;
				@request=request;
				@real_path=real_path;
				@area=area;
			end
			obj.init(self,request,real_path,area);
			def obj.generate()
				Dir.chdir($site.config.site_base_dir)		

				out = StringIO.new();
				if(@area.to_s == "User")
					req = @handler.subrequest(out, @request.method, (url/$site.config.page_skeleton/:user/:header/:users/@request.user.username/@real_path).to_s + ":Body", nil, :Skeleton);
				else
					req = @handler.subrequest(out, @request.method, (url/@real_path).to_s + ":Body", nil, @area.to_sym);
				end

				@template_args = {}
			
				PageHandler.top[:modules] << SiteModuleBase.get(:Interstitial);
			
				needed_modules = PageHandler.modules.collect{|mod| mod.javascript_dependencies + [mod] }.flatten.uniq.compact
				paths = needed_modules.map{|mod|
					mod.javascript_config.javascript_paths
				}
				paths = paths.flatten.uniq
			
				@template_args["X-scripts"] = paths
				@template_args["X-skeleton"] = PageHandler.pagehandler_module(@handler.class).to_s

				req.reply.headers.each{|key,val|
					if (key['X-'])
						@template_args[key] = val;
					else
						@handler.reply.headers[key] = val;
					end
				}
			
				return out.string
			end
		
			def obj.fetch(key)
				return @template_args[key]
			end
		
			post_vars = {};
			post_vars["X-output"] = obj;
		 
			template_out = StringIO.new();
			template_req = subrequest(template_out, :PostRequest, "/rubytemplate.php", post_vars, :Public);

	

			templ = template_out.string;
			#templ.sub!("<!--RubyReplaceThis-->", out.string);
			puts templ
		}
	end
	
	def php_page(area, real_path)
		if(!request.session.user.anonymous?())
			active_time = UserActiveTime.new();
			active_time.userid = request.session.user.userid;
			active_time.hits = 1;
			
			active_time.store(:duplicate, :increment => [:hits, 1]);
		end
		
		rewrite(request.method, (url/real_path).to_s + ":Body", nil, area.to_sym);
		
		puts tynt_include(area, real_path);
	end	
	
	# push it off to nexoskel.
	def default(remain)
		rewrite(request.method, url/:Nexoskel/remain, nil, :Skeleton)
	end
end
