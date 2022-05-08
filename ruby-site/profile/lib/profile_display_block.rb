lib_require	:Profile, "profile_block_visibility", "profile_block_query_mediator", 'profile';
lib_require :Core, 'storable/cacheable', 'users/user'

# ProfileDisplayBlock is a generic wrapper object for any profile block. The Profile
#  is a collection of these blocks belonging to a user.
#
# Blocks may or may not have additional backing objects. This is usually determined by if
#  the block can be rendered by just knowing the user. The freeform text blocks provide an
#  example where this information is not sufficient. The freeform text object's id will match
#  the blockid of the ProfileDisplayBlock.
#
# Handlers for Profile blocks have to be defined in the Users area and following convention:
#   profile_blocks/{ModuleName}/{path}/{Integer(block_id)}/
#   profile_blocks/{ModuleName}/{path}/new/
#   profile_blocks/{ModuleName}/{path}/{Integer(block_id)}/edit
#   profile_blocks/{ModuleName}/{path}/{Integer(block_id)}/save
#   profile_blocks/{ModuleName}/{path}/{Integer(block_id)}/visibility
#   profile_blocks/{ModuleName}/{path}/{Integer(block_id)}/create
#   profile_blocks/{ModuleName}/{path}/{Integer(block_id)}/remove
#   profile_blocks/{ModuleName}/{path}/{Integer(block_id)}/edit/in_place

module Profile
	class ProfileDisplayBlock < Cacheable
		extend TypeID;

		set_enums(
			:visibility => ProfileBlockVisibility.list
		);
		
		init_storable(:usersdb, "profiledisplayblocks");
		
		relation :singular, :profile, [:userid], Profile
		
		attr_accessor :visibility_options_list, :rendered_content, :visible_wrapper, :content_error;
		
		def block_uri(edit_mode = false)
			temp_name = TypeID.get_class(self.moduleid).name;
			mod_name = temp_name.sub("Module", "");
			
			uri_str = "/profile_blocks/#{mod_name}/#{self.path}/#{self.blockid}/";
			
			if(edit_mode && self.block_info().in_place_editable())
				uri_str = uri_str + "edit/in_place/";
			end
		
			uri_str = uri_str + ":Body"
		
			return uri_str;
		end
		
		def block_area(edit_mode, profile_user)
			if(edit_mode && self.block_info().in_place_editable())
				return :Self;
			end
			
			return [:User, profile_user];
		end
		
		def edit_uri()
			temp_name = TypeID.get_class(self.moduleid).name;
			mod_name = temp_name.sub("Module", "");
			
			uri_str = "/profile_blocks/#{mod_name}/#{self.path}/#{self.blockid}/edit";
			
			return uri_str;
		end		
		
		def remove_uri()
			temp_name = TypeID.get_class(self.moduleid).name;
			mod_name = temp_name.sub("Module", "");
			
			uri_str = "/profile_blocks/#{mod_name}/#{self.path}/#{self.blockid}/remove";
			
			return uri_str;
		end
		
		def visibility_save_uri()
			temp_name = TypeID.get_class(self.moduleid).name;
			mod_name = temp_name.sub("Module", "");
			
			uri_str = "/profile_blocks/#{mod_name}/#{self.path}/#{self.blockid}/visibility";
		end	
		
		def visible?(profile_user, view_user, edit_mode = false)
			if(self.visibility == :admin && PageRequest.current.session.has_priv?(CoreModule, "listusers"))
				return true;
			elsif(self.visibility == :admin)
				return false;
			end
			
			if(edit_mode && self.visibility == :none && ((profile_user.userid == view_user.userid) || PageRequest.current.session.has_priv?(CoreModule, "listusers")))
				return true;
			end
			
			if(self.visibility != :none && PageRequest.current.area == :Self && PageRequest.current.impersonation?() && PageRequest.current.session.has_priv?(CoreModule, "editprofile"))
				return true;
			end
			
			return ProfileBlockVisibility.visible?(self.visibility, profile_user, view_user);
		end
		
		# Sorting profile display blocks will return them in the sorted order of their position.
		#  Do note that column is not taken into consideration.
		def <=>(anOther)
			if(!anOther.kind_of?(ProfileDisplayBlock))
				raise(ArgumentError.new("#{anOther.class} is not comparable with #{self.class}"));
			end
			if(self.position < anOther.position)
				return -1;
			elsif(self.position > anOther.position)
				return 1;
			else
				return 0;
			end
		end
		
		def generate_javascript(html_id)
			t = Template.instance("profile", "javascript_profile_display_block");
			
			t.display_block = self;
			t.html_id = html_id;
			
			return t.display();
		end
		
		def module_name
			block_module = TypeID.get_class(self.moduleid);
			block_module_name = block_module.name.gsub(/Module$/, '');
			return block_module_name;
		end
		
		# A special case for the control block (the top left block with the username). This is to return the
		#  the proper class since the control block has specific styling.
		def control_block()
			if(self.path == "control" &&  TypeID.get_class(self.moduleid).name == "ProfileModule")
				return "control_block";
			end
			return "primary_block";
		end
		
		def block_info()
			return ProfileBlockQueryMediator.instance.query_block(module_name, path);
		end
			
		def moveable()
			return block_info.moveable;
		end
		
		
		def valid_column(column_id)
			if (block_info.form_factor == :narrow && column_id != 0 || 
					block_info.form_factor == :wide && column_id != 1)
				return false;
			else
				return true
			end
		end
		
		def valid_visibility?(visibility)
			return block_info.valid_visibility?(visibility);
		end

		def owner
			return User.get_by_id(@userid);
		end
		
		def after_create
			self.profile.update!
			if (site_module_loaded?(:GoogleProfile))
				self.owner.update_hash
			end
			super
		end

		def after_update
			self.profile.update!
			if (site_module_loaded?(:GoogleProfile))
				self.owner.update_hash
			end
			super
		end

		def before_delete
			self.profile.update!
			if (site_module_loaded?(:GoogleProfile))
				self.owner.update_hash
			end
		end
		
		def ProfileDisplayBlock.verify_visibility(block_id, profile_user, view_user, edit_mode = false)
			block = self.find(:first, profile_user.userid, block_id);
			
			if(block.nil?())
				return false;
			end
			
			return block.visible?(profile_user, view_user, edit_mode);
		end
		
		def current_column
			if(@columnid == 1)
				return :wide;
			else
				return :narrow;
			end
		end
	end
end
