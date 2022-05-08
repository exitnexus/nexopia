#!/usr/bin/env ruby
require "core/lib/ropt";
require "core/lib/daemonize";
require "core/lib/var_dump";
require "socket";
include Socket::Constants;
include Daemonize;

opt = ROpt.parse(ARGV, "hdc:i:p:n:", "once", "help") { true };
$config_name = opt[:c] || ENV['SITE_CONFIG_NAME'] || 'dev';
require "core/lib/config"; # loads the actual config file to use for options here

$ipaddr = opt[:i] || $config.ipaddr || 0;
$port = opt[:p] || $config.port || 1026;
$num_children = opt[:n] || $config.num_children || 5;
$num_children = $num_children.to_i;
$mode = (opt['once']? "dispatch-fcgi-once" : "dispatch-fcgi");

if (opt['h'] || opt['help'])
	puts("#{$0} [-h|--help] [-c <configname>] [-i <ipaddr>] [-p <port>] [-n <numchildren>] [--once]");
	exit;
end

if (!opt[:d].nil?)
	daemonize();
end

$code_root = Dir.getwd;

sock = Socket.new(AF_INET, SOCK_STREAM, 0);
sockaddr = Socket.pack_sockaddr_in( $port, $ipaddr )
sock.bind(sockaddr);
sock.listen(1024);
fd0 = IO.for_fd(0);
fd0.reopen(sock);

trap("EXIT") {
	fd0.close();
}

# now go into the correct version of the dispatch-fcgi script
load("#{$mode}.rb");
