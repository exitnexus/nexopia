lib_require :Core, 'users/user_name'
lib_require :Profile, "user_skin";

class BrowserInfo < PageHandler
	declare_handlers("browserinfo") {
		area :Public
		access_level :Any

		page :GetRequest, :Full, :info;
	}
	
	def info()
		t = Template.instance("debug", "browserinfo");
		
		t.headers = request.headers.select { |key, value|
			["HTTP_USER_AGENT",
			 "HTTP_CACHE_CONTROL",
			 "REMOTE_ADDR",
			 "HTTP_ACCEPT_CHARSET",
			 "HTTP_COOKIE",
			 "X_FORWARDED_FOR",
			 "X-FORWARDED-FOR"].include?(key.upcase)
		};
		
		user = request.session.user;
		if user.kind_of?(AnonymousUser)
			t.username = "Anonymous user"
			t.userid = "n/a";
			t.userskin = "n/a";
			t.userskintype = "n/a";
			t.userstate = "n/a";
			t.userfirstpic = "n/a";
			t.usersignpic = "n/a";
		else
			t.username = user.username;
			t.userid = user.userid;
			t.userskin = user.skin;
			t.userskintype = user.skintype;
			t.userstate = user.state;
			t.userfirstpic = user.firstpic;
			t.usersignpic = user.signpic;
		end
		
		t.cookiesessionkey = request.cookies['sessionkey'].first;
		t.session = request.session.to_s;
		
		print t.display();
	end
end
