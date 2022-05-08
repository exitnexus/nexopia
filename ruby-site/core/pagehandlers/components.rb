lib_require :Core, "template/template"
lib_want :Blogs, 'blog_post'

class Components < PageHandler
	declare_handlers("/") {
		area :Public
		access_level :Any

		handle :GetRequest, :header, "header7"
		handle :GetRequest, :randuser, "randuser"

		if (site_module_loaded? :Blogs)
			handle :GetRequest, :randblog, "randblog"
		end

		#access_level :LoggedIn
		#handle :GetRequest, :profile, "profile"
	}

	def header()
		t = Template::instance("core", "header", self);
		puts t.display();
	end

	def randuser()
		results = $site.dbs[:usersdb].query("SELECT * FROM usernames ORDER BY RAND() LIMIT 1");
		results.each { |line |
			user = User.get_by_id(line['userid'].to_i);
			puts "User: " + user.username.to_s;
			return;
		}
	end

	if (site_module_loaded? :Blogs) # THIS SHOULD NOT BE HERE
		def randblog()
			blogs = Array.new

			blog = Blog.random_blog();
			if (blog)
				puts %Q| <b class="title">#{blog.user.username.slice(0...32)}</b><br/> |;
				puts %Q| <b class="info">#{blog.title.slice(0...32)}</b><br/> |;
				puts %Q| #{blog.msg.slice(0...32)} <a href="/">&gt;more</a> |;
			end
		end
	end
end
