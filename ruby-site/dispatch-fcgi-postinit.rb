#!/bin/env ruby
require 'core/lib/fcgi-native-ext';

FCGI.listen($ipaddr, $port);

$pids = [];

# Pushes sigterms to child processes
def propagate(pids)
	pids.each {|pid|
		$log.info("Killing child process #{pid}");
		begin
			Process.kill("SIGTERM", pid);
		rescue Errno::ESRCH
			$log.info("Process #{pid} did not exist.");
		end
	}
end

# Creates a child process and returns its pid.
def create_proc()
	pid = fork {
		base_child_name = "nexopia-child"
		$0 = "#{base_child_name} initializing";

		if($config.monitor_files)
			FileChanges.init_monitoring();
		end

		require 'site_initialization';
		initialize_site();

		exit_next = false;
		handling_request = false;
		request_num = 0;

		trap("EXIT", "DEFAULT");

		trap("SIGTERM") {
			# close the listening socket
			IO.for_fd(0).close();
			if (handling_request)
				exit_next = true;
			else
				$site.shutdown();
			end
		}

		$0 = "#{base_child_name} [#{request_num}] waiting";

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

				if(($config.monitor_files && FileChanges.changes?) || exit_next)
					$0 = "#{base_child_name} [#{request_num}] quitting";
					throw :end_fcgi;
				end
			}
		}
		$site.shutdown();
	}
	return pid;
end


$num_children.times {
	$pids.push(create_proc());
}

# if kill is true, this is a permanent shutdown. Otherwise, we want the children
# killed.
def shutdown(kill = true)
	savepids = $pids;
	if (kill)
		$log.info("Pid #{Process.pid} received termination signal");
		$pids = []; # make it so that our child reloading handling below doesn't blow everything up
		if ($log.stderr_pid)
			savepids.push($log.stderr_pid)
		end
	else
		$log.info("Pid #{Process.pid} received reload signal");
	end

	propagate(savepids);
	if (kill)
		exit();
	end
end

trap("SIGTERM") { shutdown(true); }
trap("SIGINT")  { shutdown(true); }
trap("SIGHUP")  { shutdown(false); }

while (sleep(0.1))
	if ($pids.length < 1)
		break;
	end
	exitpid = Process.wait(-1, Process::WNOHANG);
	if (exitpid)
		if (pidindex = $pids.index(exitpid))
			$log.info("Pid #{exitpid} died, restarting");
			$pids[pidindex] = create_proc();
		end
	end
end
