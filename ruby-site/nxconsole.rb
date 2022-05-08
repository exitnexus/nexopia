#
# The intention of this file is to try to simplify
# the process of unittesting on the command line
#
require "rubygems"
require "core/lib/errorlog"
require 'site_initialization'
#puts ENV['SITE_CONFIG_NAME']
$config_name = ENV['SITE_CONFIG_NAME'] || 'dev';
require "core/lib/config.rb"; # loads the actual config file to use for options here
$config = ConfigBase.load_config($config_name); # this copy of the config is just for bootstrapping.
initialize_site(false);
#
# Include the global Loggin Facility
#
require 'nexopia-log'

# (use $config)
setup_global_logging_facility()

if $0 == __FILE__

libs = " -r irb/completion "
libs << " -r nxconsole"
system 'irb #{libs}'
end