class NexoSkinPage < PageHandler
	declare_handlers("Nexoskel") {
		area :Skeleton
		handle :GetRequest, :full_page, "skin", "Full", input(String), remain
		handle :GetRequest, :full_page, "skin", "Php", input(String), remain
		handle :GetRequest, :blank, "skin", "blank"

		handle :GetRequest, :php_index, "index"
		access_level :LoggedIn;
		handle :GetRequest, :mynex_menu, "skin", "menu", "Self"

	}

	# this handler should be removed when RAP can take over.
	declare_handlers("/") {
		area :Public
# 		handle :PostRequest, :rubytemplate_php, "rubytemplate.php"
	}

	def blank
	puts %q|
		<html>
		   <script language="JavaScript">
		            function pageLoaded() {
		               window.parent.dhtmlHistory.iframeLoaded(window.location);
		            }
		   </script>
		   <body onload="pageLoaded()">
		      <h1>blank.html - Needed for Internet Explorer's hidden IFrame</h1>
		   </body>
		</html>
	|;
		puts "";
	end

    def NexoSkinPage.styles
	   return { "Blue" => "newsite",
	            "Gray" => "newsite_gray" }
    end

	
	def full_page(area, real_path)
		out = StringIO.new();
		req = subrequest(out, request.method, (url/real_path).to_s + ":Body", request.params.to_hash, area.to_sym);
		
		x_headers = {};
		req.reply.headers.each{|key,val|
			if (key =~ /^X-/)
				x_headers[key] ||= [];
				x_headers[key] << val;
			else
				reply.headers[key] = val;
			end
		}
		$log.object x_headers, :critical

		#template_args["X-modules"] = PageHandler.modules.join("/")
		#template_args["X-skeleton"] = PageHandler.pagehandler_module(self.class)

		#template_out = StringIO.new();
		#template_req = subrequest(template_out, :PostRequest, "/rubytemplate.php", template_args, :Public);

		#templ = template_out.string;
		#templ.sub!("<!--RubyReplaceThis-->", out.string);
		#puts templ
		
		puts RAPminiHandler::RAP_page(out.string, x_headers)
	end

	def mynex_menu()
		t = Template::instance("core", "mynex");
		puts t.display();
	end
	
	def php_index
		rewrite(:GetRequest, "/index.php:Page", nil, :Public)
	end
end
