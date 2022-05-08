lib_want :Profile, "profile_block_query_info_module";
lib_require :Blogs, "blog"
lib_require :Blogs, "blogcomments"

class BlogProfileBlockHandler < PageHandler
	
	declare_handlers("profile_blocks/Blogs") {
		area :User
		access_level :Any
		page :GetRequest, :Full, :blog, 'blog', input(Integer)

	}
	
	def blog(id)
		width = params['layout', Integer]
		
		reply.headers['X-width'] = 420;
		
		blog = Blog::recent(request.user, request.session.user);
		if (blog)
			t = Template::instance("blogs", "blog_profile_block");
			t.user = request.user;
			t.blog = blog;
			t.comments = BlogComments.find(:all, :blogid, blog.id)
			puts t.display();
		else
			puts "User has not made any blog entries."
		end
	end
	
	def self.blog_query(info)
		if(site_module_loaded?(:Profile))
			info.extend(ProfileBlockQueryInfo);
				info.title = "Blog";
				info.initial_position = 20;
				info.initial_column = 0;
				info.form_factor = :both;
			end
			return info;
		end

	
end
