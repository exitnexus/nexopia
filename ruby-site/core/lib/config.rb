## this is the base config file for the site.

config_name = "dev";

$stderr.puts("Chose config #{$config_name}");

class ConfigBase
	attr_reader :ipaddr, :port, :num_children;

	attr_reader :base_domain, :www_domain, :plus_www_domain, :admin_domain,
	            :user_domain, :plus_user_domain, :static_domain, :image_domain,
	            :user_files_domain, :email_domain, :cookie_domain;
	attr_reader :site_base_dir, :doc_root, :static_root;
	attr_reader :slow_query_time, :error_logging;
	attr_reader :banner_servers, :memcache_servers, :pagecache_servers;
	attr_reader :contact_emails;
	attr_reader :debug_info_users;
	attr_reader :interac_info;
	attr_reader :age_min, :age_max;

	attr_reader :template_base_dir, :template_files_dir, :template_parse_dir,
	            :template_use_cached;
	# still needs a lot more added...

	@@configs = {};

	def ConfigBase.name_config(name)
		@@configs[name] = self; # self is the Class object here
	end

	def ConfigBase.get_class(name)
		return @@configs[name];
	end
end

Dir["config/*.rb"].each {|file|
	#require(file);
	mod_name = /config\/(.*)\.rb$/.match(file)[1];
	autoload("#{mod_name.capitalize}Config", file);
}

require("config/#{$config_name}.rb");

config_class = ConfigBase.get_class($config_name);
if (config_class.nil?)
	$debugout.puts("Could not load correct config file (#{$config_name})");
	exit();
end

$config = config_class.new();
