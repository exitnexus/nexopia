lib_require :Blogs, 'blog_profile'
lib_require :Blogs, 'blog_view'
module Blogs
	class BlogPanelsPageHandler < PageHandler
	
		declare_handlers("blog/panel") {
			area :Public
			page :GetRequest, :Full, :delete_entries, 'delete'
			page :GetRequest, :Full, :change_permissions, 'permissions'
			page :GetRequest, :Full, :ignore_user, 'ignore'
			page :GetRequest, :Full, :unignore_user, 'unignore'
			page :GetRequest, :Full, :cancel_post, 'cancel_post'
						
			area :Self
			access_level :IsUser
			page :GetRequest, :Full, :blog_settings, 'settings'
			handle :PostRequest, :blog_settings_save, 'settings', 'save'
			
			page :GetRequest, :Full, :delete_comments, 'comments', 'delete'
		}
	
	
		def blog_settings
			t = Template.instance('blogs', 'blog_settings')
		
			t.blog_profile = request.user.blog_profile
			t.plus = request.user.plus?
		
			puts t.display
		end
	
	
		def blog_settings_save
			blog_profile = request.user.blog_profile
			
			default_visibility = params["visibility", Integer, nil];
			
			if(BlogVisibility.instance.inverse_visibility_list[default_visibility.to_s()].nil?())
				default_visibility = blog_profile.defaultvisibility;
			end
			
			blog_profile.defaultvisibility = default_visibility
			blog_profile.allowcomments = (params["allow_comments", String, nil] == "on")
			blog_profile.showquickpost = (params["hide_quick_post", String, nil] != "on")
			blog_profile.showhits = (params["show_hits", String, nil] != "on") if request.user.plus?
		
			blog_profile.store
			
			hits = request.user.show_hits ? "#{BlogView.views(request.user, :display)} Hits" : ""
			puts "<span id='blog_hits' class='blog_hits'>#{hits}</span>"
			t = Template::instance("blogs", "quick_post")
			t.quick_post = blog_profile.showquickpost
			t.post_count = request.user.post_count
			puts t.display
		end
	

		def delete_entries
			t = Template.instance('blogs', 'blog_delete_entries')

			puts t.display
		end
	
	
		def change_permissions
			t = Template.instance('blogs', 'blog_change_permissions')
		
			puts t.display
		end
		
		
		def delete_comments
			t = Template.instance('blogs', 'blog_delete_comments')

			puts t.display
		end

		def ignore_user
			t = Template.instance('blogs', 'blog_ignore_user')

			t.ignore_or_unignore = "ignore"
			
			puts t.display
		end
		def unignore_user
			t = Template.instance('blogs', 'blog_ignore_user')

			t.ignore_or_unignore = "unignore"
			
			puts t.display
		end
		
		def cancel_post
			t = Template.instance('blogs', 'blog_edit_cancel');
			
			puts t.display
		end
	end
end