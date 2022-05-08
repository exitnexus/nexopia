lib_require :Profile, "user_skin", "user_skin_attribute";
lib_require :Core, 'array', 'rangelist'

#depends on migrate-user-profile-skin-choice

concurrency = ENV['CONCURRENCY'] || 10  # run this many processes
range_list  = ENV['RANGE_LIST'] && ENV['RANGE_LIST'].range_list # using these ranges

concurrency = concurrency.to_i
range_list ||= (0...100).to_a


PHP_SKIN_VARIABLES = [
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

CONVERSION_MAP = {
	:primary_block_text_color               => :primary_text_color,
	:primary_block_link_color               => :link_color,
	:primary_block_link_hover_color         => :link_accent_color,
	:primary_block_background_color         => :primary_background_color,
	:primary_block_header_text_color        => :header_text_color,
	:primary_block_icon_color               => :primary_text_color,
	:secondary_block_text_color             => :primary_text_color,
	:secondary_block_link_color             => :link_accent_color,
	:secondary_block_link_hover_color       => :link_color,
	:secondary_block_background_color       => :secondary_background_color,
	:secondary_block_background_hover_color => :header_background_color,
	:secondary_block_icon_color             => :primary_text_color,
	:utility_block_text_color               => :primary_text_color,
	:utility_block_link_color               => :link_color,
	:utility_block_header_text_color        => :header_text_color,
	:utility_block_background_color         => :primary_background_color,
	:utility_block_icon_color               => :primary_text_color,
	:utility_block_user_online_color        => :user_online_color,
	:utility_block_user_offline_color       => :user_offline_color
#	:section_background_color               => #leaving this empty should just have it filled at display time
}

def convert_skin(skin)
	data = skin['data'];

	return if(data.length != 66)

	parsed_skin = Hash.new();
	PHP_SKIN_VARIABLES.each_with_index {|variable, i|
		parsed_skin[variable] = data[i*6,6];
	}

	skinid = Profile::UserSkin.get_seq_id(skin['userid'].to_i);

	skindata = "---\n";
	Profile::UserSkin::USER_SKIN_ATTRIBUTE_LIST.each{|attribute|
		if(!parsed_skin[CONVERSION_MAP[attribute]].nil?() && parsed_skin[CONVERSION_MAP[attribute]].length == 6)
			skindata << ":#{attribute}: !ruby/object:Profile::UserSkinAttribute \n  value: \"##{parsed_skin[CONVERSION_MAP[attribute]]}\"\n"
		end
	}

	$site.dbs[:usersdb].query("INSERT INTO userskins SET userid = #, skinid = ?, name = ?, skindata = ?", 
		skin['userid'].to_i, skinid, skin['name'], skindata);

	return skinid;
end


range_list.each_fork(concurrency){|i|
	$log.info("Starting group #{i} at #{Time.now}");


	time = Time.now.to_i;
	
	skin_res = $site.dbs[:usersdb].query("SELECT userid, galleryskin as skinid, 1 as type FROM `users` WHERE userid % 100 = ? && galleryskin > 0 && premiumexpiry > ?
	UNION
	SELECT userid, commentsskin as skinid, 2 as type FROM users WHERE userid % 100 = ? && commentsskin > 0  && premiumexpiry > ?
	UNION
	SELECT userid, profileskin as skinid, 3 as type FROM users WHERE userid % 100 = ? && profileskin > 0 && premiumexpiry > ?
	UNION
	SELECT userid, friendsskin as skinid, 4 as type FROM users WHERE userid % 100 = ? && friendsskin > 0 && premiumexpiry > ?
	UNION
	SELECT userid, blogskin as skinid, 5 as type FROM users WHERE userid % 100 = ? && blogskin > 0 && premiumexpiry > ?", 
	i, time, i, time, i, time, i, time, i, time);

	user_skin_list = Hash.new();
	skin_res.each{|row|
		key = "#{row['userid']}-#{row['skinid']}";
		if(user_skin_list[key].nil?())
			user_skin_list[key] = Array.new();
		end
		user_skin_list[key] << row['type'];
	};
	
	skin_res.free();
	skin_res = nil;


	last_user = nil
	context = {}


	res = $site.dbs[:db].query("SELECT * FROM profileskins WHERE userid % 100 = ? ORDER BY userid", i)

	res.each{|row|
	
		#reset the context for each user to keep the user -> serverid mapping but avoid the memory leak of keeping everything
		if(last_user != row['userid'])
			context = {}
			last_user = row['userid']
		end
	
		$site.cache.use_context( context ) {
			skin_id = convert_skin(row);
			
			if(!user_skin_list["#{row['userid']}-#{row['id']}"].nil?())
				skin_change = Array.new();
				user_skin_list["#{row['userid']}-#{row['id']}"].each{|type|
					if(type.to_i() == 1)
						 skin_change << "galleryskin = #{skin_id}";
					elsif(type.to_i() == 2)
						skin_change << "commentsskin = #{skin_id}";
					elsif(type.to_i() == 3)
						skin_change << "profileskin = #{skin_id}";
					elsif(type.to_i() == 4)
						skin_change << "friendsskin = #{skin_id}";
					elsif(type.to_i() == 5)
						skin_change << "blogskin = #{skin_id}";
					end
				};

				$site.dbs[:usersdb].query("UPDATE users SET #{skin_change.join(', ')} WHERE userid = #", row['userid']);
			end
		}
	}
	$log.info("Finished group #{i} at #{Time.now}");
}
