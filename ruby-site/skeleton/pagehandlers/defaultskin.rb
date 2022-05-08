class DefaultSkinPage < PageHandler
	declare_handlers("skin/default") {
		handle :GetRequest, :full_page, "Full", input(String), remain
		handle :GetRequest, :blank, "blank"

		access_level :LoggedIn;
		handle :GetRequest, :mynex_menu, "menu", "Self"
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

    def DefaultSkinPage.styles
	   return { "Blue" => "newsite",
	            "Gray" => "newsite_gray" }
    end

	def full_page(area, real_path)
		# TODO: Make it pass on headers and cookies.
		t = Template::instance("skeleton", "skin", self);
		out = StringIO.new();
		req = subrequest(out, request.method, "/#{real_path.join('/')}:Body", request.params.to_hash, area.to_sym);
		req.reply.headers.each{|key,val|
			reply.headers[key] = val;
		}

		t.skin = "default";
		t.page = out.string;
		#t.page = "/#{real_path.join('/')}:#{request.selector}";
		if (!session.anonymous?() && !session.user.anonymous?())
			t.user = session.user;
		else
			t.user = nil;
		end
		t.area = area;

		if (session.anonymous?() || session.user.anonymous?() || session.user.skin == "" || ! DefaultSkinPage::styles.include?(session.user.skin) )
    		t.style = DefaultSkinPage::styles["Blue"]
    	else
    		t.style = session.user.skin
    	end

		t.menuitems = [ ["index"  => ""], ["browse" => ""],           ["nexfeed"   => ""],
						["games"  => ""], ["music"  => ""],           ["skins"     => ""],
						["forums" => ""], ["blogs"  => "blog"],       ["galleries" => ""],
						["plus"   => ""], [""       => "my/profile"], ["help"      => ""]];
		content_type = request.html_content_type;
		reply.headers["Content-Type"] = content_type;
		if (content_type == PageRequest::MimeType::XHTML)
			puts %Q|<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">|;
		else
			puts %Q|<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/1999/REC-html401-19991224/loose.dtd">|;
		end
		puts t.display();
	end

	def mynex_menu()
		t = Template::instance("core", "mynex");
		puts t.display();
	end
end
