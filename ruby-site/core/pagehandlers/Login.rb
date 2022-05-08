lib_require :Core, "session"

def loginlog(userid, status)
	$site.dbs[:usersdb].query("INSERT INTO loginlog SET userid = ?, time = ?, ip = ?, result = ?", userid, Time.now.to_i, request.headers["REMOTE_ADDR"], status);
end


class LoginPages < PageHandler
	declare_handlers("") {
		area :Public
		access_level :NotLoggedIn
		handle :GetRequest, :login_page, "login"
		handle :PostRequest, :post_login, "post_login"

		access_level :LoggedIn
		#handle :GetRequest, :already_logged_in, "login"
		handle :GetRequest, :logged_in_test, "logged"
		handle :GetRequest, :logout_page, "logout"
	}

	def logged_in_test()
		puts "This proves you are logged in, " + @session.user.username;
	end

	def logout_page()
		request.destroy_session();

		t = Template::instance("core", "forward");
		#TODO: Fix the actual template so that this doesn't throw an error. We will still need
		#to pass in the redirect url.
		#t.url     = "/login";
		#t.target  = "float_menu_body";
		t.message = "Logging out...";
		puts t.display();

	end

	def login_page()
		puts LoginPages.login_page
	end

	def LoginPages.login_page
		t = Template::instance('core', 'login')
		t.target  = "float_menu_body";
		return t.display
	end



	def post_login()
		username = params['username', String];
		password = params['password', String];

		puts("<table class=\"bgwhite\" width=\"100%\" align=\"left\"> <tr> <td>");

		if(username != nil)
			userid = UserName.find(:first, :conditions => ["username = #", username]).userid;
			if (userid != nil)
				correct_password = Password.check_password(password, userid);
				if (correct_password)
						user = User.get_by_id(userid);
						_with_valid_password(user)
				else
					puts "Password for #{username} is incorrect.";
					loginlog(userid, "badpass");
				end
			else
				puts "Username #{username} does not exist!";
			end
		end

		puts("</td></tr></table> <br /> <br />");


	end
	def _with_valid_password(user)
		state = "_unknown_"
		case user.state
			when 'new','frozen','deleted'
				state  = user.state
			else ## Valid Active User
				state = 'valid'
		end
		method = "_case_user_#{state}"
		self.send(method,user)
	end

	def _case_user_frozen(user)
			if (user.frozentime != nil && user.frozentime < Time.now.to_s)
				#$useraccounts->unfreeze($userid);
			else
				loginlog(user.userid, 'frozen');
				if(user.frozentime != nil)
					frozentime = (Time.now()-user.frozentime)/86400;
					if (frozentime < 1)
						frozentime = 1;
					end
					puts "Your account is frozen for another " + frozentime + " days.";
				else
					puts "Your account is frozen";
				end
			end
	end

	def _case_user_valid(user)
		username = user.username
		userid = user.userid
		loginlog(userid, 'success');
		request.create_session(userid, 'n');
		site_redirect('/sidebar/console')
	end
	def _case_user_deleted(user)
			loginlog(user.userid, 'deleted');
			puts "That account is deleted";
	end
	def _case_user_new(user)
			loginlog(user.userid, 'unactivated');
			puts "You must activate your account before using it";
	end
end
