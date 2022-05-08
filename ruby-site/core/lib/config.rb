## this is config library. config files derive from ConfigBase.

class ConfigBase
	# site name
	attr_reader :site_name

	attr_reader :ipaddr, :port, :num_children;
	attr_reader :log_facilities, :log_minlevel;
	# number of requests before a subprocess should commit seppuku
	attr_reader :max_requests;

	#number of worker and gearman processes
	attr_reader :num_workers, :num_gearman;
	
	# rate limitations for orwell notification module.
	# This is specified in milliseconds, so to limit email notifications
	# to no more than 2 per second, set this to 500.
	# This can be overridden by setting the orwell-email-rate-limit key.
	attr_reader :orwell_email_rate_limit;
	
	# Merchant id used for paymentpin.com transactions.
	attr_reader :payment_pin_merchant;
	
	# URL for validating phone paymentpin charges.
	attr_reader :payment_pin_validate_phone;

	# URL for validating mobile paymentpin charges.
	attr_reader :payment_pin_validate_mobile;

	# monitor all the require files, restarting if they change
	attr_reader :monitor_files;

	# Modules to exclude if no modules_include is specified.
	attr_reader :modules_exclude;
	# Modules to include. If this is specified, only those modules will be loaded.
	# Core will always be loaded.
	attr_reader :modules_include;
	# Meta-modules allow you to choose between implementations of some major facility.
	# More than one of a meta-module can be loaded, but only one will have its
	# pagehandlers loaded. You can specify which one in this hash, which is formatted
	# as: @modules_meta = {:Skeleton => :Nexoskel}
	# If none is specified, the first one to be loaded that claims to be a default
	# will be used.
	attr_reader :modules_meta;
	
	# a list of userids that have special debug info viewing privileges.
	# set to true to make all users debug_info_users.
	attr_reader :debug_info_users;

	# these are just plain domains as they are not used directly to specify
	# pagehandler locations.
	attr_reader :base_domain, :email_domain, :cookie_domain, :banner_domain;
	# these are in the format of ["domain", "path", "components"]
	# which translates to http://domain/path/components
	# If you want these in proper url format, you should use $site.xxx_url instead
	# of going direct to the config. These are suitable to be used in a webrequest
	# pagehandler declaration.
	attr_reader :www_url, :admin_url, :user_url, :static_url, :image_url,
	            :user_files_url, :self_url, :admin_self_url, :style_url, :script_url

	#this sets the config file for the php to use. If it's nil, it won't be sent.
	attr_reader :rap_php_config;

	# this is for the php passthrough, so it can translate the domain the live
	# site sends to lighttpd into the www_domain. Other domains (if any) need to
	# be made to go to the ruby site directly.
	attr_reader :legacy_domain, :legacy_base_dir;

	# legacy static domain for the php site. Translated to that specified by the
	# static_url option above.
	attr_reader :legacy_static_domain;

	# location for static files to be cached locally (on a ramdisk) to be sent
	# to the client with x-lighttpd-send-file. If nil, won't use a local cache
	# dir and won't use x-lighttpd-send-file.
	attr_reader :static_file_cache
	
	attr_reader :user_dump_cache

	attr_reader :long_session_timeout;
	attr_reader :svn_base_dir, :site_base_dir, :doc_base_dir, :test_base_dir
	attr_reader :source_pic_dir, :user_pic_dir, :user_pic_dir, :resume_dir,
				:gallery_dir, :gallery_full_dir, :gallery_thumb_dir, :uploads_dir;
	attr_reader :generated_base_dir;
	attr_reader :rubyinline_dir;
	attr_reader :legacy_site;
	attr_reader :slow_query_time, :error_logging;
	attr_reader :banner_servers, :memcache_options, :pagecache_servers;
	attr_reader :search_server;
	attr_reader :contact_emails;
	attr_reader :debug_info_users;
	attr_reader :interac_info;
	attr_reader :age_min, :age_max;

	# Hash of :servername => {:hosts => ['ip1','ip2'], :domain => 'blah'}
	# Can be :servername => false to indicate the server doesn't exist.
	# Will fall back to :default if an unknown config name is given.
	attr_reader :mogilefs_configs;
	attr_reader :mogilefs_options;

	attr_reader :template_base_dir, :template_files_dir, :template_parse_dir,
	            :template_use_cached;

	attr_reader :adblaster_server, :adblaster_web_interface, :banner_servers, :banner_server_port;

	attr_reader :dumpstruct_include_dbname;

	attr_reader :mail_server, :mail_port
	attr_reader :override_email
	
	attr_reader :youtube_dev_id
	attr_reader :min_username_length, :max_username_length

	attr_reader :write_userfiles_to_disk;

	#set to true to always use :nomemcache in storable finds.
	attr_reader :storable_force_nomemcache
	
	attr_reader :session_timeout;
	attr_reader :session_active_timeout;
	
	attr_reader :page_skeleton
	attr_reader :live;
	attr_reader :gearman_servers;

	attr_reader :legacy_userpic_table;
	
	attr_reader :gallery_image_size, :gallery_profile_image_size, :gallery_thumb_image_size, :gallery_full_image_size

	attr_reader :colorize_log_output

	attr_reader :worker_queue_name
	
	# pair of public/private key information for recaptcha.
	attr_reader :recaptcha_keys
				
	attr_reader :tynt_proxy_server, :tynt_proxy_port;
				
	attr_reader :zlib_compression_level
	
	attr_reader :webmaster_email
	
	attr_reader :join_ip_frequency_cap
	
	attr_reader :guest_buckets;
	# returns the log minlevel for a particular facility as specified by configuration variables called
	# @log_minlevel_#{facility}
	def log_minlevel_for(facility)
		instance_variable_get(:"@log_minlevel_#{facility}") || log_minlevel
	end

	def colors(symbol)
		return @colors[symbol] if @colors[symbol]
		return ""
	end

	# still needs a lot more added...


	# for use in rubyinfo page. Passes key/value pairs of set config options out via a block
	def get_config_data
		# determine the config inheritance hierarchy
		config_name = []
		parent = self.class
		while (parent != ConfigBase)
			config_name.push(parent.config_name)
			parent = parent.superclass
		end
		
		yield "Config Name", config_name.join(" < ")
		
		options = ConfigBase.instance_methods - Object.instance_methods - ["get_config_data"]
		options.each {|key|
			if (self.class.instance_method(key).arity == 0)
				yield key, self.send(key)
			end
		}
	end

	@@configs = {};
	@@dbconfigs = {};

	def ConfigBase.name_config(name)
		@config_name = name;
		@@configs[name] = self; # self is the Class object here
		@@dbconfigs[name] = {};
	end
	def ConfigBase.config_name
		return @config_name;
	end

	def ConfigBase.get_class(name)
		config_require(name);
		return @@configs[name];
	end

	def ConfigBase.get_class_tree(obj)
		if (obj.class == String)
			return get_class_tree(get_class(obj));
		end

		if (obj != ConfigBase)
			return get_class_tree(obj.superclass) + [obj.config_name];
		else
			return [];
		end
	end

	# declare a database config by calling:
	#  database('name') {|conf|
	#    conf.type = ...;
	#    conf.options = ...;
	#    conf.inherit = ...;
	#  }
	def ConfigBase.database(dbname = nil, &block)
		if (dbname)
			@@dbconfigs[@config_name][dbname] = block;
		else
			return block;
		end
	end

	# yields a DatabaseConfig object for each live config set up on the
	# config in question.
	# For config entries with children, it will yield the children first
	# and replace the children array's config entries with whatever the yield
	# returns (presumably a database object).
	def ConfigBase.get_dbconfigs(configname)
		# First we need to build the inheritance hierarchy of the Config
		# we're basing on so we can walk the tree's @@dbconfigs.
		tree = get_class_tree(configname);

		dbconfigs = {};

		# Now go through the configs and build the dbconfigs up via class
		# inheritence
		tree.each {|config|
			@@dbconfigs[config].each {|dbname, block|
				if (!dbconfigs.include?(dbname))
					dbconfigs[dbname] = SqlBase::Config.new();
				end
				dbconfigs[dbname].blocks_run.push(block);
				dbconfigs[dbname].all_blocks_run.push(block);
				block.call(dbconfigs[dbname]);
			}
		}

		# Now go through and do dbconf.inherit inheritence and set up defaults
		# Note that this may cause the rerunning of dbconfig blocks several
		# times while it figures out the right way to do it. This is terribly
		# inefficient, but need only happen once.
		dbconfigs.each {|name, dbconfig|
			blocks_to_run = [];
			cur_dbconfig = dbconfig;
			rebuild = false;
			# rebuild dbconfig object for each time it inherits and find out
			# if that time requires another inheritence
			while (cur_dbconfig.inherit)
				rebuild = true;
				parent_dbconfig = dbconfigs[cur_dbconfig.inherit];
				blocks_to_run = parent_dbconfig.blocks_run + blocks_to_run;

				cur_dbconfig = SqlBase::Config.new();
				parent_dbconfig.blocks_run.each {|block|
					block.call(cur_dbconfig);
				}
			end
			if (rebuild)
				new_dbconfig = SqlBase::Config.new();
				new_dbconfig.blocks_run = dbconfig.blocks_run; # only the ones for that particular item, or it'll screw up further inheritence.
				new_dbconfig.all_blocks_run = blocks_to_run + dbconfig.blocks_run;
				new_dbconfig.all_blocks_run.each {|block|
					block.call(new_dbconfig);
				}
				dbconfigs[name] = new_dbconfig;
			end
		}

		dbs = {};

		dbconfigs.each {|name, dbconfig|
			if (dbconfig.live)
				# build child configs now
				idx = 0 # Increments first so these actually start at 1.
				dbconfig.build_children(dbconfigs) {|childconfig|
					idx += 1
					yield(name, idx, childconfig);
				}

				dbs[name] = yield(name, 0, dbconfig);
			end
		}
		return dbs;
	end

	def ConfigBase.load_config(config_name)
		config_class = ConfigBase.get_class(config_name.to_s);
		if (config_class.nil?)
			$stderr.puts("Could not load correct config file (#{config_name})");
			exit();
		end

		return config_class.new();
	end
end

def config_require(name)
	load("config/#{name}.rb");
end
