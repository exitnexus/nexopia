lib_require	:Profile, "profile";

lib_want	:Profile, "profile_block_query_info_module";

module Profile
	class TagLineBlock < PageHandler
		declare_handlers("profile_blocks/Profile/tagline/") {
			area :User
			access_level :Any
			page	:GetRequest, :Full, :tag_line_block, input(Integer);

			area :Self
			access_level :IsUser, CoreModule, :editprofile
			page 	:GetRequest, :Full, :tag_line_block_edit, input(Integer), "edit";
		
			handle	:PostRequest, :tag_line_block_save, input(Integer), "save";
		
			handle	:PostRequest, :visibility_save, input(Integer), "visibility";
		}
	
		def tag_line_block(block_id)
			edit_mode = params["profile_edit_mode", Boolean, false];
		
			if(!ProfileDisplayBlock.verify_visibility(block_id, request.user, request.session.user, edit_mode))
				print "<h1>Not visible</h1>";
				return;
			end
		
			t = Template::instance('profile', 'tag_line_block_view');
			t.edit_mode = edit_mode;
		
			profile_obj = Profile.find(:first, request.user.userid);
			if(profile_obj.tagline.nil?() || profile_obj.tagline == "")
				return;
			elsif(profile_obj.ntagline.nil?() || profile_obj.ntagline == "")
				t.tag_line = profile_obj.tagline.parsed();
			else
				t.tag_line = profile_obj.ntagline;
			end

			print t.display();
		end
	
		def self.tag_line_block_query(info)
			if(site_module_loaded?(:Profile))
				info.extend(ProfileBlockQueryInfo);
				info.title = "Tag Line";
				info.initial_position = 3;
				info.initial_column = 1;
				info.form_factor = :wide;
				info.multiple = false;
				info.removable = false;
				info.moveable = false;
				info.max_number = 1;
				info.admin_editable = true;
				info.content_cache_timeout = 120
			end
		
			return info;
		end
	
		def tag_line_block_edit(block_id)
			t = Template.instance('profile', 'tag_line_edit_block');
		
			profile_obj = Profile.find(:first, request.user.userid);
			t.tag_line = profile_obj.tagline;
			t.max_length = Profile::MAX_TAG_LINE_LENGTH;
		
			print t.display();
		end
	
		def tag_line_block_save(block_id)
			tag_line = params["tag_line_content", String, ""];
			profile_obj = Profile.find(:first, request.user.userid);
			
			if(tag_line.length > Profile::MAX_TAG_LINE_LENGTH)
				tag_line = tag_line.slice(0, Profile::MAX_TAG_LINE_LENGTH);
			end
			
			profile_obj.tagline = tag_line;
			profile_obj.ntagline = profile_obj.tagline.parsed();
		
			profile_obj.store();
			
			$site.memcache.delete("tagline-#{request.user.userid}");
		end
	
		def visibility_save(block_id)
			return;
		end
	end
end
