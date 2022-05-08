#!/bin/env ruby

file = File.new("/var/log/lighttpd/r.log", "w+");
$debugout = $stdout;

require 'fcgi';
require 'erb';
require "config";
require "pagehandler";
require "var_dump";
require "dispatch";

numChildren = 5;
pids = [];

# Pushes sigterms to child processes
def propagate(pids)
	pids.each {|pid|
		$debugout.print "Killing child process #{pid}\n";
		Process.kill("SIGTERM", pid);
	}
	exit();
end

# Creates a child process and returns its pid.
def create_proc(file)
	pid = fork {
		trap("SIGTERM", "DEFAULT"); # make sure we don't try to reap other child processes when we exit.
		FCGI.each_cgi {|$cgi|
			dispatch(file, $cgi);
		}
		exit();
	}
	return pid;
end

numChildren.times {
	pids.push(create_proc(file));
}

trap("SIGTERM") {
	savepids = pids;
	pids = []; # make it so that our child reloading handling below doesn't blow everything up
	propagate(savepids);
}

while (exitpid = Process.wait)
	if (pidindex = pids.index(exitpid))
		$debugout.print("Pid #{exitpid} died, restarting\n");
		pids[pidindex] = create_proc(file);
	end
end
