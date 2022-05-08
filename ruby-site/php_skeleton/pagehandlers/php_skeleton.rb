class PhpSkeletonPages < PageHandler
	declare_handlers("PhpSkeleton") {
		area :Skeleton
		handle :GetRequest, :full_page, "skin", "Full", input(String), remain
		handle :GetRequest, :php_page, "skin", "Php", input(String), remain
		
		handle :GetRequest, :default, remain
	}
		
	def full_page(area, real_path)
		out = StringIO.new();
		req = subrequest(out, request.method, (url/real_path).to_s + ":Body", request.params.to_hash, area.to_sym);
		template_args = {}
		req.reply.headers.each{|key,val|
			if (key['X-'])
				template_args[key] = val;
			else
				reply.headers[key] = val;
			end
		}

		needed_modules = PageHandler.modules.collect{|mod| mod.javascript_dependencies + [mod] }.flatten.uniq
		mod_locations = needed_modules.collect{|mod| mod.directory_name }

		template_args["X-modules"] = mod_locations.join("/")
		template_args["X-skeleton"] = PageHandler.pagehandler_module(self.class)

		template_out = StringIO.new();
		template_req = subrequest(template_out, :PostRequest, "/rubytemplate.php", template_args, :Public);

		templ = template_out.string;
		templ.sub!("<!--RubyReplaceThis-->", out.string);
		puts templ
	end
	
	def php_page(area, real_path)
		rewrite(request.method, (url/real_path).to_s + ":Body", request.params.to_hash, area.to_sym);
	end
	
	# push it off to nexoskel.
	def default(remain)
		rewrite(request.method, url/:Nexoskel/remain, params.to_hash, :Skeleton)
	end
end
