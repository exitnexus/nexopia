require 'singleton';

class Visibility
	include Singleton;
	
	attr_accessor :visibility_list, :visibility_options_list, :inverse_visibility_list, :module_specific_options;
	
	def initialize()
		self.visibility_list = {:none => 0, :friends => 1, :friends_of_friends => 2, :logged_in => 3, :all => 4, :admin => 5 };
		self.visibility_options_list = [
			["Not Visible", self.visibility_list[:none]],
			["Visible to Friends", self.visibility_list[:friends]],
			["Visible to Logged In Users", self.visibility_list[:logged_in]],
			["Visible to All", self.visibility_list[:all]]
		];
		
		#We need an inverted value/key visibility list for conversions needed by visible?
		self.inverse_visibility_list = Hash.new();
		self.visibility_list.keys.each{|key|
			#WARNING: not sure why we are storing the integers here as strings but it is accessed
			#elsewhere as well and numbers are converted to strings at the access points.  If this
			#gets changed it needs to be changed everywhere.
			self.inverse_visibility_list[self.visibility_list[key].to_s()] = key;
		};
		
		self.module_specific_options = {};
	end
	
	
	def Visibility.set_module_specific_options(site_module, options)
		Visibility.instance.module_specific_options[site_module] = options;
	end
	
	
	def Visibility.list(site_module=nil)
		if (site_module.nil?)
			return Visibility.instance.visibility_list;
		end
		
		if (Visibility.instance.module_specific_options[site_module].nil?)
			throw "Checking for module specific options in a module for which none have been set."
		end
		
		options = Hash.new;
		Visibility.instance.visibility_list.each { |key, value| 
			if (Visibility.instance.module_specific_options[site_module].member?(key))
				options[key] = value;
			end
		};
		
		return options;
	end
	
	def Visibility.options(site_module=nil)
		if (site_module.nil?)
			return Visibility.instance.visibility_options_list;
		end	
		
		if (Visibility.instance.module_specific_options[site_module].nil?)
			throw "Checking for module specific options in a module for which none have been set."
		end
		
		options = Array.new;
		Visibility.instance.visibility_options_list.each { |key, value| 
			if (Visibility.instance.module_specific_options[site_module].member?(Visibility.instance.visibility_list.invert[key]))
				options << [key,value];
			end
		};
		
		return options;
	end
end
