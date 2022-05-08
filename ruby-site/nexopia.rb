#!/usr/bin/env ruby

# Force the cwd the directory nexopia.rb is in.
mypath = File.dirname(__FILE__);
Dir.chdir(mypath);

require "rubygems"
require "socket";
require "cgi";

require "optparse";
require "core/lib/errorlog";

include Socket::Constants;

$config_name = ENV['SITE_CONFIG_NAME'] || 'dev';
$mode = '-fcgi-reload';
redirect_stderr = true;
log_facilities = nil;

$ipaddr = nil;
$port = nil;
$num_children = nil;

OptionParser.new {|opts|
	opts.banner = "Usage: #{$0} [options]";

	opts.on("-c", "--config [NAME]", "Config file name to use (default: dev)") {|config|
		$config_name = config;
	}

	opts.on("-a", "--all-tests", "Run all test cases") {
		$mode = "-test";
		$tests = [];
		redirect_stderr = false;
		log_facilities = {:general => [:direct], :sql => []}
	}

	opts.on("-t", "--test x,y,z", Array, "Comma separated list of tests to run.") {|list|
		$mode = "-test";
		$tests = list;
		redirect_stderr = false;
		log_facilities = {:general => [:direct], :sql => []};
	}

	opts.on("-r", "--run x,y,z", Array, "Comma separated list of run-types to run.") {|list|
		$mode = '-run';
		$runs = list;
		redirect_stderr = false;
		log_facilities = {:general => [:direct], :sql => []};
	}

	opts.on("-F", "--fastcgi VERSION", [:reload, :preinit, :postinit, :once],
			"Run a fastcgi dispatcher:",
			"  reload   (initalize before fork, reloadable with -HUP) (default)",
			"  preinit  (initalize before fork)",
			"  postinit (initalize after fork, monitor files)",
			"  once     (initalize for each request)") {|version|
		$mode = "-fcgi-#{version}";
	}
	opts.on("-T", "--thread [VERSION]", "Run the fastcgi-thread[VERSION] dispatcher") {|version|
		$mode = "-fcgi-thread" + (version == '1') ? "" : version;
	}
	opts.on("-m", "--mongrel", "Run the mongrel-based dispatcher") {
		$mode = '-mongrel';
	}
	opts.on("-u", "--uri [URI]", "Run only the single page pointed to by URI and output it to stdout.") {|uri|
		$mode = '-page';
		$page = "Internal/webrequest/#{uri}";
		redirect_stderr = false;
		#log_facilities = {:general => [:direct]};
	}
	opts.on("-h", "--handler [handler]", "Run only the handler pointed to by the path handler and output it to stdout.", 
	                                     "  Differs from uri in that it doesn't go through the webrequest handler.") {|handler|
		$mode = '-page';
		$page = handler;
		redirect_stderr = false;
		#log_facilities = {:general => [:direct]};
	}

	opts.on("-q", "--post-process-queue", "Run only the post processing queue.") {|handler|
		$mode = '-ppq';
		redirect_stderr = false;
	}

	opts.on("-f", "--no-fork", "Replace fork with a simple yield (disable forking)") {
		module Kernel
			def fork()
				yield;
			end
		end
		redirect_stderr = false;
	}

	opts.on("-i IP", "Address to bind the server to (may be hostname or IP)") {|i|
		$ipaddr = i;
	}
	opts.on("-p PORT", Integer, "Port number to bind to") {|p|
		$port = p;
	}
	opts.on("-n NUM", Integer, "Number of child processes/threads to spawn (depending on dispatcher)") {|n|
		$num_children = n;
	}

	opts.on_tail("-h", "--help", "Show this message") {
		puts(opts);
		exit();
	}
}.parse!();
ARGV.clear();

puts("Chose config #{$config_name}");

load "core/lib/config.rb"; # loads the actual config file to use for options here
$config = ConfigBase.load_config($config_name); # this copy of the config is just for bootstrapping.

$ipaddr = $ipaddr || $config.ipaddr || 0;
$port = $port || $config.port || 1026;
$num_children = $num_children || $config.num_children || 5;

#
# Include the global Logging Facility
#
require 'nexopia-log'

setup_global_logging_facility($config, log_facilities, redirect_stderr)
$log.info("Logging Started", :info);

require "core/lib/filechangemonitor";

$code_root = Dir.getwd;
$log.info("Starting in mode: #{$mode}", :info);

# now go into the correct version of the dispatch-fcgi script
load("dispatch#{$mode}.rb");
