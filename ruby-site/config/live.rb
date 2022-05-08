class LiveConfig < ConfigBase
	name_config "live";

	def initialize()
		super();
		@site_name = "Nexopia.com"
		@base_domain = 'nexopia.com';
		@svn_base_dir = '/home/nexopia';
		@ipaddr = '0.0.0.0';
		@port = 1027;
		@num_children = 5;
		@log_facilities = {
			:general     => [:syslog, :request_buffer_timeline],
			:sql         => [:syslog, :request_buffer_timeline],
			:pagehandler => [:syslog, :request_buffer_timeline],
			:template    => [],
			:site_module => [],
			:admin       => [:request_buffer_admin],
		};
		@log_minlevel = :error;
		@log_minlevel_admin = :critical

		@max_requests = nil;
		@monitor_files = false;

		@debug_sql_log = false;
		@memcache_options = [
			'10.0.7.1:11212',
			'10.0.7.2:11212',
			'10.0.7.3:11212',
			'10.0.7.4:11212',
			'10.0.7.5:11212',
			'10.0.7.6:11212',
			'10.0.7.7:11212',
			'10.0.7.8:11212'
		];
		@long_session_timeout = 60 * 60 * 24 * 30;
		@gearman_servers = ["dynamic10:7003", "dynamic30:7003", "dynamic60:7003"]

		@dumpstruct_include_dbname = true;

		@legacy_userpic_table = "pics"

		@modules_exclude = [];
		@modules_include = [
			:Accountcreate,
			:Adminutils,
			:Core,
			:Debug,
			:FriendFinder,
			:Friends,
			:Images,
			:Legacy,
			:LegacyGallery,
			:LegacyUserpics,
			:Messages,
			:Modqueue,
			:Nexoskel,
			:NullSkeleton,
			:Profile,
			:Search,
			:Streams,
			:Tynt,
			:Uploader,
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
		@static_root =
		@template_base_dir =
		@template_files_dir =
		@template_parse_dir = nil;

		@adblaster_server = "192.168.0.248";
		@adblaster_web_interface = 8971;

		@mogilefs_options = {:mkcol_required => false};
		@mogilefs_hosts = ['10.0.0.101:6001','10.0.0.102:6001','10.0.0.103:6001','10.0.0.104:6001']
		@mogilefs_domain = 'nexopia.com'

		@mail_server = "10.0.0.8"
		@mail_port = 25

		@queue_identifier = 'nexoqueue'

		@min_username_length = 4;
		@max_username_length = 15;

		@gallery_thumb_image_size = "100x150"
		@gallery_full_image_size = "2560x1600>"
		@gallery_image_size = "640x640>"

		@colorize_log_output = true

		@page_skeleton = :NullSkeleton
		@live = true;
		@static_file_cache = "/var/nexopia/ruby-site/cache"
		@user_dump_cache = @static_file_cache

		@queue_identifier = "live";
		
		@contacts_url = "http://10.0.0.17/addressbook/addressbookimporter.php"
		
		@recaptcha_keys = [
			# removed
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
		@upload_url || [*www_url] + ['upload'];
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
		@user_domain || ["images.#{base_domain}"];
	end
	def user_files_url
		@user_files_url || ["users.#{base_domain}"];
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
		@legacy_base_dir || "#{svn_base_dir}";
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

	def gearman_protocol_id
		@gearman_protocol_id || (queue_identifier && "#{queue_identifier}:")
	end

	# database configuration

	database(:dbserv) {|conf|
		conf.options = {
			:login => 'root',
			:passwd => 'pRlUvi$t',
			:debug_level => 2,
		};
	}

	database(:masterdb) {|conf|
		conf.live = true;
		conf.inherit = :dbserv;
		conf.options = {:db => 'newmaster', :host => '10.0.4.100',};
	}

	database(:userdbserv) {|conf|
		conf.options = {
			:login => 'ruby-site',
			:passwd => 'cuOteGba9',
			:debug_level => 2,
		};
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
			1  => database {|sc| sc.options = {:host => '10.0.5.1',  :db => 'userdb1_1'};  sc.inherit = :userdbserv; },
			2  => database {|sc| sc.options = {:host => '10.0.5.1',  :db => 'userdb1_2'};  sc.inherit = :userdbserv; },
			3  => database {|sc| sc.options = {:host => '10.0.5.2',  :db => 'userdb2_1'};  sc.inherit = :userdbserv; },
			4  => database {|sc| sc.options = {:host => '10.0.5.2',  :db => 'userdb2_2'};  sc.inherit = :userdbserv; },
			5  => database {|sc| sc.options = {:host => '10.0.5.3',  :db => 'userdb3_1'};  sc.inherit = :userdbserv; },
			6  => database {|sc| sc.options = {:host => '10.0.5.3',  :db => 'userdb3_2'};  sc.inherit = :userdbserv; },
			7  => database {|sc| sc.options = {:host => '10.0.5.4',  :db => 'userdb4_1'};  sc.inherit = :userdbserv; },
			8  => database {|sc| sc.options = {:host => '10.0.5.4',  :db => 'userdb4_2'};  sc.inherit = :userdbserv; },
			9  => database {|sc| sc.options = {:host => '10.0.5.5',  :db => 'userdb5_1'};  sc.inherit = :userdbserv; },
			10 => database {|sc| sc.options = {:host => '10.0.5.5',  :db => 'userdb5_2'}; sc.inherit = :userdbserv; },
			11 => database {|sc| sc.options = {:host => '10.0.5.6',  :db => 'userdb6_1'}; sc.inherit = :userdbserv; },
			12 => database {|sc| sc.options = {:host => '10.0.5.6',  :db => 'userdb6_2'}; sc.inherit = :userdbserv; },
			13 => database {|sc| sc.options = {:host => '10.0.5.7',  :db => 'userdb7_1'}; sc.inherit = :userdbserv; },
			14 => database {|sc| sc.options = {:host => '10.0.5.7',  :db => 'userdb7_2'}; sc.inherit = :userdbserv; },
			15 => database {|sc| sc.options = {:host => '10.0.5.8',  :db => 'userdb8_1'}; sc.inherit = :userdbserv; },
			16 => database {|sc| sc.options = {:host => '10.0.5.8',  :db => 'userdb8_2'}; sc.inherit = :userdbserv; },
			17 => database {|sc| sc.options = {:host => '10.0.5.9',  :db => 'userdb9_1'}; sc.inherit = :userdbserv; },
			18 => database {|sc| sc.options = {:host => '10.0.5.9',  :db => 'userdb9_2'}; sc.inherit = :userdbserv; },
			19 => database {|sc| sc.options = {:host => '10.0.5.10', :db => 'userdb10_1'}; sc.inherit = :userdbserv; },
			20 => database {|sc| sc.options = {:host => '10.0.5.10', :db => 'userdb10_2'}; sc.inherit = :userdbserv; },
			21 => database {|sc| sc.options = {:host => '10.0.5.11', :db => 'userdb11_1'}; sc.inherit = :userdbserv; },
			22 => database {|sc| sc.options = {:host => '10.0.5.11', :db => 'userdb11_2'}; sc.inherit = :userdbserv; },
			23 => database {|sc| sc.options = {:host => '10.0.5.12', :db => 'userdb12_1'}; sc.inherit = :userdbserv; },
			24 => database {|sc| sc.options = {:host => '10.0.5.12', :db => 'userdb12_2'}; sc.inherit = :userdbserv; },
			25 => database {|sc| sc.options = {:host => '10.0.5.13', :db => 'userdb13_1'}; sc.inherit = :userdbserv; },
			26 => database {|sc| sc.options = {:host => '10.0.5.13', :db => 'userdb13_2'}; sc.inherit = :userdbserv; },
			27 => database {|sc| sc.options = {:host => '10.0.5.14', :db => 'userdb14_1'}; sc.inherit = :userdbserv; },
			28 => database {|sc| sc.options = {:host => '10.0.5.14', :db => 'userdb14_2'}; sc.inherit = :userdbserv; },
		};
		conf.options = {
			:seqtable => 'usercounter',
			:id_func => :account
		};
	}

=begin
	database(:forumdb) {|conf|
		conf.live = true;
		conf.type = SqlDBStripe;
		conf.children = {
#			0 => database {|subconf|
#				subconf.live = true;
#				subconf.inherit = :devdbmulti;
#				subconf.options = {:db => 'newusersanon', :seqtable => 'forumcounter'};
#			},
			200 => database {|subconf|
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

	database(:forummasterdb) {|conf|
		conf.live = true;
		conf.inherit = :devdb;
		conf.options = {:db => 'newforummaster'};
	}
=end
	database(:rolesdb) {|conf|
		conf.live = true;
		conf.inherit = :dbserv;
		conf.options = {:db => 'newmods', :host => '10.0.4.100', :seqtable => 'rolecounter'};
	}

	database(:configdb) {|conf|
		conf.live = true;
		conf.inherit = :masterdb;
		conf.options = {:db => 'newconfig'};
	}

	database(:db) {|conf|
		conf.live = true;
		conf.inherit = :masterdb;
		conf.options = {:db => 'newgeneral'};
	}

	database(:streamsdb) {|conf|
		conf.live = true;
		conf.inherit = :masterdb;
		conf.options = {:db => 'newstreams'};
	}

	database(:moddb) {|conf|
		conf.live = true;
		conf.inherit = :masterdb;
		conf.options = {:db => 'newmods'};
	}

	database(:polldb) {|conf|
		conf.live = true;
		conf.inherit = :masterdb;
		conf.options = {:db => 'newpolls'};
	}
	database(:shopdb) {|conf|
		conf.live = true;
		conf.inherit = :masterdb;
		conf.options = {:db => 'newshop'};
	}
	database(:filesdb) {|conf|
		conf.live = true;
		conf.inherit = :masterdb;
		conf.options = {:db => 'newfileupdates'};
	}
	database(:bannerdb) {|conf|
		conf.live = true;
		conf.inherit = :masterdb;
		conf.options = {:db => 'newbanners'};
	}
	database(:contestdb) {|conf|
		conf.live = true;
		conf.inherit = :masterdb;
		conf.options = {:db => 'newcontest'};
	}
	database(:articlesdb) {|conf|
		conf.live = true;
		conf.inherit = :masterdb;
		conf.options = {:db => 'newarticles'};
	}
	database(:wikidb) {|conf|
		conf.live = true;
		conf.inherit = :masterdb;
		conf.options = {:db => 'newwiki'};
	}
	database(:processqueue) {|conf|
		conf.live = true;
		conf.inherit = :masterdb;
		conf.options = { :db => 'processqueue' };
	}
	database(:videodb) {|conf|
		conf.live = true;
		conf.inherit = :masterdb;
		conf.options = { :db => 'newvideo' };
	}

=begin
	database(:picmodexamdb) {|conf|
		conf.live = true;
		conf.inherit = :masterdb;
		conf.options = {:db => 'newpicmodexam'};
	}
=end
end
