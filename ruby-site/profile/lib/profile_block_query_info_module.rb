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
	# if any number, any other number indicates a maximum.
	attr_accessor :max_number
	
	# true if the block should be added on profile creation
	attr_accessor :initial_block
	
	# how visible the block should be by default. :all for everyone. (fill in
	# rest of possibilities later)
	attr_accessor :default_visibility
	
	# whether the block uses the built in save mechanism via a form submit. Set
	# to false if the block does its own internal handling of saving its data.
	attr_accessor :explicit_save
	
	# the name of the module this comes from (set implicitly)
	attr_accessor :module_name
	
	# path to the block (set implicitly)
	attr_accessor :path
	
	# module id (set implicitly)
	attr_accessor :module_id
	
	# an javascript function that will be called immediately before showing an edit 
	# dialog for the block
	attr_accessor :javascript_init_function
	
	def moveable
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
		if(@removable.nil?())
			return true;
		end
		return @removable;
	end
	
	def editable
		if(@editable.nil?())
			return true;
		end
		return @editable;
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
		
		filtered_list = filtered_list - ["path", "module_id", "content_cache_timeout", "data_accessors", "initial_block", "generate_javascript"];
		
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
