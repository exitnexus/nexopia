require 'singleton';

class Visibility
	include Singleton;
	
	attr_accessor :visibility_list, :visibility_options_list;
	
	def initialize()
		self.visibility_list = {:none => 0, :friends => 1, :friends_of_friends => 2, :all => 3 };
		self.visibility_options_list = {
			self.visibility_list[:none] => "Not Visible",
			self.visibility_list[:friends] => "Visible to Friends",
			self.visibility_list[:friends_of_friends] => "Visible to Friends of Friends",
			self.visibility_list[:all] => "Visible to All"
		};
	end
	
	def Visibility.list
		return Visibility.instance.visibility_list;
	end
	
	def Visibility.options
		return Visibility.instance.visibility_options_list;
	end
end
