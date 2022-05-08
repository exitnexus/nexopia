require "storable"

class Test < PageHandler
	declare_handlers("/") {
		area :Public
		access_level :Any

		handle :GetRequest, :test_page, "test"
		handle :GetRequest, :index_page, "index"
		handle :GetRequest, :test_post_form, "test_post"
		handle :PostRequest, :test_post, "test_post", "run"
		handle :GetRequest, :test_arg, input(/^[0-9]+$/), 'test'
		handle :GetRequest, :default_page

		access_level :LoggedIn
		handle :GetRequest, :test_page, "test_loggedin";
		access_level :NotLoggedIn
		handle :GetRequest, :test_page, "test_notloggedin";
		access_level :Plus
		handle :GetRequest, :test_page, "test_plus";
		access_level :Admin
		handle :GetRequest, :test_page, "test_admin";

		area :User
		access_level :Any

		handle :GetRequest, :default_page
		access_level :IsUser
		handle :GetRequest, :test_page, "test_isuser";
	}

	def test_page()
		print("Hello, this is a test.");
	end

	def index_page()
		load("index.rb");
	end

	def test_arg(num)
		print("Hello. Your number is #{num}!\n");
	end

	def test_post_form()
		print("<form method='post' action='/test_post/run'><input type=text name=hello value=hello /><input type=submit value=run /></form>");
	end

	def test_post()
		cgi.params.html_dump();
	end

	def default_page()
		print("Hello, this is the default page: " + cgi.script_name);
		$config.html_dump();
	end
end
