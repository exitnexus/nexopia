class DevConfig < ConfigBase
	name_config "dev";

	def initialize()
		super();
		@base_domain = 'ruby.dev.nexopia.com';
		@site_base_dir = '/home/nexopia/site-ruby';
		@template_use_cached = false;
		@ipaddr = 0;
		@port = 1026;
		@num_children = 5;
		#much more needs to be set here. Some stuff (see below) is defined in terms of these.
	end

	# The following config variables are generally based on the top two defined in
	# initialize, but configs deriving from this one can set them as instance
	# variables in their own initialize() to override them.
	def www_domain
		@www_domain || "www.#{@base_domain}";
	end
	def plus_www_domain
		@plus_www_domain || "plus.www.#{@base_domain}";
	end
	def admin_domain
		@admin_domain || "admin.www.#{@base_domain}";
	end
	def user_domain
		@user_domain || "users.#{@base_domain}";
	end
	def plus_user_domain
		@plus_user_domain || "plus.users.#{@base_domain}";
	end
	def static_domain
		@static_domain || "static.#{@base_domain}";
	end
	def image_domain
		@user_domain || "image.#{@base_domain}";
	end
	def user_files_domain
		@user_files_domain || "files.#{@base_domain}";
	end
	def email_domain
		@email_domain || @base_domain;
	end
	def cookie_domain
		@cookie_domain || "www.#{@base_domain}";
	end

	def doc_root
		@doc_root || "#{@site_base_dir}/experiments";
	end
	def static_root
		@static_root || "#{@site_base_dir}/static";
	end

	def template_base_dir
		@template_base_dir || "#{@site_base_dir}/templates";
	end
	def template_files_dir
		@template_files_dir || "#{@template_base_dir}/template_files";
	end
	def template_parse_dir
		@template_parse_dir || "#{@template_base_dir}/compiled_files";
	end
end
