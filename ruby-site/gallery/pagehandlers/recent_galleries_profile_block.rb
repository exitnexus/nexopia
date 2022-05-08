lib_want :Profile, "profile_block_query_info_module";

module Gallery
	class RecentGalleriesProfileBlockHandler < PageHandler
		declare_handlers("profile_blocks/Gallery/recent_galleries") {
			area :User
			access_level :Any
			page :GetRequest, :Full, :recent_galleries, input(Integer)
			
			area :Self
			handle	:PostRequest, :create_recent_galleries, input(Integer), "create";
			handle	:PostRequest, :remove_recent_galleries, input(Integer), "remove";
			handle	:PostRequest, :save_visibility, input(Integer), "visibility";
		}
		
		def recent_galleries(block_id)
			edit_mode = params["profile_edit_mode", Boolean, false];
			
			if(!Profile::ProfileDisplayBlock.verify_visibility(block_id, request.user, request.session.user, edit_mode))
				print "<h1>Not visible</h1>";
				return;
			end
			
			# Simply return if there are no galleries to display
			if (request.user.public_galleries.empty?)
				return;
			end
			
			t = Template::instance("gallery", "recent_galleries_profile_block");
			
			puts t.display();
				
		end
		
		def self.recent_galleries_query(info)
			if(site_module_loaded?(:Profile))
				info.extend(ProfileBlockQueryInfo);
				info.title = "Recent Galleries";
				info.initial_position = 20;
				info.initial_column = 0;
				info.editable = false;
				info.multiple = false;
				info.initial_block = true;
				info.max_number = 1;
				info.form_factor = :narrow;
				info.page_url = ["Gallery", url/:gallery, 1];

				# changes on a per user basis because it shows only galleries the reader has a right to read.
				# if we want to make it work well, we could make it only ever show public galleries (if any)
				info.content_cache_timeout = 0 
			end
			
			return info;
		end
	
		def create_recent_galleries(block_id)
			return;
		end
		
		def remove_recent_galleries(block_id)
			return;
		end
	
		def save_visibility(block_id)
			return;
		end
	end
end