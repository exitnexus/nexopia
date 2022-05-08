class DevConfig < ConfigBase
	name_config "dev";

	def initialize()
		super();
		@site_name = "DevNexus";
		@base_domain = 'dev';
		@svn_base_dir = '/home/nexopia';
		@template_use_cached = false;
		@ipaddr = '127.0.0.1';
		@port = 1126;
		@num_children = 5;
		@monitor_files = false;
		@log_facilities = {
			:general => [:stderr, :logfile, :request_buffer_timeline],
			:sql => [:request_buffer, :request_buffer_timeline],
			:pagehandler => [:logfile, :request_buffer_timeline],
			:template => [:logfile],
			:site_module => [],
			:admin => [:request_buffer_admin],
		};
		@log_minlevel = :error;
		@log_minlevel_admin = :critical

		@max_requests = nil;

		@memcache_options = [[ '192.168.10.50',11211 ]];
		@mogilefs_hosts = ['mogilefs:6001']
		@mogilefs_domain = 'nexopia.com'
		@mogilefs_options = {:mkcols_required => true};

		@legacy_userpic_table = "legacypics"
		@gearman_servers = ["dev:7003"];
		
		@search_server = "dev:8872";

		@long_session_timeout = 60 * 60 * 24 * 30;

		@dumpstruct_include_dbname = true;

		@modules_exclude = [:Php, :Rap, :LegacyUserpics, :LegacyGallery];
		@modules_include = nil; #[:Core, :Devutils, :Debug, :Skeleton, :Music];

		# these are the ones that are done through a programmatic interface.
		# Just setting the instance variable will explicitly override that, though
		@www_url =
		@admin_url =
		@admin_self_url = 
		@user_url =
		@user_file_url =
		@email_domain =
		@cookie_domain =
		@banner_domain =
		@legacy_domain =
		@legacy_static_domain =
		@doc_root =
		@static_root =
		@template_base_dir =
		@template_files_dir =
		@template_parse_dir = nil;
		@user_dump_cache = "/tmp"

		@adblaster_server = "192.168.0.248";
		@adblaster_web_interface = 8971;
		@banner_servers = ['dev']
		@banner_server_port = 8435

		@mail_server = "dev.office.nexopia.com"
		@mail_port = 25

		#set to true to always use :refresh in storable finds.
		@storable_force_nomemcache = false;

		@page_skeleton = :NullSkeleton;

		@queue_identifier = $config_name
		@requeue_on_error = false;  #SHOULD BE FALSE WHEN LIVE!

		@live = false;

		$mysql_inline_c = true;
		# much more needs to be set here.

		@min_username_length = 4;
		@max_username_length = 15;

		@gallery_thumb_image_size = "100x150"
		@gallery_full_image_size = "2560x1600>"
		@gallery_image_size = "640x640>"

		@colorize_log_output = true
		@colors = {
			:info => "green",
			:general => "bold yellow",
			:error => "red",
			:warning => "yellow",
			:critical => "yellow on_red",
			:sql => "blue",
			:memcache => "blue", # same as mysql
			:pagehandler => "magenta",
			:template => "cyan",
			:site_module => "yellow",
			:time => "underline"
		}

		@recaptcha_keys = [
			'6Lev0QAAAAAAAEPyQjtezywyt_A2K1OtRlZaxPLe',
			'6Lev0QAAAAAAAAbOIS06JQI43PBJlEIUquASIZQC'
						  ]
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
		@upload_url || ["upload.#{[*www_url][0]}"];
	end
	def static_url
		@static_url || [*www_url] + ['static']
	end
	def style_url
		@style_url || [*www_url] + ['style']
	end
	def script_url
		@script_url || [*www_url] + ['script']
	end
	def image_url
		@user_domain || [*www_url] + ['images'];
	end
	def user_files_url
		@user_domain || [*www_url] + ['userfiles'];
	end
	def banner_url
		@banner_url || [*static_url] + ['banners']
	end
	def contacts_url
		@contacts_url || "http://#{[*www_url].join('/')}/addressbookimporter.php"
	end

	def legacy_domain
		@legacy_domain || "ruby.#{base_domain}"
	end
	def legacy_static_domain
		@static_domain || "static.#{base_domain}";
	end

	def site_base_dir
		@site_base_dir || "#{svn_base_dir}/ruby-site";
	end
	def legacy_base_dir
		@legacy_base_dir || "#{svn_base_dir}/php-site";
	end
	def doc_base_dir
		@doc_base_dir || "#{svn_base_dir}/ruby-doc";
	end
	def test_base_dir
		@test_base_dir || "#{svn_base_dir}/ruby-test";
	end
	def static_root
		@static_root || "#{svn_base_dir}/public_static";
	end

	def source_pic_dir
		@source_pic_dir || "#{legacy_base_dir}/public_static/user_data/source"
	end
	def gallery_dir
		@gallery_dir || "#{legacy_base_dir}/public_static/user_data/gallery"
	end
	def gallery_full_dir
		@gallery_full_dir || "#{legacy_base_dir}/public_static/user_data/galleryfull"
	end
	def gallery_thumb_dir
		@gallery_thumb_dir || "#{legacy_base_dir}/public_static/user_data/gallerythumb"
	end
	def banners_dir
		@banners_dir || "#{legacy_base_dir}/public_static/user_data/banners"
	end
	def user_pic_dir
		@user_pic_dir || "#{legacy_base_dir}/public_static/user_data/userpics"
	end
	def user_pic_thumb_dir
		@user_pic_thumb_dir || "#{legacy_base_dir}/public_static/user_data/userpicsthumb"
	end
	def uploads_dir
		@pending_dir || "#{legacy_base_dir}/public_static/user_files/uploads"
	end
	def pending_dir
		@pending_dir || "#{legacy_base_dir}/public_static/user_data/pending"
	end
	def resume_dir
		@resume_dir || "#{legacy_base_dir}/public_static/user_data/resumes"
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
	def static_php_domain
		return "static.#{@base_php_domain}";
	end

	def gearman_protocol_id
		@gearman_protocol_id || (queue_identifier && "#{queue_identifier}:")
	end

	# database configuration

	database(:dbserv50) {|conf|
		#conf.type = SqlDBdbi;
		conf.options = {
			:host => 'mysql',
			:login => 'root',
			:passwd => 'root',
			:debug_level => 2,
		};
	}

	database(:test50) {|conf|
		conf.live = true;
		conf.options = {
			:db => 'test',
		};
		conf.inherit = :dbserv50;
	}

	database(:dbserv41) {|conf|
		conf.options = {
			:host => '192.168.10.50',
			:login => 'root',
			:passwd => 'Hawaii',
			:debug_level => 2
		};
	}

	database(:test41) {|conf|
		conf.live = true;
		conf.options = {
			:db => 'test',
		};
		conf.inherit = :dbserv41;
	}

	database(:devdb) {|conf|
		conf.inherit = :dbserv50;
	}

	database(:processqueue) {|conf|
		conf.live = true;
		conf.options = {
			:db => 'processqueue',
		};
		conf.inherit = :dbserv50;
	}

	database(:devdbmulti) {|conf|
		conf.type = SqlDBMirror;
		conf.children = {
			:insert => database {|subconf| subconf.inherit = :devdb },
			:select => [
				database {|subconf| subconf.inherit = :devdb }
			]
		};
	}

	database(:generatedtestdb) {|conf|
		conf.live = true
		conf.inherit = :devdb
		conf.options = {:db => 'generatedtest'}
	}

	database(:masterdb) {|conf|
		conf.live = true;
		conf.inherit = :devdbmulti;
		conf.options = {:db => 'newmaster'};
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
			1 => database {|subconf|
				subconf.live = true;
				subconf.inherit = :devdbmulti;
				subconf.options = {:db => 'newusers', :seqtable => 'usercounter'};
			},
			2 => database {|subconf|
				subconf.live = true;
				subconf.inherit = :devdbmulti;
				subconf.options = {:db => 'newusers1', :seqtable => 'usercounter'};
			},
		};
		conf.options = {
			:seqtable => 'usercounter',
			:id_func => :account
		};
	}

	database(:forumdb) {|conf|
		conf.live = true;
		conf.type = SqlDBStripe;
		conf.children = {
#			0 => database {|subconf|
#				subconf.live = true;
#				subconf.inherit = :devdbmulti;
#				subconf.options = {:db => 'newusersanon', :seqtable => 'forumcounter'};
#			},
			3 => database {|subconf|
				subconf.live = true;
				subconf.inherit = :devdbmulti;
				subconf.options = {:db => 'newforum', :seqtable => 'forumcounter'};
			}
		};
		conf.options = {
			:seqtable => 'forumcounter',
			:id_func => :account
		};
	}

	database(:rolesdb) {|conf|
		conf.live = true;
		conf.inherit = :devdb;
		conf.options = {:db => 'newmods'};
	}

=begin
	database(:rolesdb) {|conf|
		conf.live = true;
		conf.type = SqlDBStripe;
		conf.children = {
			4 => database {|subconf|
				subconf.live = true;
				subconf.inherit = :devdb;
				subconf.options = {:db => 'newmods', :seqtable => 'rolecounter'};
			}
		};
		conf.options = {
			:seqtable => 'rolecounter',
			:id_func => :account
		};
	}
=end

	database(:forummasterdb) {|conf|
		conf.live = true;
		conf.inherit = :devdb;
		conf.options = {:db => 'newforummaster'};
	}

	database(:configdb) {|conf|
		conf.live = true;
		conf.inherit = :devdb;
		conf.options = {:db => 'newconfig'};
	}

	database(:db) {|conf|
		conf.live = true;
		conf.inherit = :devdbmulti;
		conf.options = {:db => 'newgeneral'};
	}

	database(:streamsdb) {|conf|
		conf.live = true;
		conf.inherit = :devdb;
		conf.options = {:db => 'newstreams'};
	}

	database(:moddb) {|conf|
		conf.live = true;
		conf.inherit = :devdb;
		conf.options = {:db => 'newmods'};
	}

	database(:polldb) {|conf|
		conf.live = true;
		conf.inherit = :devdb;
		conf.options = {:db => 'newpolls'};
	}
	database(:shopdb) {|conf|
		conf.live = true;
		conf.inherit = :devdb;
		conf.options = {:db => 'newshop'};
	}
	database(:filesdb) {|conf|
		conf.live = true;
		conf.inherit = :devdb;
		conf.options = {:db => 'newfileupdates'};
	}
	database(:bannerdb) {|conf|
		conf.live = true;
		conf.inherit = :devdb;
		conf.options = {:db => 'newbanners'};
	}
	database(:contestdb) {|conf|
		conf.live = true;
		conf.inherit = :devdb;
		conf.options = {:db => 'newcontest'};
	}
	database(:articlesdb) {|conf|
		conf.live = true;
		conf.inherit = :devdb;
		conf.options = {:db => 'newarticles'};
	}
	database(:wikidb) {|conf|
		conf.live = true;
		conf.inherit = :devdb;
		conf.options = {:db => 'newwiki'};
	}
	database(:picmodexamdb) {|conf|
		conf.live = true;
		conf.inherit = :devdb;
		conf.options = {:db => 'nexopiapicmodexam'};
	}
	database(:taskdb) {|conf|
		conf.live = true;
		conf.inherit = :devdb;
		conf.options = {:db => 'newtasks'};
	}

	database(:videodb) {|conf|
	  conf.live = true;
	  conf.inherit = :devdb;
	  conf.options = {:db => 'newvideo'};
	}

	database(:old_taskdb) {|conf|
		conf.live = true;
		conf.inherit = :dbserv41;
		conf.options = {:db => 'newtasks'};
	}

	database(:rubytest) {|conf|
		conf.live = true;
		conf.inherit = :devdb;
		conf.options = {:db => 'rubytest'};
	}

	database(:testbanner) {|conf|
		conf.live = true;
		conf.inherit = :dbserv50;
		conf.options = {:db => 'testbanner'};
	}

	database(:jobsdb) {|conf|
		conf.live = true;
		conf.inherit = :devdb;
		conf.options = {:db => 'newjobs'};
	}

	database(:eventsdb) {|conf|
		conf.live = true;
		conf.inherit = :devdb;
		conf.options = {:db => 'newevents'};
	}

	database(:devtaskdb) {|conf|
		conf.live = true;
		conf.inherit = :devdb;
		conf.options = {:db => 'newdevtasks'};
	}

	database(:groupsdb) {|conf|
		conf.live = true;
		conf.inherit = :devdb;
		conf.options = {:db => 'newgroups'};
	}
end
