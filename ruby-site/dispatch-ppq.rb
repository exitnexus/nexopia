#!/bin/env ruby

require 'core/lib/dispatcher';

$term_handler = Proc.new {|kill| exit(); };
trap("EXIT")    { $term_handler.call(true); }
trap("SIGTERM") { $term_handler.call(true); }
trap("SIGINT")  { $term_handler.call(true); }
trap("SIGHUP")  { $term_handler.call(false); }

require 'site_initialization';
initialize_site(false, false);
SiteModuleBase.loaded(){|mod|
	SiteModuleBase.get(mod).load_all_rb;
}
$site.close_dbs(); #close all dbs before the fork


if (!site_module_loaded?(:Worker))
	$log.info("Worker module not loaded, going down", :critical)
	exit(1);
end

$num_workers = $site.config.num_workers || 1
$num_gearman = $site.config.num_gearman || 1

$worker_pids = []
$gearman_pids = []

# if kill is true, this is a permanent shutdown. Otherwise, we want the children killed and respawned.
$term_handler = Proc.new {|kill| 
	if(kill)
		$num_workers = 0
		$num_gearman = 0
	end

	Dispatcher.kill_children($worker_pids, 'queue worker');
	Dispatcher.kill_children($gearman_pids, 'gearman worker');

	if (kill)
		exit();
	end
};

$num_workers.times {
	$worker_pids.push(WorkerModule.spawn_ppq_worker());
}
$num_gearman.times {
	$gearman_pids.push(WorkerModule.spawn_gearman_worker());
}

while(sleep(0.1) && ($num_gearman > 0 || $num_workers > 0))
#monitor the children, respawning them if needed
	begin
		while(exitpid = Process.wait(-1, Process::WNOHANG))
			if($worker_pids.delete(exitpid))
				$log.info("Queue worker #{exitpid} died unexpectedly, restarting", :critical);
			elsif($gearman_pids.delete(exitpid))
				$log.info("Gearman worker #{exitpid} died unexpectedly, restarting", :critical);
			end
		end
	rescue SystemCallError
		$worker_pids.clear();
		$gearman_pids.clear();
	end

	while($worker_pids.length < $num_workers)
		$worker_pids.push(WorkerModule.spawn_ppq_worker());
	end
	while($gearman_pids.length < $num_gearman)
		$gearman_pids.push(WorkerModule.spawn_gearman_worker());
	end
end

