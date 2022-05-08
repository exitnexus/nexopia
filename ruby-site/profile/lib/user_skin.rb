lib_require :Core, 'users/user';
lib_require :Profile, "user_skin_attribute";

module Profile
	class UserSkin < Cacheable
		init_storable(:usersdb, "userskins");
		
		attr_accessor :skin_attribute_list;
		
		relation :singular, :user, [:userid], User;
		
		extend TypeID;
		
		USER_SKIN_ATTRIBUTE_LIST = [
			:primary_block_text_color,
			:primary_block_link_color,
			:primary_block_link_hover_color,
			:primary_block_background_color,
			:primary_block_header_text_color,
			:primary_block_icon_color,
			:secondary_block_text_color,
			:secondary_block_link_color,
			:secondary_block_link_hover_color,
			:secondary_block_background_color,
			:secondary_block_background_hover_color,
			:secondary_block_icon_color,
			:utility_block_text_color,
			:utility_block_link_color,
			:utility_block_background_color,
			:utility_block_header_text_color,
			:utility_block_icon_color,
			:utility_block_user_online_color,
			:utility_block_user_offline_color,
			:section_background_color,
			:section_gutter_color
		];
		
		USER_SKIN_DISPLAY_GROUPS = {
			:primary => [[:primary_block_text_color, "Text"],
				[:primary_block_link_color, "Link"],
				[:primary_block_link_hover_color, "Link Hover"],
				[:primary_block_background_color, "Background"],
				[:primary_block_header_text_color, "Title"],
				[:primary_block_icon_color, "Icon"]],
			:secondary => [[:secondary_block_text_color, "Text"],
				[:secondary_block_link_color, "Link"],
				[:secondary_block_link_hover_color, "Link Hover"],
				[:secondary_block_background_color, "Background"],
				[:secondary_block_background_hover_color, "Hover"],
				[:secondary_block_icon_color, "Icon"]],
			:utility => [[:utility_block_text_color,"Text"],
				[:utility_block_link_color, "Link"],
				[:utility_block_background_color, "Background"],
				[:utility_block_header_text_color, "User Name"],
				[:utility_block_icon_color, "Icon"],
				[:utility_block_user_online_color, "Online"],
				[:utility_block_user_offline_color, "Offline"]],
			:page => [[:section_background_color, "Background"],
				[:section_gutter_color, "Use Block Layout"]]
		};
		
		USER_SKIN_DISPLAY_GROUP_SELECTORS = {
			:primary => [".primary_block"],
			:secondary => [".secondary_block"],
			:utility => [".control_block"],
			:page => ["body"]
		};
		
		USER_SKIN_GROUP_DISPLAY_NAMES = {
			:primary => ["Main Body"],
			:secondary => ["Secondary Boxes"],
			:utility => ["Username Box"],
			:page => ["Background"]
		};
		
		USER_SKIN_DISPLAY_GROUP_ORDER = [
			:primary,
			:secondary,
			:utility,
			:page
		];
		
		USER_SKIN_JAVASCRIPT_SELECTORS = {
			:primary_block_text_color => { :selector => [".primary_block"], :property => "color" },
			:primary_block_link_color => { :selector => [".primary_block a", "#user_area_header a"], :property => "color", :exclude_selectors => [:secondary_block_link_color] },
			:primary_block_link_hover_color => { :selector => [".primary_block a:hover", "#user_area_header a:hover"], :property => "color", :apply_on_focus => "true", :revert_to => "primary_block_link_color", :exclude_selectors => [:secondary_block_link_hover_color] },
			:primary_block_background_color => { :selector => [".primary_block", "#user_area_header"], :property => "background-color", :conditional_selectors => [[:section_gutter_color, "section_gutter_disabled"]] },
			:primary_block_header_text_color => { :selector => [".primary_block .block_title"], :property => "color" },
			:primary_block_icon_color => {:selector => [".primary_block .custom_color_icon"], :property => "color", :exclude_selectors => [:secondary_block_icon_color]},
			
			:secondary_block_text_color => { :selector => [".secondary_block"], :property => "color" },
			:secondary_block_link_color => { :selector => [".secondary_block a"], :property => "color"},
			:secondary_block_link_hover_color => { :selector => [".secondary_block a:hover"], :property => "color", :apply_on_focus => "true", :revert_to => "secondary_block_link_color"},
			:secondary_block_background_color => { :selector => [".secondary_block"], :property => "background-color" },
			:secondary_block_background_hover_color => { :selector => ["#friends_pages .secondary_block"], :property => "background-color", :apply_on_focus => "true", :revert_to => "secondary_block_background_color" },
			:secondary_block_icon_color => { :selector => [".secondary_block .custom_color_icon"], :property => "color" },
			
			:utility_block_text_color => { :selector => [".control_block"], :property => "color" },
			:utility_block_link_color => { :selector => [".control_block a"], :property => "color" },
			:utility_block_background_color => { :selector => [".control_block"], :property => "background-color" },
			:utility_block_header_text_color => { :selector => [".control_block .user_name"], :property => "color"},
			:utility_block_icon_color => { :selector => [".control_block .custom_color_icon"], :property => "color" },
			:utility_block_user_online_color => { :selector => [".control_block .user_status_online"], :property => "color"},
			:utility_block_user_offline_color => { :selector => [".control_block .user_status_online"], :property => "color", :apply_on_focus => "true", :revert_to => "utility_block_user_online_color"},
			
			:section_background_color => { :selector =>[ "body"], :property => "background-color", :conditional_selectors => [[:section_gutter_color, "section_gutter_enabled"]] },
			:section_gutter_color => { :selector => ["#profile td.profile_left_column", "#profile td.profile_right_column"], :property => "background-color", :type => "checkbox", :onchange => {:enabled => "section_background_color", :disabled => "primary_block_background_color"} }
		};
		
		SITE_THEME_CONVERSION_MAP = {
			:primary_block_text_color => "primary_text_color",
			:primary_block_link_color => "link_color",
			:primary_block_link_hover_color => "link_accent_color",
			:primary_block_background_color => "primary_background_color",
			:primary_block_header_text_color => "header_background_color",
			:primary_block_icon_color => "primary_text_color", 
			:secondary_block_text_color => "secondary_text_color",
			:secondary_block_link_color => "link_accent_color",
			:secondary_block_link_hover_color => "link_color", 
			:secondary_block_background_color => "secondary_background_color",
			:secondary_block_background_hover_color => "secondary_background_color",
			:secondary_block_icon_color => "secondary_text_color",
			:utility_block_text_color => "primary_text_color",
			:utility_block_link_color => "link_color",
			:utility_block_background_color => "primary_background_color",
			:utility_block_header_text_color => "header_background_color",
			:utility_block_icon_color => "primary_text_color",
			:utility_block_user_online_color => "user_online_color",
			:utility_block_user_offline_color => "user_offline_color",
			:section_background_color => "page_background_color",
			:section_gutter_color => "primary_background_color"
		};
		
		USER_SKIN_ATTRIBUTE_SPECIAL_INPUT = {
			:section_gutter_color => :checkbox
		};
		
		def initialize(*args)
			super(*args);
			self.skin_attribute_list = Hash.new();
		end
		
		def after_load()
			self.skin_attribute_list = YAML.load(self.skindata);
			self.verify_attributes();
		end
		
		def before_create()
			self.skindata = self.skin_attribute_list.to_yaml();
		end
		
		def before_update()
			self.skindata = self.skin_attribute_list.to_yaml();
			self.revision += 1;
		end
		
		alias :attribute_list :skin_attribute_list;
		
		def skin_values(user)
			return site_skin_values(user.skin);
		end
		
		def site_skin_values(skin_name)
			if(self.skin_attribute_list.empty?())
				init_from_site_theme(skin_name);
			end
			
			value_hash = Hash.new();
			for attribute in self.skin_attribute_list.keys
				value_hash[attribute] = self.skin_attribute_list[attribute].value;
				if(value_hash[attribute].nil?())
					value_hash[attribute] = SkinMediator.request_skin_value(:Nexoskel, skin_name, SITE_THEME_CONVERSION_MAP[attribute]);
				end
			end
			
			return value_hash;
		end
		
		def init_from_site_theme(skin_name)
			for attribute in USER_SKIN_ATTRIBUTE_LIST
				temp = UserSkinAttribute.new();
				temp.value = SkinMediator.request_skin_value(:Nexoskel, skin_name, SITE_THEME_CONVERSION_MAP[attribute]);
				temp.input_type = get_attribute_input_type(attribute);
				self.skin_attribute_list[attribute] = temp;
			end
		end
		
		def header
			return url / :users / self.user.username / :style / $site.static_number / self.revision / "#{self.skinid}.css";
		end
		
		def generate_javascript_model()
			js_attribute_list = Hash.new();
			for attribute in self.skin_attribute_list.keys()
				
			end
		end
		
		def copy(base_skin)
			for attribute in USER_SKIN_ATTRIBUTE_LIST
				self.skin_attribute_list[attribute] = base_skin.skin_attribute_list[attribute].duplicate();
			end
		end
		
		def verify_attributes()
			user = User.find(:first, self.userid);
			
			for attr in USER_SKIN_ATTRIBUTE_LIST
				temp = self.skin_attribute_list[attr];
				if(temp.nil?())
					self.skin_attribute_list[attr] = UserSkinAttribute.new();
					if(attr != :section_gutter_color)
						self.skin_attribute_list[attr].value = SkinMediator.request_skin_value(:Nexoskel, user.skin, SITE_THEME_CONVERSION_MAP[attr]).upcase();
					else
						self.skin_attribute_list[attr].value = self.skin_attribute_list[:primary_block_background_color].value;
					end
					
					self.skin_attribute_list[attr].input_type = get_attribute_input_type(attr);
				end
			end
		end
		
		def get_attribute_input_type(attr_name)
			input_type = USER_SKIN_ATTRIBUTE_SPECIAL_INPUT[attr_name];
			
			if(input_type.nil?())
				input_type = :text_swatch;
			end
			
			return input_type;
		end
		
		def [](key)
			return self.skin_attribute_list[key].value;
		end
	end
end
