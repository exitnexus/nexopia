lib_want :Profile, "profile_block_query_info_module";

module Userpics
	class ClassicFilmStripProfileBlockHandler < PageHandler
		
		declare_handlers("profile_blocks/Userpics/classic_film_strip/") {
			area :User
			access_level :Any
			page :GetRequest, :Full, :classic_film_strip, input(Integer);
			
			area :Self
			access_level :IsUser, CoreModule, :editprofile
			page		:GetRequest, :Full, :film_strip_edit_view, input(Integer), "edit";
			page		:GetRequest, :Full, :in_place_edit_view, input(Integer), "edit", "in_place";
			
			handle	:PostRequest, :film_strip_save, input(Integer), "save";
		}
		
		def classic_film_strip(block_id)
			edit_mode = params["profile_edit_mode", Boolean, false];
			
			if(!Profile::ProfileDisplayBlock.verify_visibility(block_id, request.user, request.session.user, edit_mode))
				print "<h1>Not visible</h1>";
				return;
			end
			
			t = Template.instance("userpics", "classic_film_strip");
			
			t.edit_mode = edit_mode;
			t.user = request.user
			sorted_pictures = request.user.pics
			
			t.sorted_pictures = sorted_pictures
			t.json_pictures = clean_up_json(t.sorted_pictures.map {|pic| pic.img_info("classicprofile")})
			puts t.display
		end
		
		def in_place_edit_view(block_id)
			t = Template.instance("userpics", "film_strip_in_place_edit_view");
			
			t.classic = "selected";
			t.profile_user = request.session.user;
			t.block_id = block_id;
			
			# We explicitly set a form key because the handler for saving is in the "improved film strip" handler and the
			#  form key doesn't validate.
			t.form_key = SecureForm.encrypt(request.session.user, url/:Self/:profile_blocks/:Userpics/:film_strip);
			
			puts t.display();
		end
		
		def self.classic_film_strip_query(info)
			if(site_module_loaded?(:Profile))
				info.extend(ProfileBlockQueryInfo);
				info.title = "Classic Film Strip";
				info.initial_position = 0;
				info.initial_column = 1;
				info.form_factor = :wide;
				info.moveable = false;
				info.initial_block = false;
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
			# print "<div style=\"padding: 10px\">Nathan insert edit content here</div>";
		end
		
		def film_strip_save(block_id)
			#empty definition for the saving of the profile block.
		end

		
		#This blanks any description that has characters we can't handle in it, a better solution
		#is desirable but needs to be implemented at a lower level
		def clean_up_json(img_infos)
			img_infos.each {|img_info|
				begin
					img_info.to_json
				rescue Object
					img_info[0] = ""
				end	
			}
		end
	end
end
