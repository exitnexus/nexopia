lib_require	:Profile, "profile_block_visibility";

module Profile
	class ProfileDisplayBlock < Storable
		extend TypeID;
		
		set_enums(
			:visibility => ProfileBlockVisibility.list
		);
		
		init_storable(:usersdb, "profiledisplayblocks");
		
		attr_accessor :visibility_options_list, :rendered_content;
		
		def block_uri()
			temp_name = TypeID.get_class(self.moduleid).name;
			mod_name = temp_name.sub("Module", "");
			
			uri_str = "/profile_blocks/#{mod_name}/#{self.path}/#{self.blockid}/:Body";
			
			return uri_str;
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
		
		def visible?(profile_user, view_user)
			if(profile_user.userid == view_user.userid && self.visibility != :none)
				return true;
			end
			
			if(self.visibility == :friends)
				return profile_user.is_friend?(view_user);
			elsif(self.visibility == :friends_of_friends)
				return profile_user.is_friend?(view_user) || profile_user.is_friend_of_friend?(view_user);
			elsif(self.visibility == :logged_in)
				return !view_user.anonymous?();
			elsif(self.visibility == :all)
				return true;
			end
			
			return false;
		end
		
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
	end
end
