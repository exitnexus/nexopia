module ProfileBlockQueryInfo
	# True if you can move it, false if not.
	attr_accessor :moveable
	
	# :wide if it fits in a wide column, :narrow if it fits in a narrow column, and
	# :both if it fits in either.
	attr_accessor :form_factor
	
	# The name shown on the "add block" menu
	attr_accessor :title
	
	# True if only available to plus, false if available to everyone.
	attr_accessor :plus_only
	
	# How long the profile display can cache the contents of the block. 0 if
	# it shouldn't be cached at all.
	attr_accessor :content_cache_timeout
	
	# true if the block can be removed by the user, false otherwise
	attr_accessor :removable
	
	# true if the block can be edited, false otherwise
	attr_accessor :editable
	
	# the column number the block belongs in on initial profile creation. Nil
	# if it shouldn't be added on profile creation.
	attr_accessor :initial_column
	
	# the numerical position of the block on the page on initial profile creation.
	# Ignored if initial_column is nil. Blocks within a column are placed in sorted
	# ascending order downwards when created.
	attr_accessor :initial_position
	
	# true if you can have more than one of this block on a profile
	attr_accessor :multiple
	
	# maximum number of this type of block you can have on your profile. 0
	# if any number, any other number indicates a maximum. Ignored if
	# mulitple is false.
	# Can also be set to a class with a static function called "max_number".
	# If so this function will be called with the current session user to
	# figure out the max number. This is needed for values which vary
	# between Plus users and non-Plus users (See Freeform Text block for example). 
	attr_accessor :max_number
	
	# true if the block should be added on profile creation
	attr_accessor :initial_block
	
	# how visible the block should be by default.
	# :all for everyone
	# :logged_in for logged in users
	# :friends_of_friends for friends of the user's friends
	# :friends for only the user's friends
	# :none not displayed on the profile
	attr_accessor :default_visibility
	
	# whether the block uses the built in save mechanism via a form submit. Set
	# to false if the block does its own internal handling of saving its data.
	attr_accessor :explicit_save
	
	# description of a path to a full page that corresponds to the block as part
	# of the profile area (nil if there is none). Format is:
	# ["title", url/:path, order] where "title" is the name, url/:path is a path
	# under url/:User/profile, and order is for the ordering of the links on the
	# header bar. If page_per_block is set, the block id will be added
	# to the url given. If "title" is not a string, it's assumed to be a block into
	# which the block id will be given that returns a string.
	attr_accessor :page_url
	
	# If true, the profile menu will include one entry for each block of this
	# type on the profile page (including none if the block is not on the page at
	# all)
	attr_accessor :page_per_block
	
	# the name of the module this comes from (set implicitly)
	attr_accessor :module_name
	
	# path to the block (set implicitly)
	attr_accessor :path
	
	# module id (set implicitly)
	attr_accessor :module_id;
	
	# a javascript function that will be called immediately before showing an edit 
	# dialog for the block
	attr_accessor :javascript_init_function;
	
	# whether the block will be wrapped in a primary color container element. Defaults
	# to true.
	attr_accessor :visible_wrapper;
	
	#whether or not a block can be edited by an admin.
	attr_accessor :admin_editable;
	
	#whether or not a block can be removed by an admin.
	attr_accessor :admin_removable;
	
	# This specifies the first path selector after the user area that this block matches.
	# By default, it will pull apart the second element in the page_url array to determine
	# the first path selector. If more path selectors are needed than what can be extracted
	# from page_url, it needs to be explicitly set.
	#
	# For the profile section it is overridden in pagehandlers/profile_control_block.rb to
	# ["", "profile"]
	# If it relied on the default implementation it would be ["profile"] (from page_url[1]
	# which is "/profile")
	attr_accessor :pagehandler_section_list;
	
	# Stores if the section being viewed has a user area header menu edit control.
	# We store it because we don't want to check for the existence of the file
	# for every user area load.
	# The values will be:
	# nil if it hasn't been looked at yet
	# false if the template doesn't exist
	# true if it does
	attr_accessor :header_edit_control;
	
	attr_accessor :visibility_exclude;
	
	attr_accessor :in_place_editable;
	
	attr_accessor :custom_edit_button;
	
	attr_accessor :immutable_after_create;
	
	def moveable
		if(PageRequest.current.area == :Self && PageRequest.current.impersonation?())
			return false;
		end
		
		if(@moveable.nil?())
			return true;
		end
		return @moveable;
	end
	
	def form_factor
		if(@form_factor.nil?())
			return :wide;
		end
		return @form_factor;
	end
	
	def plus_only
		if(@plus_only.nil?())
			return false;
		end
		return @plus_only;
	end
	
	def content_cache_timeout
		if(@content_cache_timeout.nil?())
			return 0;
		end
		return @content_cache_timeout;
	end
	
	def removable
		if(PageRequest.current.area == :Self && PageRequest.current.impersonation?())
			return admin_removable;
		end
		
		if(@removable.nil?())
			return true;
		end
		return @removable;
	end
	
	def editable
		if(PageRequest.current.area == :Self && PageRequest.current.impersonation?())
			return admin_editable;
		end
		
		if(@editable.nil?())
			return true;
		end
		return @editable;
	end
	
	def immutable_after_create
		if(@immutable_after_create.nil?())
			return false;
		end
		
		return @immutable_after_create;
	end
	
	def multiple
		if(@multiple.nil?())
			return true;
		end
		return @multiple;
	end
	
	def initial_block
		if(@initial_block.nil?())
			return !self.removable;
		end
		return @initial_block;
	end
	
	def default_visibility()
		if(@default_visibility.nil?())
			return :all;
		end
		return @default_visibility;
	end
	
	def max_number()
		if(@max_number.nil?())
			if(self.multiple)
				return 0;
			end
			return 1;
		end
		
		if(@max_number.kind_of?(Class) && @max_number.respond_to?(:max_number))
			return @max_number.send(:max_number, PageRequest.current.session.user);
		elsif(@max_number.kind_of?(Class))
			return 1;
		end
		
		return @max_number;
	end
	
	
	def javascript_init_function()
		if (@javascript_init_function.nil?)
			return JavascriptFunction.new(nil);
		end
		
		return @javascript_init_function;
	end
	
	
	def module_id()
		if(@module_id.nil?())
			name = "#{self.module_name}Module";
			module_type_id = TypeID.get_typeid(name);
			@module_id = module_type_id;
		end
		
		return @module_id;
	end
	
	def explicit_save()
		if(@explicit_save.nil?())
			return true;
		end
		return @explicit_save;
	end
	
	def data_accessors
		list = ProfileBlockQueryInfo.instance_methods.to_a();
		filtered_list = Array.new();
		
		for item in list
			if(!/\w=$/.match(item))
				filtered_list << item;
			end
		end
		
		filtered_list = filtered_list - ["path", "module_id", "content_cache_timeout", "data_accessors", "initial_block", "generate_javascript", "page_url", "page_per_block", "pagehandler_section_list", "header_edit_control", "add_visibility_exclude", "visibility_exclude", "valid_visibility?", "default_visibility"];
		
		return filtered_list;
	end
	
	def generate_javascript()
		t = Template.instance("profile", "javascript_profile_block_info");
		t.block_info = self;

		return t.display();
	end
	
	def generate_key()
		s = "#{self.module_id}-#{self.path}";
		return s;
	end
	
	def visible_wrapper
		if(@visible_wrapper.nil?())
			return true;
		end
		return @visible_wrapper;
	end
	
	def admin_editable
		if(@admin_editable.nil?())
			return false;
		end
		return @admin_editable;
	end
	
	def admin_removable
		if(@admin_removable.nil?())
			return false;
		end
		return @admin_removable;
	end
	
	def page_per_block
		if(@page_per_block.nil?())
			return false;
		end
		return @page_per_block;
	end
	
	def pagehandler_section_list
		if(@pagehandler_section_list.nil?())
			temp = Array.new();
			if(!@page_url.nil?)
				temp_page_url_parts = @page_url[1].split("/");
				temp << temp_page_url_parts[1];
			end
			
			@pagehandler_section_list = temp;
		end
		return @pagehandler_section_list;
	end
	
	def visibility_exclude
		if(@visibility_exclude.nil?())
			temp = Array.new();
			temp << :admin;
			@visibility_exclude = temp;
		end
		return @visibility_exclude;
	end
	
	def javascript_visibility_exclude
		temp = self.visibility_exclude.map{|x|
				Profile::ProfileBlockVisibility.instance.visibility_list[x];
			}.join(",");
		
		return temp;
	end
	
	def javascript_default_visibility
		return Profile::ProfileBlockVisibility.instance.visibility_list[self.default_visibility];
	end
	
	def valid_visibility?(visibility)
		if(visibility.kind_of?(Symbol))
			visibility = Profile::ProfileBlockVisibility.instance.visibility_list[visibility];
		end
		
		#make sure the provided visibility is a defined visibility level.
		if(visibility.nil? || !Profile::ProfileBlockVisibility.instance.inverse_visibility_list.has_key?(visibility.to_s()))
			return false;
		end
		
		temp = self.visibility_exclude.map{|x|
			Profile::ProfileBlockVisibility.instance.visibility_list[x];
		};
		
		if(temp.include?(visibility))
			return false;
		else
			return true;
		end
		
	end
	
	def add_visibility_exclude(vis)
		if(!vis.nil?() && vis.kind_of?(Symbol))
			self.visibility_exclude << vis;
		elsif(!vis.nil?() && vis.kind_of?(Array))
			for item in vis
				if(item.kind_of?(Symbol))
					self.visibility_exclude << item;
				end
			end
		end
	end
	
	def in_place_editable
		if(@in_place_editable.nil?())
			return false;
		end
		
		return @in_place_editable;
	end
	
	def custom_edit_button()
		if(@custom_edit_button.nil?())
			return "null";
		end
		
		return @custom_edit_button;
	end
	
	class JavascriptFunction
		def initialize(function_name)
			@function_name = function_name;
		end
		
		def to_s
			if (@function_name.nil?)
				return "null";
			end
			
			return @function_name;
		end
	end	
end
