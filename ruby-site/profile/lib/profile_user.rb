lib_require	:Core, "users/user";
lib_require	:Profile, "profile";
lib_require	:Profile, "profile_block_query_mediator", "profile_display_block", "profile_block";
lib_require	:Profile, "profile_block_visibility";
lib_require :Profile, "user_skin";
lib_require :Profile, "user_task";
lib_require :Profile, "new_user_task_mediator";

lib_want :Interstitial, "interstitial_user";

class User < Cacheable
	relation :singular, :profile, [:userid], Profile::Profile;
	relation :multi, :profile_blocks, [:userid], Profile::ProfileDisplayBlock
	relation :multi, :freeform_text_blocks, [:userid], Profile::ProfileBlock
	relation :singular, :rel_profile_skin, [:userid, :profileskin], Profile::UserSkin
	relation :singular, :rel_friends_skin, [:userid, :friendsskin], Profile::UserSkin
	relation :singular, :rel_gallery_skin, [:userid, :galleryskin], Profile::UserSkin
	relation :singular, :rel_blog_skin, [:userid, :blogskin], Profile::UserSkin
	relation :singular, :rel_comments_skin, [:userid, :commentsskin], Profile::UserSkin
	relation :multi, :user_task_list, [:userid], Profile::UserTask
	
	attr_accessor :cached_profile_skin, :cached_friends_skin, :cached_gallery_skin, :cached_blog_skin, :cached_comments_skin;
	
	self.postchain_method(:after_create, &lambda {
							  
			profile = Profile::Profile.new();
			profile.userid = self.userid;
			profile.tagline = '';
			profile.ntagline = '';
			profile.signiture = '';
			profile.nsigniture = '';
			profile.about = '';
			profile.likes = '';
			profile.dislikes = '';
			profile.firstname = '';
			profile.lastname = '';
			profile.profileupdatetime = Time.now.to_i();
			profile.store();
			
			# If you try to create a user via the console you will get an error from this line. This is expected and 
			#  necessary. The user has been created, the failure has to do with the creation of profile blocks.
			#
			# There is a link here to the pagehandler subsystem. This was done so we could discover profile blocks
			#  without having to explicitly tie blocks to pagehandlers or something of the like. The connection only
			#  exists so far as needing a current pagerequest (which is missing in the console causing the error) with
			#  which to do some query subrequests. It was assumed normal user creation would take place within a webrequest,
			#  thus the assumption we'd have a request object. This was a design choice with the system.
			#
			#get the list of all the mandatory blocks
			block_list = Profile::ProfileBlockQueryMediator.instance.initial_blocks();
			
			#setting up the display blocks with default values.
			temp_columns = Array.new();
			temp = nil;
			for block in block_list
				block_id = Profile::ProfileDisplayBlock.get_seq_id(self.userid);

				temp = Profile::ProfileDisplayBlock.new();
				temp.blockid = block_id;
				temp.userid = self.userid;
				temp.visibility = block.default_visibility;
				temp.columnid = block.initial_column;
				temp.position = block.initial_position;
				
				#this will break if a profile block does not use the standard
				#set up for profile blocks. The standard being the base path for
				#the block being: profile_blocks/{ModuleName}/{path}
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
			
			task_list = Profile::NewUserTaskMediator.task_list();
			
			task_list.each{|task|
				temp = Profile::UserTask.new();
				temp.userid = self.userid;
				temp.taskid = task.taskid;
				
				temp.store();
			};
	});
	
	def real_name(user)
		return profile.real_name(user)
	end
	
	def skinable_areas()
		list = Array.new();
		
		for col in self.columns.keys()
			temp_col = col.to_s();
			if(temp_col.match(/\wskin$/))
				list << temp_col.sub(/skin$/, '');
			end
		end
		return list;
	end
	
	def skin_applied_to_all?()
		if(self.profileskin == 0)
			return false;
		end
		
		skin_total = 0;
		for area in self.skinable_areas
			temp = self.send(area.concat("skin").to_sym());
			skin_total = skin_total + temp;
		end
		
		return skin_total == self.profileskin*self.skinable_areas.length;
	end
	
	def skin_selected(skinable_area, skin_id)
		skin_area = skinable_area + "skin";
		
		user_skin_value = self.send(skin_area.to_sym());
		if(user_skin_value == skin_id)
			return "selected";
		end
		return false;
	end
	
	def profile_skin
		if(!@cached_profile_skin.nil?())
			return @cached_profile_skin;
		end
		
		if(!self.rel_profile_skin.nil?() && self.plus?())
			@cached_profile_skin = self.rel_profile_skin;
		else
			temp = Profile::UserSkin.new();
			temp.init_from_site_theme(PageRequest.current.session.user.skin)
			@cached_profile_skin = temp;
		end
		
		return @cached_profile_skin;
	end
	
	def gallery_skin
		if(!@cached_gallery_skin.nil?())
			return @cached_gallery_skin;
		end
		
		if(!self.rel_gallery_skin.nil?() && self.plus?())
			@cached_gallery_skin = self.rel_gallery_skin;
		else
			temp = Profile::UserSkin.new();
			temp.init_from_site_theme(PageRequest.current.session.user.skin)
			@cached_gallery_skin = temp;
		end
		
		return @cached_gallery_skin;
	end
	
	def blog_skin
		if(!@cached_blog_skin.nil?())
			return @cached_blog_skin;
		end
		
		if(!self.rel_blog_skin.nil?() && self.plus?())
			@cached_blog_skin = self.rel_blog_skin;
		else
			temp = Profile::UserSkin.new();
			temp.init_from_site_theme(PageRequest.current.session.user.skin)
			@cached_blog_skin = temp;
		end
		
		return @cached_blog_skin;
	end
	
	def comments_skin
		if(!@cached_comments_skin.nil?())
			return @cached_comments_skin;
		end
		
		if(!self.rel_comments_skin.nil?() && self.plus?())
			@cached_comments_skin = self.rel_comments_skin;
		else
			temp = Profile::UserSkin.new();
			temp.init_from_site_theme(PageRequest.current.session.user.skin)
			@cached_comments_skin = temp;
		end
		
		return @cached_comments_skin;
	end
	
	def friends_skin
		if(!@cached_friends_skin.nil?())
			return @cached_friends_skin;
		end
		
		if(!self.rel_friends_skin.nil?() && self.plus?())
			@cached_friends_skin = self.rel_friends_skin;
		else
			temp = Profile::UserSkin.new();
			temp.init_from_site_theme(PageRequest.current.session.user.skin)
			@cached_friends_skin = temp;
		end
		
		return @cached_friends_skin;
	end
end
