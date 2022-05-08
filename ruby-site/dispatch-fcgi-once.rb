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
		$0 = base_child_name;

		handling_request = false;

		trap("EXIT", "DEFAULT");

		trap("SIGTERM") {
			# since this script always exits after one request, if we're in the
			# middle of one just ignore it. Otherwise, exit immediately.
			if (!handling_request)
				$site.shutdown();
			end
		}
		catch(:end_fcgi) {
			FCGI.each_cgi {|cgi|
				handling_request = true;
				$0 = "#{base_child_name} active";

				# we'll never accept another connection, so just shut down our copy of the listening socket now.
				IO.for_fd(0).close();

				$log.reassert_stderr();
				require 'site_initialization';
				initialize_site();
				PageRequest.new_from_cgi(cgi) {|pageRequest|
					PageHandler.execute(pageRequest);
				}

				$0 = "#{base_child_name} quitting";
				throw :end_fcgi; # leave the fcgi loop gracefuly.
			}
		}
		$site.shutdown();
	}
	return pid;
end


$num_children.times {
	$pids.push(create_proc());
}

def shutdown()
	$log.info("Pid #{Process.pid} received termination signal");
	savepids = $pids;
	$pids = []; # make it so that our child reloading handling below doesn't blow everything up
	if ($log.stderr_pid)
		savepids.push($log.stderr_pid)
	end
	propagate(savepids);
	exit();
end

trap("SIGTERM") { shutdown(); }
trap("SIGINT")  { shutdown(); }

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
