#!/bin/env ruby
require 'core/lib/fcgi-native-ext';
require 'core/lib/dispatcher';
require 'core/lib/memusage'

#do signal handler this way since ruby seems to forget to re-assign it in some cases
$term_handler = Proc.new {|kill| Process.kill("SIGTERM", $log.stderr_pid); };
trap("EXIT")    { $term_handler.call(true); }
trap("SIGTERM") { $term_handler.call(true); }
trap("SIGINT")  { $term_handler.call(true); }
trap("SIGHUP")  { $term_handler.call(false); }

FCGI.listen($ipaddr, $port);

# Creates a child process and returns its pid.
def spawn_child()
	pid = fork {
		base_child_name = "nexopia-child"
		$0 = base_child_name;

		# enable the debug code to log all object creations. This is great for profiling object creation
		# and reducing garbage collection. It requires a custom build of ruby to use.
		#ObjectSpace.enable_new_dump()
		
		# debug code to checkpoint objects. This is great for finding memory leaks.
		# It requries a custom build of ruby to use.
		if(ObjectSpace.respond_to?(:set_snapshot_name))
			lib_require :Core, "symbol"
			trap("SIGUSR2") { 
				ObjectSpace.dump_objs("/tmp/snapshot-#{Process.pid}.csv")
				Symbol.dump_symbols("/tmp/symbols-#{Process.pid}.csv")
				if (ObjectSpace.respond_to?(:snapshot_hash_size))
					File.open("/tmp/snapshot-mem-#{Process.pid}.csv", 'w') { |fp|
						fp.write("#{ObjectSpace.snapshot_hash_size}\n")
					}
				end
			}
		end

		exit_next = 0;
		handling_request = false;
		request_num = 0;

		$term_handler = Proc.new {|kill|
			$log.info("Child #{Process.pid} received termination signal", :debug);

			# close the listening socket
			begin
				IO.for_fd(0).close();
			rescue
			end

			if (handling_request && exit_next < 2) #die on the third kill request
				exit_next += 1;
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

				# Set a new object checkpoint, used to find memory leaks.
				# It requries a custom build of ruby to use.
				if ObjectSpace.respond_to?(:set_snapshot_name)
					ObjectSpace.set_snapshot_name("page")
				end

				begin
					use_before = MemUsage.total
					PageRequest.new_from_cgi(cgi) {|pageRequest|
						PageHandler.execute(pageRequest);
					}
				rescue
					$log.error
					raise
				ensure
					use_after = MemUsage.total
					if (request_num > 1 && use_after > (use_before + 20 * 1024 * 1024))
						$log.info("Memory usage grew more than 20MB times during the current request (#{use_before} -> #{use_after}) URL: http://#{cgi.env_table['HTTP_HOST']}#{cgi.env_table['REQUEST_URI']}", :warning, :dispatcher)
					end
				end
				
				handling_request = false;
				$0 = "#{base_child_name} [#{request_num}] waiting";

				#Handling for if the PageRequest stack is not empty. Not the best fix to just clear the stack upon new request, but it's better than nothing.
				if(!PageRequest.stack.empty?())
					$log.info "Skipped ensure blocks detected. Dumping PageRequest stack and going down", :critical;
					$log.info "PageRequest stack not empty, has #{PageRequest.stack.length} items", :critical;
					
					if(PageRequest && PageRequest.current && PageRequest.current.session && PageRequest.current.session.user)
						if(PageRequest.current.session.user.anonymous?())
							$log.info "Current request was made by anonymous user", :critical;
						else
							$log.info "Current request was made by user(#{PageRequest.current.session.user.userid}) -> #{PageRequest.current.session.user.username}", :critical;
						end
					end
					
					PageRequest.stack.each{|old_req, i|
						$log.info "In position #{i} old request to #{old_req.uri}", :critical
					};

					#With a skipped ensure, we can't trust the interpreters state at this point, so exit at the end of the request.
					exit_next = 1
				end

				if($site.config.max_requests && $site.config.max_requests <= request_num)
					$log.info "Hit max_requests after #{request_num} requests, going down.", :critical
					exit_next = 1
				end

				if(exit_next != 0)
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

	Dispatcher.kill_children($pids, "child");

	if(kill)
		exit;
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
		initialize_site(true, true);

		GC.start;

		$0 = "nexopia-parent - spawning #{$num_children} children";

	#tell the master process to kill the other parent, if one exists
		Process.kill("SIGUSR1", $master_pid);

		$num_children.times {
			$pids.push(spawn_child());
			sleep(0.1); #spawn a max of 10 per second (to give the old processes time to shut down and free memory)
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

	Dispatcher.kill_children(pids, "parent");
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

		Dispatcher.kill_children([pid], "old parent");
	end
}

#promote the old parent (useful if the new parent is spin-dying)
trap("SIGUSR2"){
	if($old_parent_pid)
		pid = $parent_pid;
		$parent_pid = $old_parent_pid;

		Dispatcher.kill_children([pid], "new parent");
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

