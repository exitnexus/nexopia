#!/bin/env ruby
require 'core/lib/fcgi-native-ext';

require "core/lib/filechangemonitor";
FileChanges.init_monitoring();
require 'site_initialization';
initialize_site(false, false);
SiteModuleBase.loaded(){|mod|
	SiteModuleBase.get(mod).load_all_rb;
}

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

$queue_worker_pid = nil;
$gearman_worker_pid = nil;
if (site_module_loaded?(:Worker))
	if (!$gearman_worker_pid)
		$gearman_worker_pid = WorkerModule.init_gearman();
	end
	if (!$queue_worker_pid)
		$queue_worker_pid = WorkerModule.init_ppq();
	end
end

# if kill is true, this is a permanent shutdown. Otherwise, we want the children
# killed.
def shutdown(kill = true)
	savepids = [];
	if (kill)
		$log.info("Pid #{Process.pid} received termination signal");
		if ($log.stderr_pid)
			savepids.push($log.stderr_pid)
		end
	else
		$log.info("Pid #{Process.pid} received reload signal");
	end

	if ($queue_worker_pid)
		savepids.push($queue_worker_pid);
		if (kill)
			$queue_worker_pid = nil; # prevent it from restarting
		end
	end
	if ($gearman_worker_pid)
		savepids.push($gearman_worker_pid);
		if (kill)
			$gearman_worker_pid = nil; # prevent it from restarting
		end
	end

	propagate(savepids);
	if (kill)
		exit();
	end
end

trap("SIGTERM") { shutdown(true); }
trap("SIGHUP") { shutdown(false); }

while (sleep(0.1))
	exitpid = Process.wait(-1, Process::WNOHANG);
	if (exitpid)
		if (exitpid == $queue_worker_pid)
			$log.info("Worker thread (#{exitpid}) died, restarting");
			$queue_worker_pid = WorkerModule.init_ppq();
		end
		if (exitpid == $gearman_worker_pid)
			$log.info("Worker thread (#{exitpid}) died, restarting");
			$gearman_worker_pid = WorkerModule.init_gearman();
		end
	end
end
