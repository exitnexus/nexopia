require 'singleton';
lib_require :Core, 'json', 'visibility';
lib_want :GoogleProfile, 'google_user';

module Profile
	class ProfileBlockVisibility < Visibility
		include Singleton;
		
		def generate_json()
			if(@json.nil?())
				@json = self.visibility_options_list.to_json();
			end
			return @json;
		end
		
		def generate_javascript()
			if(@jscript.nil?())
				t = Template.instance("profile", "javascript_visibility_options");
				t.visibility_json = ProfileBlockVisibility.json();
				
				@jscript = t.display();
			end
			return @jscript;
		end
		
		def ProfileBlockVisibility.list
			return ProfileBlockVisibility.instance.visibility_list;
		end
		
		def ProfileBlockVisibility.options
			return ProfileBlockVisibility.instance.visibility_options_list;
		end
		
		def ProfileBlockVisibility.json()
			return ProfileBlockVisibility.instance.generate_json();
		end
		
		def ProfileBlockVisibility.javascript()
			return ProfileBlockVisibility.instance.generate_javascript();
		end
		
		def ProfileBlockVisibility.visible?(visibility_level, profile_user, view_user)
			#Sometimes the visibility_level is sent in as a number, so we need to convert it to
			#the proper symbol. If the visibility level is not found, raise an error.
			if(visibility_level.class == Integer || visibility_level.class == Fixnum)
				orig_vis = visibility_level;
				visibility_level = ProfileBlockVisibility.instance.inverse_visibility_list[visibility_level.to_s()];
				if(visibility_level.nil?())
					raise "Visibility level: #{orig_vis} not found";
				end
 			end

			if(profile_user.userid == view_user.userid && visibility_level != :none) 
				return true;
			end
			
			if(visibility_level == :friends)
				return profile_user.is_friend?(view_user);
			elsif(visibility_level == :friends_of_friends)
				return profile_user.is_friend?(view_user) || profile_user.is_friend_of_friend?(view_user);
			elsif(visibility_level == :logged_in)
				return !view_user.anonymous?();
			elsif(visibility_level == :all)
				return true;
			end
			
			return false;
		end	
	end
end
