require "json";

module Gallery
	class GalleryCommentPostResult
		attr_accessor :success, :comment_id, :comment_obj, :viewing_user, :profile_user, :thumbnail_image_type, :admin_viewer, :error, :show_delete_controls, :comments_delete_url;
		
		def initialize()
			@success = true;
		end
		
		def render_comment()
			if(@success && !@comment_obj.nil?())
				t = Template.instance("gallery", "gallery_comment");
			
				t.gallery_comment = @comment_obj;
				t.gallery_user = @profile_user;
				t.viewing_user = @viewing_user;
				t.admin_viewer = @admin_viewer;
				t.thumbnail_image_type = @thumbnail_image_type;
				t.comments_delete_url = @comments_delete_url;
				t.comments_quick_delete_url = @comments_delete_url;
				if(@show_delete_controls.nil?())
					t.show_delete_controls = (@admin_viewer || @profile_user.userid == @viewing_user.userid);
				else
					t.show_delete_controls = @show_delete_controls
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