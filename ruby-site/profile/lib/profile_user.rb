lib_require :Profile, "profile_block_query_mediator", "profile_display_block";
lib_require :Profile, "profile";

class User < Cacheable
	relation_singular :profile, :userid, Profile::Profile;
	
	self.postchain_method(:after_create, &lambda {
			#get the list of all the mandatory blocks
			block_list = Profile::ProfileBlockQueryMediator.instance.initial_blocks();
			
			#setting up the display blocks with default values.
			temp_columns = Array.new();
			for block in block_list
				block_id = Profile::ProfileDisplayBlock.get_seq_id(self.userid);
				temp = Profile::ProfileDisplayBlock.new();
				temp.blockid = block_id;
				temp.userid = self.userid;
				temp.visibility = block.default_visibility;
				temp.columnid = block.initial_column;
				temp.position = block.initial_position;
				
				#this will break if a profile block does not use the standard
				#set up for profile blocks.
				#TODO: Ask graham about the problems (not existing) with cur_page.
				path_components = block.klass.base_path;
				temp.path = path_components[2];
				module_name = "#{path_components[1]}Module";
				module_type_id = TypeID.get_typeid(module_name);
				temp.moduleid = module_type_id;
				
				#if the column doesn't exist, create an array for it
				if(temp_columns[temp.columnid].nil?())
					temp_columns[temp.columnid] = Array.new();
				end
				
				temp_columns[temp.columnid] << temp;
			end
			
			#sort the blocks in the column, set their position correctly and store
			#them in the database
			for column in temp_columns
				column.sort!
				i = 0;
				while i<column.length
					column[i].position = i;
					column[i].store();
					i = i + 1;
				end
			end
	});
	
	def real_name(user)
		first_name = "";
		last_name = "";

		if ((profile.firstnamevisibility == :all && !user.anonymous?()) ||
			(profile.firstnamevisibility == :friends && self.friend?(user)) ||
			(profile.firstnamevisibility == :friends_of_friends && (self.friend?(user) || self.friend_of_friend?(user)))
		)
			first_name = self.firstname
		end
		
		if ((profile.lastnamevisibility == :friends && self.friend?(user)) ||
			(profile.lastnamevisibility == :friends_of_friends && (self.friend?(user) || self.friend_of_friend?(user)))
		)
			last_name = self.lastname
		end
		
		return "#{first_name} #{last_name}".strip
	end
	
	def skinable_areas()
		list = Array.new();
		
		for col in self.columns.keys()
			if(col.match(/\wskin$/))
				list << col.sub(/skin$/, '');
			end
		end
		return list;
	end
	
	def skin_applied_to_all()
		if(self.profileskin == 0)
			return false;
		end
		
		skin_total = 0;
		for area in self.skinable_areas
			skin_total = skin_total + self.send(area.concat("skin"));
		end
		
		if(skin_total == self.profileskin*self.skinable_areas.length)
			return "selected";
		else
			return false;
		end
	end
	
	def skin_selected(skinable_area, skin_id)
		skin_area = skinable_area + "skin";
		
		user_skin_value = self.send(skin_area.to_sym());
		if(user_skin_value == skin_id)
			return "selected";
		end
		return false;
	end
end
