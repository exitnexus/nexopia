
lib_require :Blogs, "blog";
lib_require :Blogs, "blogcomments";
lib_require :Blogs, "blogcommentsunread";
lib_require :Blogs, "bloglastreadfriends";

class BlogHandler < PageHandler
	declare_handlers("blog") {
		area :Public
		access_level :Any

		handle :GetRequest, :index
		handle :GetRequest, :list,     "list"

		handle :GetRequest, :tinymce,  "tinymce"
		handle :GetRequest, :insert,   "insert"
		handle :GetRequest, :calendar, "calendar"
		handle :GetRequest, :friends,  "friends"
	}


	def index()
		t = TemplateB.new("blogs/templates/index.html")

		if (blogs = Blog.someUsers(t.user.tag.times))
			blogs.each {|blog|
				t.user.userid   = blog.userid
				t.user.username = blog.user.username if blog.user
			}
		else
			t.empty.tag.show = Template::Once
		end

		puts t.display
	end

	def list()
		t = Template1.new("blogs/templates/list.html")

		blogs = Blog.allEntries( params["userid", Integer, 0] )

		blogs.each {|x|
			time = Time.at(x.time)

			t.list.title = x.title
			t.list.msg   = x.msg
			t.list.date  = time.strftime("%A %B, %d, %Y, %I:%M ") + time.strftime("%p").downcase
			t.list.scope = x.scope

			t.list.reply.num_replies = x.time if (x.allowcomments == true)
		}

		t.display
	end

	def insert()
		puts "insert "
	end

	def calendar()
		puts "calendar "
	end

	def friends()
		puts "friends "
	end
end
