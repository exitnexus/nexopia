require 'singleton';
lib_require :Core, 'json', 'visibility';
lib_want :GoogleProfile, 'google_user';

module Blogs
	class BlogVisibility < Visibility
		include Singleton;
		
		attr_accessor :blog_visibility_name_list;
		
		def initialize()
			super();
			self.blog_visibility_name_list = {:none => "Private", :friends => "Friends", :logged_in => "Logged In", :all => "Public"};
		end
		
		def generate_json()
			if(@json.nil?())
				@json = self.visibility_options_list.to_json();
			end
			return @json;
		end
		
		def self.list
			return self.instance.visibility_list;
		end
		
		def self.options
			return self.instance.visibility_options_list;
		end
		
		def self.blog_visibility_name(visibility)
			if(visibility.kind_of?(Integer))
				visibility = self.instance.inverse_visibility_list[visibility.to_s()];
			end
			
			return self.instance.blog_visibility_name_list[visibility];
		end
		
		def self.json()
			return self.instance.generate_json();
		end
		
		def self.visible?(visibility_level, blog_user, view_user)
			#Sometimes the visibility_level is sent in as a number, so we need to convert it to
			#the proper symbol. If the visibility level is not found, raise an error.
			if(visibility_level.class == Integer || visibility_level.class == Fixnum)
				orig_vis = visibility_level;
				visibility_level = self.instance.inverse_visibility_list[visibility_level.to_s()];
				if(visibility_level.nil?())
					raise "Visibility level: #{orig_vis} not found";
				end
 			end

			if(blog_user.userid == view_user.userid) 
				return true;
			end
			
			if(visibility_level == :friends)
				return blog_user.is_friend?(view_user);
			elsif(visibility_level == :friends_of_friends)
				return blog_user.is_friend?(view_user) || blog_user.is_friend_of_friend?(view_user);
			elsif(visibility_level == :logged_in)
				return !view_user.anonymous?();
			elsif(visibility_level == :all)
				return true;
			end
			
			return false;
		end
		
		def self.determine_visibility_level(blog_user, viewing_user, admin_viewer = false)
			if(blog_user.userid == viewing_user.userid)
				lowest_visibility = self.list[:none];
			elsif(admin_viewer)
				lowest_visibility = self.list[:friends];
			else
				lowest_visibility = 4;
			end
			
			self.list.keys.each{ |visibility|
				if(lowest_visibility == 0)
					break;
				end
				
				if(self.visible?(visibility, blog_user, viewing_user))
					if(self.list[visibility] < lowest_visibility)
						lowest_visibility = self.list[visibility];
					end
				end		
			};
			
			return lowest_visibility;
		end
	end
end
