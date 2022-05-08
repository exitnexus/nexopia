lib_want	:Profile, "profile_block_query_info_module";

class BasicsBlock < PageHandler
	declare_handlers("profile_blocks/Profile/basics/") {
		area :User
		access_level :Any
		page	:GetRequest, :Full, :basics_block, input(Integer);

		area :Self
		page 	:GetRequest, :Full, :basics_block_edit, input(Integer), "edit";
		page	:GetRequest, :Full, :basics_block_edit, "new";
		
		
		handle	:PostRequest, :basics_block_save, input(Integer), "save";
		handle	:PostRequest, :basics_block_save, input(Integer), "create";
		
		handle	:PostRequest, :freeform_block_remove, input(Integer), "remove";
	}
	
	Basic = Struct.new(:name, :value)
	
	def basics_block(block_id)
		t = Template::instance('profile', 'basics_block_view');
		t.user = request.user
		
		basics = []
		
		height = request.user.profile.display_string(:height)
		basics << Basic.new("Height:", height) if height
		
		weight = request.user.profile.display_string(:weight)
		basics << Basic.new("Weight:", weight) if weight
		
		orientation = request.user.profile.display_string(:orientation)
		basics << Basic.new("Sexual Orientation:", orientation) if orientation
	
		dating = request.user.profile.display_string(:dating)
		basics << Basic.new("Dating:", dating) if dating
		
		living = request.user.profile.display_string(:living)
		basics << Basic.new("Living Situation:", living) if living
		
		if (request.user.profile.showjointime)
			basics << Basic.new("Join Date:", Time.at(request.user.jointime).strftime("%B %d, %Y"))
		end
		if (request.user.profile.showactivetime)
			basics << Basic.new("Last Active:", Time.at(request.user.activetime).strftime("%B %d, %Y"))
		end
		if (request.user.profile.showprofileupdatetime)
			basics << Basic.new("Profile Updated:", Time.at(request.user.profile.profileupdatetime).strftime("%B %d, %Y"))
		end
		
		
		t.basics = basics
		
		print t.display();
	end
	
	def self.basics_block_query(info)
		if(site_module_loaded?(:Profile))
			info.extend(ProfileBlockQueryInfo);
			info.title = "Basics";
			info.initial_position = 10;
			info.initial_column = 1;
			info.form_factor = :wide;
			info.multiple = false;
			info.removable = false;
		end
		
		return info;
	end
	
	def basics_block_edit(block_id = nil)
		t = Template::instance('profile', 'basics_edit_block')
		
		t.user = request.user
		
		puts t.display
	end
	
	def basics_block_save(block_id)
		request.user.profile.weight = params['weight', String, nil]
		request.user.profile.height = params['height', String, nil]
		request.user.profile.orientation = params['orientation', String, nil]
		request.user.profile.living = params['living', String, nil]
		request.user.profile.dating = params['dating', String, nil]
		request.user.profile.profileupdatetime = Time.now.to_i
		request.user.profile.store
	end
	
	def freeform_block_remove(block_id)
		return;
	end
end
