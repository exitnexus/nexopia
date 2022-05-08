require 'singleton'
require 'json'

module Profile
	class ProfileBlockVisibility
		include Singleton;
		
		attr_accessor :visibility_list, :visibility_options_list;
		
		def initialize()
			self.visibility_list = {:none => 0, :friends => 1, :friends_of_friends => 2, :logged_in => 3, :all => 4 };
			self.visibility_options_list = [
				["Not Visible", self.visibility_list[:none]],
				["Visible to Friends", self.visibility_list[:friends]],
				["Visible to Friends of Friends", self.visibility_list[:friends_of_friends]],
				["Visible to Logged In Users", self.visibility_list[:logged_in]],
				["Visible to All", self.visibility_list[:all]]
			];
		end
		
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
	end
end
