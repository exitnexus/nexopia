require "json";

module Blogs
	class BlogCommentPostResult
		attr_accessor :success, :comment_id, :blog_comment_obj, :viewing_user, :blog_user, :admin_viewer, :error, :show_delete_controls, :comments_delete_url;
		
		def initialize()
			@success = true;
		end
		
		def render_comment()
			if(@success)
				t = Template.instance("comments", "single_comment");
			
				t.blog_comment = @blog_comment_obj;
				t.blog_user = @blog_user;
				t.viewing_user = @viewing_user;
				t.admin_viewer = @admin_viewer;
				t.comments_delete_url = @comments_delete_url;
				if(@show_delete_controls.nil?())
					t.show_delete_controls = (@admin_viewer || @profile_user.userid == @viewing_user.userid);
				else
					t.show_delete_controls = @show_delete_controls;
				end
			
				return t.display();
			end
		end
		
		def to_json(*a)
			{
				:json_class => self.class.name,
				:success => @success,
				:comment_id => @comment_id,
				:comment_content => self.render_comment(),
				:error => @error
			}.to_json(*a);
		end
	end
end