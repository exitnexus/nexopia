lib_require :Profile, "profile_view";

lib_want	:Adminutils, 'adminroleaccount'
lib_want	:Profile, "profile_block_query_info_module";
lib_want :Moderator, "moderator";

module Profile
	class ProfileControlBlock < PageHandler
		declare_handlers("profile_blocks/Profile/control/") {
			area :User
			access_level :Any
			page	:GetRequest, :Full, :control_block, input(Integer);
			page  :GetRequest, :Full, :friend_toggle, "friend_toggle"
		}
	
		def control_block(block_id)
			edit_mode = params["profile_edit_mode", Boolean, false];
		
			if(!ProfileDisplayBlock.verify_visibility(block_id, request.user, request.session.user, edit_mode))
				print "<h1>Not visible</h1>";
				return;
			end
		
			profile_views = ProfileView.views(request.user);
		
			t = Template::instance('profile', 'profile_control_block');
			t.user = request.user
			t.viewer = request.session.user
			t.profile_views = profile_views.to_s().gsub(/(\d)(?=(\d\d\d)+(?!\d))/, "\\1,");
			t.comments_visible = ProfileBlockVisibility.visible?(request.user.commentsmenuaccess(), request.user, request.session.user)
		
			print t.display();
		end
	
	
		def friend_toggle()
			t = Template::instance('profile', 'profile_control_block_friend_toggle');
		
			t.user = request.user;
			t.viewer = request.session.user;
		
			print t.display;
		end
	
	
		def self.control_block_query(info)
			if(site_module_loaded?(:Profile))
				info.extend(ProfileBlockQueryInfo);
				info.title = "Control Block";
				info.initial_position = 0;
				info.initial_column = 0;
				info.form_factor = :narrow;
				info.multiple = false;
				info.editable = false;
				info.moveable = false;
				info.removable = false;
				info.page_url = ["Profile", url/:profile, 0];
				info.pagehandler_section_list = ["profile", ""];
			
				# changes on a per user basis, I think.
				info.content_cache_timeout = 0
			end
		
			return info;
		end
	end
end
