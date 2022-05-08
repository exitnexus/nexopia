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

	def default_page()
		#PageHandler.list_uri(url/:test_stuff, :User).html_dump
		#PageHandler.query_uri(:GetRequest, url/:test_stuff, :Public).html_dump
		
#		request.reply.out.buffer = false
#		(1..100000000000).each {|i|
#			print("#{i}\n")
#		}
		
		print("Hello, this is the default page<br/>");
		
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

end

