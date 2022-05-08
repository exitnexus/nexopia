lib_want	:Profile, "profile_block_query_info_module";

class ProfileControlBlock < PageHandler
	declare_handlers("profile_blocks/Profile/control/") {
		area :User
		access_level :Any
		page	:GetRequest, :Full, :control_block, input(Integer);
		page	:GetRequest, :Full, :control_block_edit, input(Integer), "edit";
		
		handle	:PostReqest, :control_block_save, input(Integer), "save";
	}
	
	def control_block(block_id)
		t = Template::instance('profile', 'profile_control_block');
		t.user = request.user
		
		print t.display();
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
		end
		
		return info;
	end
	
	def control_block_edit(block_id)
		t = Template.instance("profile", "profile_control_block");
		
		print t.display();
	end
	
	def control_block_save(block_id)
		#do savey things!
	end
end
