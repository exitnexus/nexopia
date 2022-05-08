#!/bin/env ruby

# threaded fcgi dispatcher: spawn one thread per request

require 'core/lib/fcgi-native-ext';

FCGI.listen($ipaddr, $port);

require 'site_initialization';

$0 = "Nexopia Parent";
initialize_site();

$threads = [];

FCGI.each_cgi { |cgi|
	$log.reassert_stderr();

	#Wait until there are fewer than $num_children threads running
	while($threads.delete_if { |thread| !thread.alive? }.length >= $num_children)
		sleep(0.001); #wait a millisecond and try again
	end

	$threads << Thread.new(cgi) { |cgi|
		begin
			PageRequest.new_from_cgi(cgi) {|req|
				PageHandler.execute(req);
			}
		rescue
			$log.error
			raise
		end

		$log.info("Page done");
	}
}

# tell each thread to stop after the next connection
def shutdown()
	#stop listening
	IO.for_fd(0).close();

	#kill all remaining threads
	$threads.each { |thread|
		if(thread.alive?)
			$log.info("Waiting on child thread");
			thread.join(1); #1 second should be enough

			if(thread.alive?)
				$log.info("Killing child thread");
				thread.kill();
			end
		end
	}
	exit();
end

trap("SIGTERM") { shutdown(); }
#trap("SIGHUP") { shutdown(); }

while($threads.length < 1)
	sleep(0.1);
end
