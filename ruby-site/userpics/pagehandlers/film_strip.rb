lib_want :Profile, "profile_block_query_info_module";

module Userpics
	class FilmStripProfileBlockHandler < PageHandler
		
		declare_handlers("profile_blocks/Userpics/film_strip/") {
			area :User
			access_level :Any
			page		:GetRequest, :Full, :film_strip, input(Integer);
			
			area :Self
			access_level :IsUser, CoreModule, :editprofile
			page		:GetRequest, :Full, :film_strip_edit_view, input(Integer), "edit";
			page		:GetRequest, :Full, :in_place_edit_view, input(Integer), "edit", "in_place";
			
			handle	:PostRequest, :film_strip_save, input(Integer), "save";
			handle	:PostRequest, :film_strip_choice_save, input(Integer), "save", "type";
		}
		
		def film_strip(block_id)
			edit_mode = params["profile_edit_mode", Boolean, false];
			
			if(!Profile::ProfileDisplayBlock.verify_visibility(block_id, request.user, request.session.user, edit_mode))
				print "<h1>Not visible</h1>";
				return;
			end
			
			t = Template.instance("userpics", "film_strip");
			t.pics = request.user.pics
			t.pics.each {|pic|
				pic.gallery_pic
			}
			t.pics.each {|pic|
			  if(!pic.gallery_pic.nil?)
				  pic.gallery_pic.owner
			  end
			}
			
			t.edit_mode = edit_mode;
			
			puts t.display
		end
		
		
		def in_place_edit_view(block_id)
			t = Template.instance("userpics", "film_strip_in_place_edit_view");

			t.improved = "selected";
			t.profile_user = request.session.user;
			t.block_id = block_id;

			puts t.display();
		end
		
		def self.film_strip_query(info)
			if(site_module_loaded?(:Profile))
				info.extend(ProfileBlockQueryInfo);
				info.title = "Film Strip";
				info.initial_position = 0;
				info.initial_column = 1;
				info.form_factor = :wide;
				info.moveable = false;
				info.editable = false;
				info.removable = false;
				info.visible_wrapper = false;
				info.in_place_editable = true;
				#info.custom_edit_button = ProfileBlockQueryInfo::JavascriptFunction.new("film_strip_edit_button");
				info.custom_edit_button = "";
				info.explicit_save = false;
				info.add_visibility_exclude(:logged_in);
				info.add_visibility_exclude(:friends_of_friends);
				info.add_visibility_exclude(:friends);
				info.add_visibility_exclude(:none);
				
				info.content_cache_timeout = 120
			end
			return info;
		end
		
		def film_strip_edit_view(block_id)
			t = Template::instance("gallery", "add_profile_pic")
			puts t.display
			#print "<div style=\"padding: 10px\">Nathan insert edit content here</div>";
		end
		
		def film_strip_save(block_id)
			#empty definition for the saving of the profile block.
		end
		
		def film_strip_choice_save(block_id)
			choice = params["film_strip_choice", String];
			
			display_block = Profile::ProfileDisplayBlock.find(:first, [request.session.user.userid, block_id]);
			
			if(choice == "improved")
				display_block.path = "film_strip";
			else
				display_block.path = "classic_film_strip";
			end
			
			display_block.store();
		end
	end
end
