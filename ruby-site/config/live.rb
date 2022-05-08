class LiveConfig < ConfigBase
	name_config "live";

	def initialize()
		super();
		@site_name = "Nexopia.com"
		@base_domain = 'nexopia.com';
		@svn_base_dir = '/home/nexopia';
		@ipaddr = '0.0.0.0';
		@port = 1027;
		@num_children = 8;
		@log_facilities = {
			:general     => [:syslog, :request_buffer_timeline],
			:sql         => [:request_buffer_timeline],
			:pagehandler => [:request_buffer_timeline],
			:memcache    => [:request_buffer_timeline],
			:template    => [],
			:site_module => [],
			:admin       => [:request_buffer_admin],
			:worker      => [:syslog]
		};
		@log_minlevel = :warning;
		@log_minlevel_admin = :critical
		@log_minlevel_cron = :info

		@debug_info_users = [1,21,997372,1745917,2309088,3233577,3495055]

		@payment_pin_merchant = 0; # removed
		@payment_pin_validate_phone = nil; # removed
		@payment_pin_validate_mobile = nil; # removed

		@zlib_compression_level = 9;

		@guest_buckets = 30;

		@max_requests = 100;
		@monitor_files = false;

		@debug_sql_log = false;
		@memcache_options = [
			'memcached1:11212',
			'memcached2:11212',
			'memcached3:11212',
			'memcached4:11212',
			'memcached5:11212',
			'memcached6:11212',
			'memcached7:11212',
			'memcached8:11212'
		];
		@session_active_timeout = 10 * 60; # 10 minutes
		@session_timeout = 60 * 60; # 1 hour
		@long_session_timeout = 60 * 60 * 24 * 30; # 30 days
		@gearman_servers = [
			'gearman1:7003',
			'gearman2:7003'
		]
		@num_workers = 2
		@num_gearman = 1

		@dumpstruct_include_dbname = true;

		@legacy_userpic_table = "pics"

		@modules_exclude = [
			:Migrate
			];
		@modules_include = [
			:Account,
			:Adminutils,
			:Akamai,
			:Archive,
			:Autocomplete,
			:Banner,
			:Bbcode,
			:Blogs,
			:Christmas,
			:Comments,
			:Core,
			:Debug,
			:EnhancedTextInput,
			:FileServing,
			:FriendFinder,
			:Friends,
			:Gallery,
			:Interstitial,
			:Legacy,
			:Messages,
			:Metrics,
			:Moderator,
			:Mods,
			:Nexoskel,
			:NullSkeleton,
			:Orwell,
			:Paginator,
			:Panels,
			:PhpSkeleton,
			:Plus,
			:Polls,
			:Profile,
			:Promotions,
			:Rap,
			:Scoop,
			:Search,
			:Smilies,
			:Store,
			:Streams,
			:ThemedSelect,
			:Truncator,
			:Uploader,
			:UserDump,
			:UserFiles,
			:Userpics,
			:Vote,
			:Video,
			:Wiki,
			:Worker,
			:Yui,
			];

		# these are the ones that are done through a programmatic interface.
		# Just setting the instance variable will explicitly override that, though
		@www_url =
		@admin_url =
		@user_url =
		@user_file_url =
		@email_domain =
		@cookie_domain =
		@banner_domain =
		@legacy_domain =
		@legacy_static_domain =
		@doc_root =
		@template_base_dir =
		@template_files_dir =
		@template_parse_dir = nil;

		@site_base_dir = ENV["NEXOPIA_RUBY_BASE"] || raise("Must set NEXOPIA_RUBY_BASE")
		@legacy_base_dir = ENV["NEXOPIA_PHP_BASE"] || raise("Must set NEXOPIA_PHP_BASE")

		@adblaster_server = "192.168.0.248";
		@adblaster_web_interface = 8971;

		@mogilefs_options = {:mkcol_required => false};
		@mogilefs_configs = {
			:default => {
				:hosts => [
					'mogilefs1:6001',
					'mogilefs2:6001',
					'mogilefs3:6001',
					'mogilefs4:6001',
					'mogilefs5:6001',
					'mogilefs6:6001',
				],
				:domain => 'nexopia.com',
			},
			:source => {
				:hosts => [
					'slogilefs1:6001',
					'slogilefs2:6001',
					'slogilefs3:6001',
					'slogilefs4:6001',
				],
				:domain => 'nexopia.com',
			},
			:legacy => nil,
		}

		@mail_server = "127.0.0.1"
		@mail_port = 25

		@min_username_length = 4;
		@max_username_length = 15;

		@gallery_thumb_image_size = "100x150"
		@gallery_full_image_size = "2560x1600>"
		@gallery_image_size = "640x640>"
		@gallery_profile_image_size = "320x320>"

		@colorize_log_output = true

		@page_skeleton = :PhpSkeleton
		@live = true;
		@static_file_cache = ENV["NEXOPIA_RUBY_CACHE_STATIC"] || raise("Must set NEXOPIA_RUBY_CACHE_STATIC")
		@generated_base_dir = ENV["NEXOPIA_RUBY_CACHE_GENERATED"] || raise("Must set NEXOPIA_RUBY_CACHE_GENERATED")

		@worker_queue_name = "live"

		@rap_php_config = "live"
		@recaptcha_keys = [
			# removed
		]

		@write_userfiles_to_disk = false;

		@tynt_proxy_server = "tyntapp.tynt.com";
		@tynt_proxy_port = 80;

		@orwell_email_rate_limit = 500;

		@webmaster_email = "webmaster@nexopia.com"
		@join_ip_frequency_cap = 10
	end

	# The following config variables are generally based on the ones defined in
	# initialize, but configs deriving from this one can set them as instance
	# variables in their own initialize() to override them.
	def email_domain
		@email_domain || @base_domain;
	end
	def cookie_domain
		@cookie_domain || "#{[*www_url][0]}";
	end
	def www_url
		@www_url || ["www.#{base_domain}"];
	end
	def admin_url
		@admin_url || [*www_url] + ['admin']
	end
	def admin_self_url
		@admin_self_url || [*admin_url] + ['self']
	end
	def user_url
		@user_url || [*www_url] + ['users']
	end
	def self_url
		@self_url || [*www_url] + ['my']
	end
	def upload_url
		@upload_url || [*www_url] + ['upload'];
	end
	def static_url
		@static_url || ["static.#{base_domain}"]
	end
	def image_url
		@user_domain || ["images.#{base_domain}"];
	end
	def user_files_url
		@user_files_url || ["users.#{base_domain}"];
	end
	def banner_url
		@banner_url || [*static_url] + ['banners']
	end
	def legacy_domain
		@legacy_domain || "ruby.#{base_domain}"
	end
	def legacy_static_domain
		@static_domain || "static.#{base_domain}";
	end

	def site_base_dir
		@site_base_dir || "#{svn_base_dir}/ruby-live";
	end
	def legacy_base_dir
		@legacy_base_dir || "#{svn_base_dir}";
	end
	def doc_base_dir
		@doc_base_dir || "#{svn_base_dir}/ruby-doc";
	end
	def test_base_dir
		@test_base_dir || "#{svn_base_dir}/ruby-test";
	end

	def generated_base_dir
		@generated_base_dir || "#{site_base_dir}/generated";
	end
	def rubyinline_dir
		@rubyinline_dir || "#{generated_base_dir}/rubyinline";
	end

	def user_dump_cache
		@user_dump_cache || @static_file_cache
	end

	def template_base_dir
		@template_base_dir || "#{site_base_dir}/templates";
	end
	def template_files_dir
		@template_files_dir || "#{template_base_dir}/template_files";
	end
	def template_parse_dir
		@template_parse_dir || "#{template_base_dir}/compiled_files";
	end

	def gearman_protocol_id
		@gearman_protocol_id || (cluster_name && cluster_name.to_s)
	end

	# database configuration

	database(:dbserv) {|conf|
		conf.options = {
			:login => ENV['NEXOPIA_DBSERV_LOGIN'] || 'ruby-site',
			:passwd => ENV['NEXOPIA_DBSERV_PASSWORD'] || 'cuOteGba9',
			:debug_level => 2,
		};
	}

	database(:slaveserv) {|conf|
		conf.options = {
			:login => ENV['NEXOPIA_SLAVEDBSERV_LOGIN'] || 'ruby-site-ro',
			:passwd => ENV['NEXOPIA_SLAVEDBSERV_PASSWORD'] || 'ksdjU23Q3s',
			:debug_level => 2,
		};
	}

	database(:masterdb) {|conf|
		conf.live = true;
		conf.inherit = :dbserv;
		conf.options = {:db => 'master', :host => 'masterdb',};
	}

	database(:masterdbslave) {|conf|
		conf.live = true;
		conf.inherit = :slaveserv;
		conf.options = {:db => 'master', :host => 'masterdb-slave',};
	}

	database(:forumdb) {|conf|
		conf.live = true;
		conf.inherit = :dbserv;
		conf.options = {:db => 'forum', :host => 'forumdb',};
	}

	database(:forumdbslave) {|conf|
		conf.live = true;
		conf.inherit = :slaveserv;
		conf.options = {:db => 'forum', :host => 'forumdb-slave',};
	}

	database(:anondb) {|conf|
		conf.live = true;
		conf.inherit = :masterdb;
		conf.options = {:db => 'usersanon', :seqtable => 'usercounter'};
	}

	database(:anondbslave) {|conf|
		conf.live = true;
		conf.inherit = :masterdbslave;
		conf.options = {:db => 'usersanon', :seqtable => 'usercounter'};
	}

	database(:usersdb) {|conf|
		conf.live = true;
		conf.type = SqlDBStripe;
		conf.children = {
#			0 => database {|subconf|
#				subconf.live = true;
#				subconf.inherit = :devdbmulti;
#				subconf.options = {:db => 'newusersanon', :seqtable => 'usercounter'};
#			},
			1  => database {|sc| sc.options = {:host => 'userdb1',  :db => 'userdb1_1'};  sc.inherit = :dbserv; },
			2  => database {|sc| sc.options = {:host => 'userdb1',  :db => 'userdb1_2'};  sc.inherit = :dbserv; },
			3  => database {|sc| sc.options = {:host => 'userdb2',  :db => 'userdb2_1'};  sc.inherit = :dbserv; },
			4  => database {|sc| sc.options = {:host => 'userdb2',  :db => 'userdb2_2'};  sc.inherit = :dbserv; },
			5  => database {|sc| sc.options = {:host => 'userdb3',  :db => 'userdb3_1'};  sc.inherit = :dbserv; },
			6  => database {|sc| sc.options = {:host => 'userdb3',  :db => 'userdb3_2'};  sc.inherit = :dbserv; },
			7  => database {|sc| sc.options = {:host => 'userdb4',  :db => 'userdb4_1'};  sc.inherit = :dbserv; },
			8  => database {|sc| sc.options = {:host => 'userdb4',  :db => 'userdb4_2'};  sc.inherit = :dbserv; },
			9  => database {|sc| sc.options = {:host => 'userdb5',  :db => 'userdb5_1'};  sc.inherit = :dbserv; },
			10 => database {|sc| sc.options = {:host => 'userdb5',  :db => 'userdb5_2'};  sc.inherit = :dbserv; },
			11 => database {|sc| sc.options = {:host => 'userdb6',  :db => 'userdb6_1'};  sc.inherit = :dbserv; },
			12 => database {|sc| sc.options = {:host => 'userdb6',  :db => 'userdb6_2'};  sc.inherit = :dbserv; },
			13 => database {|sc| sc.options = {:host => 'userdb7',  :db => 'userdb7_1'};  sc.inherit = :dbserv; },
			14 => database {|sc| sc.options = {:host => 'userdb7',  :db => 'userdb7_2'};  sc.inherit = :dbserv; },
			15 => database {|sc| sc.options = {:host => 'userdb8',  :db => 'userdb8_1'};  sc.inherit = :dbserv; },
			16 => database {|sc| sc.options = {:host => 'userdb8',  :db => 'userdb8_2'};  sc.inherit = :dbserv; },
			17 => database {|sc| sc.options = {:host => 'userdb9',  :db => 'userdb9_1'};  sc.inherit = :dbserv; },
			18 => database {|sc| sc.options = {:host => 'userdb9',  :db => 'userdb9_2'};  sc.inherit = :dbserv; },
			19 => database {|sc| sc.options = {:host => 'userdb10', :db => 'userdb10_1'}; sc.inherit = :dbserv; },
			20 => database {|sc| sc.options = {:host => 'userdb10', :db => 'userdb10_2'}; sc.inherit = :dbserv; },
			21 => database {|sc| sc.options = {:host => 'userdb11', :db => 'userdb11_1'}; sc.inherit = :dbserv; },
			22 => database {|sc| sc.options = {:host => 'userdb11', :db => 'userdb11_2'}; sc.inherit = :dbserv; },
			23 => database {|sc| sc.options = {:host => 'userdb12', :db => 'userdb12_1'}; sc.inherit = :dbserv; },
			24 => database {|sc| sc.options = {:host => 'userdb12', :db => 'userdb12_2'}; sc.inherit = :dbserv; },
			25 => database {|sc| sc.options = {:host => 'userdb13', :db => 'userdb13_1'}; sc.inherit = :dbserv; },
			26 => database {|sc| sc.options = {:host => 'userdb13', :db => 'userdb13_2'}; sc.inherit = :dbserv; },
			27 => database {|sc| sc.options = {:host => 'userdb14', :db => 'userdb14_1'}; sc.inherit = :dbserv; },
			28 => database {|sc| sc.options = {:host => 'userdb14', :db => 'userdb14_2'}; sc.inherit = :dbserv; },
		};
		conf.options = {
			:seqtable => 'usercounter',
			:id_func => :account
		};
	}

	database(:usersdbslave) {|conf|
		conf.live = true;
		conf.type = SqlDBStripe;
		conf.children = {
			1  => database {|sc| sc.options = {:host => 'userdb1-slave',  :db => 'userdb1_1'};  sc.inherit = :slaveserv; },
			2  => database {|sc| sc.options = {:host => 'userdb1-slave',  :db => 'userdb1_2'};  sc.inherit = :slaveserv; },
			3  => database {|sc| sc.options = {:host => 'userdb2-slave',  :db => 'userdb2_1'};  sc.inherit = :slaveserv; },
			4  => database {|sc| sc.options = {:host => 'userdb2-slave',  :db => 'userdb2_2'};  sc.inherit = :slaveserv; },
			5  => database {|sc| sc.options = {:host => 'userdb3-slave',  :db => 'userdb3_1'};  sc.inherit = :slaveserv; },
			6  => database {|sc| sc.options = {:host => 'userdb3-slave',  :db => 'userdb3_2'};  sc.inherit = :slaveserv; },
			7  => database {|sc| sc.options = {:host => 'userdb4-slave',  :db => 'userdb4_1'};  sc.inherit = :slaveserv; },
			8  => database {|sc| sc.options = {:host => 'userdb4-slave',  :db => 'userdb4_2'};  sc.inherit = :slaveserv; },
			9  => database {|sc| sc.options = {:host => 'userdb5-slave',  :db => 'userdb5_1'};  sc.inherit = :slaveserv; },
			10 => database {|sc| sc.options = {:host => 'userdb5-slave',  :db => 'userdb5_2'};  sc.inherit = :slaveserv; },
			11 => database {|sc| sc.options = {:host => 'userdb6-slave',  :db => 'userdb6_1'};  sc.inherit = :slaveserv; },
			12 => database {|sc| sc.options = {:host => 'userdb6-slave',  :db => 'userdb6_2'};  sc.inherit = :slaveserv; },
			13 => database {|sc| sc.options = {:host => 'userdb7-slave',  :db => 'userdb7_1'};  sc.inherit = :slaveserv; },
			14 => database {|sc| sc.options = {:host => 'userdb7-slave',  :db => 'userdb7_2'};  sc.inherit = :slaveserv; },
			15 => database {|sc| sc.options = {:host => 'userdb8-slave',  :db => 'userdb8_1'};  sc.inherit = :slaveserv; },
			16 => database {|sc| sc.options = {:host => 'userdb8-slave',  :db => 'userdb8_2'};  sc.inherit = :slaveserv; },
			17 => database {|sc| sc.options = {:host => 'userdb9-slave',  :db => 'userdb9_1'};  sc.inherit = :slaveserv; },
			18 => database {|sc| sc.options = {:host => 'userdb9-slave',  :db => 'userdb9_2'};  sc.inherit = :slaveserv; },
			19 => database {|sc| sc.options = {:host => 'userdb10-slave', :db => 'userdb10_1'}; sc.inherit = :slaveserv; },
			20 => database {|sc| sc.options = {:host => 'userdb10-slave', :db => 'userdb10_2'}; sc.inherit = :slaveserv; },
			21 => database {|sc| sc.options = {:host => 'userdb11-slave', :db => 'userdb11_1'}; sc.inherit = :slaveserv; },
			22 => database {|sc| sc.options = {:host => 'userdb11-slave', :db => 'userdb11_2'}; sc.inherit = :slaveserv; },
			23 => database {|sc| sc.options = {:host => 'userdb12-slave', :db => 'userdb12_1'}; sc.inherit = :slaveserv; },
			24 => database {|sc| sc.options = {:host => 'userdb12-slave', :db => 'userdb12_2'}; sc.inherit = :slaveserv; },
			25 => database {|sc| sc.options = {:host => 'userdb13-slave', :db => 'userdb13_1'}; sc.inherit = :slaveserv; },
			26 => database {|sc| sc.options = {:host => 'userdb13-slave', :db => 'userdb13_2'}; sc.inherit = :slaveserv; },
			27 => database {|sc| sc.options = {:host => 'userdb14-slave', :db => 'userdb14_1'}; sc.inherit = :slaveserv; },
			28 => database {|sc| sc.options = {:host => 'userdb14-slave', :db => 'userdb14_2'}; sc.inherit = :slaveserv; },
		};
		conf.options = {
			:seqtable => 'usercounter',
			:id_func => :account
		};
	}

	database(:rolesdb) {|conf|
		conf.live = true;
		conf.inherit = :masterdb;
		conf.options = {:db => 'mods', :seqtable => 'rolecounter'};
	}

	database(:rolesdbslave) {|conf|
		conf.live = true;
		conf.inherit = :masterdbslave;
		conf.options = {:db => 'mods', :seqtable => 'rolecounter'};
	}

	database(:configdb) {|conf|
		conf.live = true;
		conf.inherit = :masterdb;
		conf.options = {:db => 'config'};
	}

	database(:configdbslave) {|conf|
		conf.live = true;
		conf.inherit = :masterdbslave;
		conf.options = {:db => 'config'};
	}

	database(:db) {|conf|
		conf.live = true;
		conf.inherit = :masterdb;
		conf.options = {:db => 'general'};
	}

	database(:dbslave) {|conf|
		conf.live = true;
		conf.inherit = :masterdbslave;
		conf.options = {:db => 'general'};
	}

	database(:streamsdb) {|conf|
		conf.live = true;
		conf.inherit = :masterdb;
		conf.options = {:db => 'streams'};
	}

	database(:streamsdbslave) {|conf|
		conf.live = true;
		conf.inherit = :masterdbslave;
		conf.options = {:db => 'streams'};
	}

	database(:moddb) {|conf|
		conf.live = true;
		conf.inherit = :masterdb;
		conf.options = {:db => 'mods'};
	}

	database(:moddbslave) {|conf|
		conf.live = true;
		conf.inherit = :masterdbslave;
		conf.options = {:db => 'mods'};
	}

	database(:polldb) {|conf|
		conf.live = true;
		conf.inherit = :masterdb;
		conf.options = {:db => 'polls'};
	}

	database(:polldbslave) {|conf|
		conf.live = true;
		conf.inherit = :masterdbslave;
		conf.options = {:db => 'polls'};
	}

	database(:shopdb) {|conf|
		conf.live = true;
		conf.inherit = :masterdb;
		conf.options = {:db => 'shop'};
	}

	database(:shopdbslave) {|conf|
		conf.live = true;
		conf.inherit = :masterdbslave;
		conf.options = {:db => 'shop'};
	}

	database(:filesdb) {|conf|
		conf.live = true;
		conf.inherit = :masterdb;
		conf.options = {:db => 'fileupdates'};
	}

	database(:filesdbslave) {|conf|
		conf.live = true;
		conf.inherit = :masterdbslave;
		conf.options = {:db => 'fileupdates'};
	}

	database(:bannerdb) {|conf|
		conf.live = true;
		conf.inherit = :masterdb;
		conf.options = {:db => 'banners'};
	}

	database(:bannerdbslave) {|conf|
		conf.live = true;
		conf.inherit = :masterdbslave;
		conf.options = {:db => 'banners'};
	}

	database(:contestdb) {|conf|
		conf.live = true;
		conf.inherit = :masterdb;
		conf.options = {:db => 'contest'};
	}

	database(:contestdbslave) {|conf|
		conf.live = true;
		conf.inherit = :masterdbslave;
		conf.options = {:db => 'contest'};
	}

	database(:articlesdb) {|conf|
		conf.live = true;
		conf.inherit = :masterdb;
		conf.options = {:db => 'articles'};
	}

	database(:articlesdbslave) {|conf|
		conf.live = true;
		conf.inherit = :masterdbslave;
		conf.options = {:db => 'articles'};
	}

	database(:wikidb) {|conf|
		conf.live = true;
		conf.inherit = :masterdb;
		conf.options = {:db => 'wiki'};
	}

	database(:wikidbslave) {|conf|
		conf.live = true;
		conf.inherit = :masterdbslave;
		conf.options = {:db => 'wiki'};
	}

	database(:processqueue) {|conf|
		conf.live = true;
		conf.inherit = :masterdb;
		conf.options = { :db => 'processqueue' };
	}

	database(:processqueueslave) {|conf|
		conf.live = true;
		conf.inherit = :masterdbslave;
		conf.options = { :db => 'processqueue' };
	}

	database(:videodb) {|conf|
		conf.live = true;
		conf.inherit = :masterdb;
		conf.options = { :db => 'video' };
	}

	database(:videodbslave) {|conf|
		conf.live = true;
		conf.inherit = :masterdbslave;
		conf.options = { :db => 'video' };
	}

	database(:groupsdb) {|conf|
		conf.live = true;
		conf.inherit = :masterdb;
		conf.options = {:db => 'groups'};
	}

	database(:groupsdbslave) {|conf|
		conf.live = true;
		conf.inherit = :masterdbslave;
		conf.options = {:db => 'groups'};
	}
end
