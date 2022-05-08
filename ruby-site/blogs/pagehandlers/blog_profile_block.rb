lib_want :Profile, "profile_block_query_info_module";
lib_require :Blogs, "blog_post"
lib_require :Blogs, "blog_comment"

module Blogs
	class BlogProfileBlockHandler < PageHandler
	
		declare_handlers("profile_blocks/Blogs/blog") {
			area :User
			access_level :Any
			page :GetRequest, :Full, :blog, input(Integer)
		
			area :Self
			handle :PostRequest, :blog_block_remove, input(Integer), "remove"
			handle :PostRequest, :blog_block_create, input(Integer), "create"
		}
	
		def blog(block_id)
			edit_mode = params["profile_edit_mode", Boolean, false];
		
			if(!Profile::ProfileDisplayBlock.verify_visibility(block_id, request.user, request.session.user, edit_mode))
				print "<h1>Not visible</h1>";
				return;
			end
		
			blog_post = BlogPost.recent(request.user, request.session.user);
			if (blog_post)
				t = Template.instance("blogs", "blog_profile_block");
				blog_post.msg = blog_post.msg[0, 1024];
				blog_post.msg.parsed();
				t.blog_post = blog_post;
				print t.display();
			end
		end
	
		def self.blog_query(info)
			if(site_module_loaded?(:Profile))
				info.extend(ProfileBlockQueryInfo);
					info.title = "Latest Blog Entry";
					info.initial_position = 20;
					info.initial_column = 0;
					info.editable = false;
					info.form_factor = :both;
					info.page_url = ["Blog", url/:blog, 2];
					info.page_per_block = true;
					info.max_number = 1;
				
					# changes on a per user basis because it shows your last entry the reader has a right to read.
					# if we want to make it work well, we could make it only ever show the last public entry (if any)
					info.content_cache_timeout = 0 
				end
				return info;
			end

		# the create and remove pages are expected, so if this isn't here we get an error.
		def blog_block_create(block_id)		
		end

		def blog_block_remove(block_id)
		end
	end
end
