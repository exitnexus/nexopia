lib_require :Core, 'users/user';
lib_require :Profile, "user_skin_attribute";

module Profile
	class UserSkin < Cacheable
		init_storable(:usersdb, "userskins");
		
		attr_accessor :skin_attribute_list;
		
		relation_singular :user, :userid, User;
		
		extend TypeID;
		
		USER_SKIN_ATTRIBUTE_LIST = [
			:primary_block_text_color,
			:primary_block_link_color,
			:primary_block_background_color,
			:primary_block_header_text_color,
			:primary_block_icon_color,
			:secondary_block_text_color,
			:secondary_block_link_color,
			:secondary_block_background_color,
			:secondary_block_background_hover_color,
			:secondary_block_icon_color,
			:utility_block_text_color,
			:utility_block_link_color,
			:utility_block_background_color,
			:utility_block_icon_color,
			:section_background_color
		];
		
		#keys are like they are because we needed a quick way to sort the display
		#groups. I don't like it, but it's necessary.
		USER_SKIN_DISPLAY_GROUPS = {
			:primary => [[:primary_block_text_color, "Text"],
				[:primary_block_link_color, "Link"],
				[:primary_block_background_color, "Background"],
				[:primary_block_header_text_color, "Title"],
				[:primary_block_icon_color, "Icon"]],
			:secondary => [[:secondary_block_text_color, "Text"],
				[:secondary_block_link_color, "Link"],
				[:secondary_block_background_color, "Background"],
				[:secondary_block_background_hover_color, "Hover"],
				[:secondary_block_icon_color, "Icon"]],
			:utility => [[:utility_block_text_color,"Text"],
				[:utility_block_link_color, "Link"],
				[:utility_block_background_color, "Background"],
				[:utility_block_icon_color, "Icon"]],
			:page => [[:section_background_color, "Background"]]
		};
		
		USER_SKIN_DISPLAY_GROUP_SELECTORS = {
			:primary => "class:primary_block/",
			:secondary => "class:secondary_block/",
			:utility => "class:utility_block/",
			:page => "tag:body/"
		};
		
		USER_SKIN_JAVASCRIPT_SELECTORS = {
			:primary_block_text_color => "class:primary_block,color",
			:primary_block_link_color => "",
			:primary_block_background_color => "class:light_block,background-color",
			:primary_block_header_text_color => "tag:h1,color",
			:primary_block_icon_color => "",
			:secondary_block_text_color => "",
			:secondary_block_link_color => "",
			:secondary_block_background_color => "class:dark_block,background-color",
			:secondary_block_background_hover_color => "",
			:secondary_block_icon_color => "",
			:utility_block_text_color => "class:utility_block,color",
			:utility_block_link_color => "",
			:utility_block_background_color => "class:utility_block,background-color",
			:utility_block_icon_color => "class:utility_block/class:custom_color_icon,color",
			:section_background_color => "tag:body,background-color"
		};
		
		SITE_THEME_CONVERSION_MAP = {
			:primary_block_text_color => "primary_text_color",
			:primary_block_link_color => "link_color",
			:primary_block_background_color => "primary_background_color",
			:primary_block_header_text_color => "header_text_color",
			:primary_block_icon_color => "primary_text_color",
			:secondary_block_text_color => "secondary_text_color",
			:secondary_block_link_color => "link_accent_color",
			:secondary_block_background_color => "secondary_background_color",
			:secondary_block_background_hover_color => "secondary_background_color",
			:secondary_block_icon_color => "secondary_text_color",
			:utility_block_text_color => "primary_text_color",
			:utility_block_link_color => "link_color",
			:utility_block_background_color => "primary_background_color",
			:utility_block_icon_color => "primary_text_color",
			:section_background_color => "page_background_color"
		};
		
		def initialize()
			super();
			self.skin_attribute_list = Hash.new();
		end
		
		def after_load()
			self.skin_attribute_list = YAML.load(self.skindata);
		end
		
		def before_create()
			self.skindata = self.skin_attribute_list.to_yaml();
		end
		
		def before_update()
			self.skindata = self.skin_attribute_list.to_yaml();
		end
		
		alias :attribute_list :skin_attribute_list;
		
		def skin_values(user)
			if(self.skin_attribute_list.empty?())
				init_from_site_theme(user);
			end
			
			value_hash = Hash.new();
			for attribute in self.skin_attribute_list.keys
				value_hash[attribute] = self.skin_attribute_list[attribute].value;
			end
			
			return value_hash;
		end
		
		def init_from_site_theme(user)
			for attribute in USER_SKIN_ATTRIBUTE_LIST
				temp = UserSkinAttribute.new();
				temp.value = SkinMediator.request_skin_value(:Nexoskel, user.skin, SITE_THEME_CONVERSION_MAP[attribute]);
				self.skin_attribute_list[attribute] = temp;
			end
		end
		
		def before_update
			self.revision += 1
		end
		
		def header
			return "#{URI.escape(self.user.username)}/#{self.revision}/#{URI.escape(self.name)}"
		end
		
		def generate_javascript_model()
			js_attribute_list = Hash.new();
			for attribute in self.skin_attribute_list.keys()
				
			end
		end
	end
end
