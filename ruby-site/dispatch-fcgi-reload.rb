#!/bin/env ruby
require 'core/lib/fcgi-native-ext';

FCGI.listen($ipaddr, $port);

#do signal handler this way since ruby seems to forget to re-assign it in some cases
$term_handler = Proc.new {|kill| true; };
trap("EXIT")    { $term_handler.call(true); }
trap("SIGTERM") { $term_handler.call(true); }
trap("SIGINT")  { $term_handler.call(true); }
trap("SIGHUP")  { $term_handler.call(false); }


# Creates a child process and returns its pid.
def spawn_child()
	pid = fork {
		base_child_name = "nexopia-child"
		$0 = base_child_name;

		exit_next = false;
		handling_request = false;
		request_num = 0;

		$term_handler = Proc.new {|kill|
			$log.info("Child #{Process.pid} received termination signal", :debug);

			# close the listening socket
			IO.for_fd(0).close();
			if (handling_request)
				exit_next = true;
			else
				$site.shutdown();
			end
		}
		catch(:end_fcgi) {
			FCGI.each_cgi {|cgi|
				request_num += 1;
				handling_request = true;
				$0 = "#{base_child_name} [#{request_num}] active";
				$log.reassert_stderr();
				PageRequest.new_from_cgi(cgi) {|pageRequest|
					PageHandler.execute(pageRequest);
				}
				handling_request = false;
				$0 = "#{base_child_name} [#{request_num}] waiting";

				if(exit_next)
					$0 = "#{base_child_name} [#{request_num}] quitting";
					throw :end_fcgi;
				end
			}
		}
		$site.shutdown();
	}
	return pid;
end



# if kill is true, this is a permanent shutdown. Otherwise, we just want the children killed.
def shutdown_parent(kill = true)
	if (kill)
		$log.info("Parent #{Process.pid} received termination signal");
		$num_children = 0;
	else
		$log.info("Parent #{Process.pid} received reload signal");
	end

	kill_children($pids, "child");

	if(kill)
		exit;
	end
end

# Kill all children in the pids list, forcefully if needed
def kill_children(pids, name = "child")
	#try 5 times, sleeping half a second between each, returning early if possible
	5.times {
		kill_pids(pids, name, "SIGTERM");

		catch_pids(pids, name);
		return if(pids.length == 0);

		sleep(0.5);

		catch_pids(pids, name);
		return if(pids.length == 0);
	}

	kill_pids(pids, name, "SIGKILL");
	sleep(0.5);
	catch_pids(pids, name);
end

#kill the processes
def kill_pids(pids, name = "child", sig = "SIGTERM")
	pids.each {|pid|
		if(pid)
			$log.info("Killing #{name} process #{pid} with #{sig}", :debug);
			begin
				Process.kill(sig, pid);
			rescue Errno::ESRCH
				$log.info("Process #{pid} did not exist.", :debug);
			end
		end
	}
end

#try to catch all the killed processes
def catch_pids(pids, name = "child")
	begin
		#iterate over duplicate so deleting from the array doesn't break, and the reference to pids stays valid
		itpids = pids.dup;
		itpids.each {|pid|
			if(exitpid = Process.wait(pid, Process::WNOHANG))
				pids.delete(exitpid);
				$log.info("#{name} process #{exitpid} killed successfully", :debug);
			end
		}
	rescue SystemCallError #no pids to wait on
		pids.clear();
	end
end






def spawn_parent()
	pid = fork {
		$term_handler = Proc.new {|kill| shutdown_parent(kill); };

		$0 = "nexopia-parent - initializing";
		$pids = [];

		if($config.monitor_files)
			FileChanges.init_monitoring();
		end

		require 'site_initialization';
		initialize_site();

		$0 = "nexopia-parent - spawning #{$num_children} children";

	#tell the master process to kill the other parent, if one exists
		Process.kill("SIGUSR1", $master_pid);

		$num_children.times {
			$pids.push(spawn_child());
		}

		$0 = "nexopia-parent - monitoring #{$num_children} children";

		while(sleep(0.1) && $num_children > 0)
		#monitor the children, respawning them if needed
			begin
				while(exitpid = Process.wait(-1, Process::WNOHANG))
					if($pids.delete(exitpid))
						$log.info("Child #{exitpid} died unexpectedly, restarting", :critical);
					end
				end
			rescue SystemCallError
				$pids.clear();
			end

			while($pids.length < $num_children)
				$pids.push(spawn_child());
			end

		#reload gracefully if files changed by telling master to spawn a new parent and kill this one
			if($config.monitor_files && FileChanges.changes?)
				$log.info("File changed, reload", :info)
				Process.kill("SIGHUP", $master_pid);
			end
		end
	}
	return pid;
end

def shutdown()
	pids = [$log.stderr_pid, $parent_pid, $old_parent_pid];

	$old_parent_pid = nil;
	$parent_pid = nil;

	kill_children(pids, "parent");
	exit();
end


$master_pid = Process.pid;
$old_parent_pid = nil;
$parent_pid = nil;

#kill the old parent
trap("SIGUSR1"){
	if($old_parent_pid)
		pid = $old_parent_pid;
		$old_parent_pid = nil;

		kill_children([pid], "old parent");
	end
}

#promote the old parent (useful if the new parent is spin-dying)
trap("SIGUSR2"){
	if($old_parent_pid)
		pid = $parent_pid;
		$parent_pid = $old_parent_pid;

		kill_children([pid], "new parent");
	end
}

$term_handler = Proc.new {|kill|
	if(kill)
		shutdown();
	else #SIGHUP
	#spawn a new parent
		if(!$old_parent_pid) #don't do it if one is already spawning
			$old_parent_pid = $parent_pid;
			$parent_pid = spawn_parent();
		end
	end
};


#spawn a parent
$parent_pid = spawn_parent();

#monitor the parents
while (sleep(0.1))
	exitpid = Process.wait(-1, Process::WNOHANG);

	if(exitpid == $parent_pid)
		$log.info("Parent pid #{exitpid} died, restarting", :critical);
		$parent_pid = spawn_parent();
	end
end

