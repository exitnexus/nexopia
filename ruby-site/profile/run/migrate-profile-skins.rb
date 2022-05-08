lib_require :Profile, "user_skin", "php_profile_skin", "user_skin_attribute";

class PHPProfileSkin < Storable
	init_storable(:db, 'profileskins')
	
	SKIN_VARIABLES = [
		:header_background_color,
		:header_text_color,
		:header_link_color,
		:header_link_accent_color,

		:primary_background_color,
		:secondary_background_color,
		:primary_text_color,
		:link_color,
		:link_accent_color,

		:user_online_color,
		:user_offline_color
	]
	
	def variables()
		return SKIN_VARIABLES;
	end
	
	def seperate_values()
		temp_hash = Hash.new();
		self.variables.each_with_index {|variable, i|
			temp_hash[variable] = self.data[i*6,6];
		};
		
		return temp_hash;
	end
end

CONVERSION_MAP = {
	:primary_block_text_color => "primary_text_color",
	:primary_block_link_color => "link_color",
	:primary_block_background_color => "primary_background_color",
	:primary_block_header_text_color => "header_text_color",
	:primary_block_icon_color => "primary_text_color",
	:secondary_block_text_color => "secondary_text_color",
	:secondary_block_link_color => "link_accent_color",
	:secondary_block_background_color => "secondary_background_color",
	:secondary_block_background_hover_color => "header_background_color",
	:secondary_block_icon_color => "secondary_text_color",
	:utility_block_text_color => "primary_text_color",
	:utility_block_link_color => "link_color",
	:utility_block_background_color => "primary_background_color",
	:utility_block_icon_color => "primary_text_color"
};


def convert_skin(skin)
	parsed_skin = skin.seperate_values();
	user_obj = User.find(:first, skin.userid);
	if(user_obj.nil?())
		return nil;
	end
	$log.info("(***************************************:");
	$log.info(skin.userid);
	$log.info("(***************************************:");
	user_skin = Profile::UserSkin.new();
	user_skin.userid = skin.userid;
	user_skin.name = skin.name;
	user_skin.skinid = Profile::UserSkin.get_seq_id(user_skin.userid);
	
	for attribute in Profile::UserSkin::USER_SKIN_ATTRIBUTE_LIST
		temp = Profile::UserSkinAttribute.new();
		temp.value = parsed_skin[CONVERSION_MAP[attribute]];
		if(temp.value.nil?())
			user_skin_key = Profile::UserSkin::SITE_THEME_CONVERSION_MAP[attribute];
			temp.value = SkinMediator.request_skin_value(:Nexoskel, user_obj.skin, user_skin_key);
		end
		user_skin.attribute_list[attribute] = temp;
	end
	
	return user_skin;
end


skin_list = PHPProfileSkin.find(:all, :scan);

for skin in skin_list
	if(skin.nil?())
		$log.info("Nil skin!");
		next;
	end
	user_skin = convert_skin(skin);
	if(!user_skin.nil?())
		user_skin.store();
	end
end
