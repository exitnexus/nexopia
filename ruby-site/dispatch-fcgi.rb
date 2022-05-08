#!/bin/env ruby
require 'fcgi';
require 'erb';
require "core/lib/config";
require "core/lib/pagehandler";
require "core/lib/var_dump";
require "core/lib/dispatch";

pids = [];

# Pushes sigterms to child processes
def propagate(pids)
	pids.each {|pid|
		$stderr.print "Killing child process #{pid}\n";
		Process.kill("SIGTERM", pid);
	}
	exit();
end

# Creates a child process and returns its pid.
def create_proc()
	pid = fork {
		trap("EXIT", "DEFAULT"); # make sure we don't kill off the listening port when we exit.
		trap("SIGTERM", "DEFAULT"); # make sure we don't try to reap other child processes when we exit.
		FCGI.each_cgi {|$cgi|
			dispatch($cgi);
		}
		exit();
	}
	return pid;
end

$num_children.times {
	pids.push(create_proc());
}

trap("SIGTERM") {
	savepids = pids;
	pids = []; # make it so that our child reloading handling below doesn't blow everything up
	propagate(savepids);
}

while (exitpid = Process.wait)
	if (pidindex = pids.index(exitpid))
		$stderr.print("Pid #{exitpid} died, restarting\n");
		pids[pidindex] = create_proc();
	end
end
