lib_require :Profile, 'user_skin';

class ProfileSkinPage < PageHandler
	declare_handlers("profile") {
		area :Self
		access_level :Plus
		page :GetRequest, :Full, :skin_edit, "skin_edit";
		page :GetRequest, :Full, :skin_edit_preview, "skin_edit_preview";
	}
	
	def skin_edit(skin_id = nil)
		request.reply.headers['X-width'] = 0;
		
		template = Template.instance "profile", "skin_edit";
		
		user_skin_list = Profile::UserSkin.find(:scan, request.session.user.userid);
		
		if(skin_id.nil?() && request.session.user.profileskin > 0)
			current_skin = Profile::UserSkin.find(:first, [request.session.user.userid, request.session.user.profileskin]);
		elsif(skin_id.nil?() && request.session.user.profileskin == 0)
			if(user_skin_list.first.nil?())
				current_skin = Profile::UserSkin.new();
			else
				current_skin = user_skin_list.first;
			end
		else
			current_skin = Profile::UserSkin.find(:first, [request.session.user.userid, skin_id]);
		end
		
		user_skin_js_list = Hash.new();
		user_skins = Array.new();
		for skin in user_skin_list
			user_skin_js_list[skin.skinid] = skin.skin_values(request.session.user);
			user_skins << [skin.name, skin.skinid];
		end
		
		javascript = "<script>\n";
		javascript = javascript + "PROFILE.init_user_skin_model = function(){\n";
		javascript = javascript + "YAHOO.profile.UserSkin.skin_list = YAHOO.lang.JSON.parse('#{user_skin_js_list.to_json()}');\n";
		javascript = javascript + "}\n";
		javascript = javascript + "PROFILE.init_user_skin_selectors = function(){\n";
		javascript = javascript + "YAHOO.profile.UserSkin.skin_selectors = YAHOO.lang.JSON.parse('#{Profile::UserSkin::USER_SKIN_JAVASCRIPT_SELECTORS.to_json()}');\n";
		javascript = javascript + "}\n";
		javascript = javascript + "PROFILE.init_user_skin_display_group_selectors = function(){\n";
		javascript = javascript + "YAHOO.profile.UserSkin.group_selectors = YAHOO.lang.JSON.parse('#{Profile::UserSkin::USER_SKIN_DISPLAY_GROUP_SELECTORS.to_json()}');\n";
		javascript = javascript + "}\n";
		javascript = javascript + "</script>\n";
		
		template.user_skin_js = javascript;
		
		template.user_skin_list = user_skins;
		template.skin_display_groups = Profile::UserSkin::USER_SKIN_DISPLAY_GROUPS;
		template.skinable_areas = request.session.user.skinable_areas();
		template.user = request.session.user;
		template.current_skin = current_skin;
		
		print template.display();
	end
	
	def skin_edit_preview
		core_js = $site.script_url/"Core.js"
	
		t = Template.instance "profile", "skin_edit_preview"
		t.core_js = core_js
		puts t.display
	end
end