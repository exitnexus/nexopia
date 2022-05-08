require "json";

module Comments
	class CommentPostResult
		attr_accessor :success, :comment_id, :comment_obj, :viewing_user, :profile_user, :thumbnail_image_type, :admin_viewer, :error, :show_delete_controls, :current_page, :comments_delete_url;
		
		def initialize()
			@success = true;
		end
		
		def render_comment()
			if(@success && !@comment_obj.nil?())
				t = Template.instance("comments", "single_comment");
			
				t.comment = @comment_obj;
				t.profile_user = @profile_user;
				t.viewing_user = @viewing_user;
				t.admin_viewer = @admin_viewer;
				t.thumbnail_image_type = @thumbnail_image_type;
				t.comments_delete_url = @comments_delete_url;
				t.comments_quick_delete_url = @comments_delete_url;
				t.current_page = @current_page;
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