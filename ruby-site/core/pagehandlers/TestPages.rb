class TestUris
	def uri_info(type = 'stuff')
		return ['hello', "/Whatever.#{type}"];
	end

	def img_info(type = 'thumb')
		return ['imger', "/Whatoover.#{type}"];
	end
end

class TestPages < PageHandler
	declare_handlers("test_stuff") {
		area :Public
		access_level :Any

		handle :GetRequest, :test_post_form, "test_post"
		handle :PostRequest, :test_post, "test_post", "run"
		handle :GetRequest, :test_arg, input(/^[0-9]+$/), 'test'
		page   :GetRequest, :Full, :default_page, "page"
		handle :GetRequest, :default_page
		page   :GetRequest, :Full, :sean_test, "sean"
		page	 :GetRequest, :Full, :storable_test, "storable_test"

		handle :GetRequest, :object_test, 42

		access_level :LoggedIn
		handle :GetRequest, :test_page, "test_loggedin";
		access_level :NotLoggedIn
		handle :GetRequest, :test_page, "test_notloggedin";
		access_level :Plus
		handle :GetRequest, :test_page, "test_plus";
		access_level :Admin
		handle :GetRequest, :test_page, "test_admin";


		access_level :LoggedIn
		handle :GetRequest, :test_page, "test_isuser";
		
		access_level :NotLoggedIn
		handle :GetRequest, :test_page, "test_notloggedin";
		
		access_level :Activated
		handle :GetRequest, :test_page, "activated";
		
		area :Self
		access_level :IsUser, CoreModule, :impersonate
		handle :GetRequest, :test_page, "destination"
		handle :GetRequest, :test_self

		area :User
		access_level :IsUser
		handle :GetRequest, :test_page, "test";
	}
	
	def test_self()
		$log.info(["test page", "ran the test page..."], :info, :admin)
		print(%Q{<a href="#{request.area_base_uri/:test_stuff/:destination}">To the Destination</a>})
	end


	def test_page()
		print("Hello, this is a test.");
	end

	def test1()
		test_count = 40;
		runs = 100;

		require 'core/lib/template/template'
		require 'blogs/lib/blog'
		t = Template::instance("blogs", "index2", self);
		t.user = [{'userid' => 1, 'username' => "thomas"},
		{'userid' => 2, 'username' => "kenny"},
		{'userid' => 3, 'username' => "john"},
		{'userid' => 4, 'username' => "jason"}];


		puts t.display();
	end

	def test_arg(num)
		print("Hello. Your number is #{num}!\n");
	end

	def test_post_form()
		print("<form method='post' action='/test_stuff/test_post/run'><input type=text name=hello value=yournamehere /><input type=text name=age value=youragehere /><input type=submit value=run /></form>");
	end

	def test_post()
		#params.html_dump();
		puts("Hello, " + params['hello', String, ""] + ". You are " + params['age', Integer, 0].to_s + " years old!");
	end

	class Blorp
		extend UserContent
		attr :str
		def initialize(str)
			@str = str
		end
		user_content :str
	end
	
	def default_page()
		#PageHandler.list_uri(url/:test_stuff, :User).html_dump
		#PageHandler.query_uri(:GetRequest, url/:test_stuff, :Public).html_dump
		
#		request.reply.out.buffer = false
#		(1..100000000000).each {|i|
#			print("#{i}\n")
#		}

		print("Hello, this is the default page -- I think.<br/>");
=begin		
		x = Blorp.new(%Q{<img src="http://blah"/> &stuff <3 <br><a href="mailto:blorp" zref="woop" style="position: absolute">blah</a><b> <ul><li>blah
			<object width="464" height="392"><param name="movie" value="http://embed.break.com/NDc5ODUx"></param><embed src="http://embed.break.com/NDc5ODUx" type="application/x-shockwave-flash" width="464" height="392"></embed></object><br><font size=1><a href="http://break.com/index/genius-interviewed-about-aliens.html">Genius Interviewed About Aliens</a> - Watch more <a href="http://www.break.com/">free videos</a></font>
			<embed style="width:400px; height:326px;" id="VideoPlayback" type="application/x-shockwave-flash" src="http://video.google.com/googleplayer.swf?docId=-4974994628499449162&hl=en-CA" flashvars=""> </embed>
			<embed width="448" height="361" type="application/x-shockwave-flash" wmode="transparent" src="http://i212.photobucket.com/player.swf?file=http://vid212.photobucket.com/albums/cc38/babes_photobucket/CIMG0211.flv&amp;sr=1">
			<object width="425" height="355"><param name="movie" value="http://www.youtube.com/v/-GNApD8yoTM&hl=en"></param><param name="wmode" value="transparent"></param><embed src="http://www.youtube.com/v/-GNApD8yoTM&hl=en" type="application/x-shockwave-flash" wmode="transparent" width="425" height="355"></embed></object>
			<object type="application/x-shockwave-flash" width="400" height="225" data="http://www.vimeo.com/moogaloop.swf?clip_id=419344&amp;server=www.vimeo.com&amp;fullscreen=1&amp;show_title=1&amp;show_byline=1&amp;show_portrait=0&amp;color=">	<param name="quality" value="best" />	<param name="allowfullscreen" value="true" />	<param name="scale" value="showAll" />	<param name="movie" value="http://www.vimeo.com/moogaloop.swf?clip_id=419344&amp;server=www.vimeo.com&amp;fullscreen=1&amp;show_title=1&amp;show_byline=1&amp;show_portrait=0&amp;color=" /></object><br /><a href="http://www.vimeo.com/419344/l:embed_419344">Pause</a> from <a href="http://www.vimeo.com/sjogren/l:embed_419344">Aaron Sjogren</a> on <a href="http://vimeo.com/l:embed_419344">Vimeo</a>.
			<object width="460" height="390"><param name="movie" value="http://media.imeem.com/s/cdr5rYCuCu/aus=false/"></param><param name="allowFullScreen" value="true"></param></param><param name="salign" value="lt"></param><param name="scale" value="noscale"></param><embed src="http://media.imeem.com/s/cdr5rYCuCu/aus=false/" allowFullScreen="true" scale="noscale" type="application/x-shockwave-flash" width="460" height="390"></embed></object>
			<object width="400" height="345"><param name="movie" value="http://media.imeem.com/v/SDyLPCvheA/aus=false/pv=2"></param><param name="allowFullScreen" value="true"></param><embed src="http://media.imeem.com/v/SDyLPCvheA/aus=false/pv=2" type="application/x-shockwave-flash" width="400" height="345" allowFullScreen="true"></embed></object>
			<object width="400" height="345"><param name="movie" value="http://media.imeem.com/v/SDyLPCvheA/pv=2"></param><param name="allowFullScreen" value="true"></param><embed src="http://media.imeem.com/v/SDyLPCvheA/pv=2" type="application/x-shockwave-flash" width="400" height="345" allowFullScreen="true"></embed></object>
			})
		print(x.str.parsed)
		
		salt = rand
		print(%Q{
			  <form action="/tynt/send_message" method="post">
				Fromid: <input type="text" name="fromid" value="#{request.session.userid}"/><br/>
				Toid: <input type="text" name="toid"/><br/>
				Sessionkey: <input type="text" name="sessionkey" value="#{request.session.sessionid}"/><br/>
				Subject: <input type="text" name="subject"/><br/>
				Salt: <input type="text" name="salt" value="#{salt}"/><br/>
				Salt+Message (do not remove salt): <input type="text" name="message" value="#{salt}"/>
				<input type="submit"/>
			  </form>
			  })
=end
	end
	module TestStuff
		attr :boom, true
	end
	def self.default_page_query(info)
		info.extend(TestStuff)
		info.boom = "wompa"
		return info
	end

	def object_test()
		print("boom")
	end

	def template_test()
		t = Template::instance("core", "login");
		#t.poop = 4;
		#t.thing = "Even Greater!";
		t.display()
	end

	def sean_test
		t = Template::instance("core", "sean")
		
		emoticons = ""
		request.skeleton.smilies.each do |key, value|
			emoticons << "<td valign=\"middle\"><img src=\"#{$site.static_files_url}/smilies/#{value}.gif\" alt=\"#{key}\" /></td>"
		end
		t.emoticons = "<div style=\"display: none;\">
		<table id=\"emoticons_list\" height=\"30\" width=\"100%\"><tr>#{emoticons}</tr></table>
		<img id=\"emoticon_left_arrow_img\" src=\"#{$site.static_files_url}/core/enhanced_text_input/images/icon_left_arrow.gif\" />
		<img id=\"emoticon_right_arrow_img\" src=\"#{$site.static_files_url}/core/enhanced_text_input/images/icon_right_arrow.gif\" />
		<img id=\"emoticon_left_back_img\" src=\"#{$site.static_files_url}/core/enhanced_text_input/images/left_arrow_back.png\" />
		<img id=\"emoticon_right_back_img\" src=\"#{$site.static_files_url}/core/enhanced_text_input/images/right_arrow_back.png\" />
		</div>"

    t.hack = "</tr><tr>"

    # t.skin_list = SkinMediator.request_skin_list.sort!
    # t.skin_list.delete("default")
		t.skin_list = Array.new;

		rows = 3
		t.skin_list_height = rows*100 + 2*rows*5 + 20;

    skins = SkinMediator.request_skin_list(request.skeleton).sort!
    skins.delete("default")
    cols = (skins.length / rows.to_f).ceil
		t.skin_list.push skins.slice!(0,cols) while skins.length > cols
		t.skin_list.push skins
		unless params['skin', String] == nil then
			t.skin = params['skin', String]
			t.skin_properties = SkinMediator.request_all_values(request.skeleton, t.skin = params['skin', String])
		end

		unless params['property', String] == nil then
			t.property = params['property', String]
			t.skin_property_value = SkinMediator.request_skin_value(request.skeleton, t.skin = params['skin', String], params['property', String])
		end

		t.myvar = "Sean is #{params['msg',String]}."
		print t.display
	end

	
	def storable_test
		ip = -1062729173;
		puts "Banned User Check for: #{ip.to_s}<br/>";
		banned = BannedUsers.find(:first, ip);
		puts "nil (1st access)?: #{banned.nil?.to_s}<br/>";
		banned = BannedUsers.find(:first, ip);
		puts "nil (2nd access)?: #{banned.nil?.to_s}<br/>";
		banned = BannedUsers.find(:first, ip);
		puts "nil (3rd access)?: #{banned.nil?.to_s}<br/>";
	end

end

