lib_require :Profile, 'user_skin', 'user_skin_attribute';

module Profile
	class ProfileSkinPage < PageHandler
		declare_handlers("profile/edit/skin") {
			area :Self
			access_level :Plus
			page 	:GetRequest, :Full, :edit_skin;
			page	:GetRequest, :Full, :edit_skin, input(Integer);
			page	:PostRequest, :Full, :new_skin, "new";
			page	:PostRequest, :Full, :duplicate_skin, input(Integer), "duplicate";
			handle	:GetRequest, :skin_edit_preview, "preview";
			handle 	:GetRequest, :skin_edit_preview, "preview", input(Integer);
			
			handle	:PostRequest, :save_skin, input(Integer), "save";
			handle	:PostRequest, :create_skin, "create";
			handle	:PostRequest, :remove_skin, input(Integer), "remove";
			handle	:PostRequest, :save_skin_application, "apply";
		}
		
		def edit_skin(skin_id = nil)
			t = Template.instance("profile", "skin_edit");
			
			user_skin_list = UserSkin.find(:scan, request.session.user.userid, :order => "name");
			new_skin = false;
			
			if(skin_id.nil?() && request.session.user.profileskin > 0)
				current_skin = UserSkin.find(:first, [request.session.user.userid, request.session.user.profileskin]);
			elsif(skin_id.nil?() && request.session.user.profileskin == 0)
				current_skin = user_skin_list.first;
			else
				current_skin = UserSkin.find(:first, [request.session.user.userid, skin_id]);
			end
			
			if (current_skin.nil?)
				current_skin = UserSkin.new();
				current_skin.init_from_site_theme(request.session.user.skin);
				current_skin.name = "New Skin";
				new_skin = true;
			end
			
			if(new_skin)
				t.save_path = "create";
			else
				t.save_path = "#{current_skin.skinid}/save";
			end
			
			generate_edit_view(t, current_skin, user_skin_list);
		end
		
		def new_skin()
			t = Template.instance("profile", "skin_edit");
			
			user_skin_list = UserSkin.find(:scan, request.session.user.userid, :order => "name");
			
			user_skin = UserSkin.new();
			user_skin.name = "New Skin";
			user_skin.init_from_site_theme(request.session.user.skin);
			
			t.save_path = "create";
			
			generate_edit_view(t, user_skin, user_skin_list);
		end
		
		def duplicate_skin(skin_id)
			t = Template.instance("profile", "skin_edit");
			
			user_skin_list = UserSkin.find(:scan, request.session.user.userid, :order => "name");
			
			base_skin = UserSkin.find(:first, [request.session.user.userid, skin_id]);
			
			dup_skin = UserSkin.new();
			dup_skin.name = "Copy of #{base_skin.name}";
			
			t.dup_skin_id = base_skin.skinid;
			
			dup_skin.copy(base_skin);
			dup_skin.skinid = nil;
			t.save_path = "create";
			
			generate_edit_view(t, dup_skin, user_skin_list);
		end
		
		def generate_edit_view(t ,current_skin, user_skin_list)
			request.reply.headers['X-width'] = 0;
			
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
			javascript = javascript + "YAHOO.profile.UserSkin.skin_selectors = YAHOO.lang.JSON.parse('#{UserSkin::USER_SKIN_JAVASCRIPT_SELECTORS.to_json()}');\n";
			javascript = javascript + "}\n";
			javascript = javascript + "PROFILE.init_user_skin_display_group_selectors = function(){\n";
			javascript = javascript + "YAHOO.profile.UserSkin.group_selectors = YAHOO.lang.JSON.parse('#{UserSkin::USER_SKIN_DISPLAY_GROUP_SELECTORS.to_json()}');\n";
			javascript = javascript + "}\n";
			javascript = javascript + "PROFILE.init_user_skin_areas = function(){\n";
			javascript = javascript + "YAHOO.profile.UserSkin.skinable_areas = YAHOO.lang.JSON.parse('#{request.session.user.skinable_areas().to_json()}');\n";
			javascript = javascript + "}\n";
			javascript = javascript + "</script>\n";
			
			t.user_skin_js = javascript;
			
			t.user_skin_list = user_skins;
			t.skin_display_groups = UserSkin::USER_SKIN_DISPLAY_GROUPS;
			t.skin_display_group_order = UserSkin::USER_SKIN_DISPLAY_GROUP_ORDER;
			t.skin_display_group_names = UserSkin::USER_SKIN_GROUP_DISPLAY_NAMES;
			
			# The assumption is made that all top level user areas (Profile, Comments, Gallery, etc) are skinable
			menu_blocks = ProfileBlockQueryMediator.instance.menu_blocks();
			t.skinable_areas = menu_blocks.map{|menu_details| menu_details.page_url[0].downcase() };
			t.user = request.session.user;
			t.current_skin = current_skin;
			
			print t.display();
		end
		
		def skin_edit_preview(skin_id = nil)
			request.reply.headers['X-width'] = 0;
			
			core_js = $site.script_url/"Core.js"
			
			user_skin_list = UserSkin.find(:scan, request.session.user.userid);
			
			if(skin_id.nil?() && request.session.user.profileskin > 0)
				current_skin = UserSkin.find(:first, [request.session.user.userid, request.session.user.profileskin]);
			elsif(skin_id.nil?() && request.session.user.profileskin == 0)
				current_skin = user_skin_list.first;
			else
				current_skin = UserSkin.find(:first, [request.session.user.userid, skin_id]);
			end
			
			t = Template.instance("profile", "skin_edit_preview");
			t.skin_name = request.session.user.skin;
			t.current_skin = current_skin;
			t.core_js = core_js;
			print "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">";
			print t.display();
		end
		
	
		def save_skin(skin_id)
			skin = UserSkin.find(:first, [request.session.user.userid, skin_id]);
			skin.name = params["skin_name", String];
			
			for attribute in UserSkin::USER_SKIN_ATTRIBUTE_LIST
				if(attribute == :section_gutter_color)
					if(params.has_key?("section_gutter_color"))
						skin.attribute_list[attribute].value = params["section_background_color", String];
					else
						skin.attribute_list[attribute].value = params["primary_block_background_color", String];
					end
				else
					skin.attribute_list[attribute].value = params[attribute.to_s(), String];
				end
			end
			
			skin.store();
			
			save_location = params["skin_save_submit", String, nil];
			
			if(save_location)
				site_redirect("/profile/edit/skin/#{skin_id}");
			else
				site_redirect("/my/profile");
			end
		end
		
		def create_skin()
			skin = UserSkin.new();
			skin.skinid = UserSkin.get_seq_id(request.session.user.userid);
			skin.name = params["skin_name", String];
			skin.userid = request.session.user.userid;
			
			for attribute in UserSkin::USER_SKIN_ATTRIBUTE_LIST
				temp = UserSkinAttribute.new();
				if(attribute == :section_gutter_color)
					if(params.has_key?("section_gutter_color"))
						temp.value = params["section_background_color", String];
					else
						temp.value = params["primary_block_background_color", String];
					end
				else
					temp.value = params[attribute.to_s(), String];
				end
				
				temp.input_type = skin.get_attribute_input_type(attribute);
				skin.attribute_list[attribute] = temp;
			end
			
			skin.store();
			
			site_redirect("/profile/edit/skin/#{skin.skinid}");
		end
		
		def remove_skin(skin_id)
			skin = UserSkin.find(:first, [request.session.user.userid, skin_id]);
			
			skin.delete();
			
			site_redirect("/profile/edit/skin/");
		end
		
		def save_skin_application()
			user_skin_list = UserSkin.find(:scan, request.session.user.userid);			
			user_skin_id_list = user_skin_list.map{|skin| skin.skinid};
			
			# The assumption is made that all top level user areas (Profile, Comments, Gallery, etc) are skinable
			menu_blocks = ProfileBlockQueryMediator.instance.menu_blocks();
			skinable_areas = menu_blocks.map{|menu_details| menu_details.page_url[0].downcase() };

			skinable_areas.each{|area|
				temp = params["#{area}skin", Integer, 0];
				if(user_skin_id_list.include?(temp))
					request.session.user.send("#{area}skin=".to_sym(), temp);
				else
					temp = 0;
					request.session.user.send("#{area}skin=".to_sym(), temp);
				end
			};
			request.session.user.store();
		end
	end
end
