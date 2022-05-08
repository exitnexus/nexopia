#!/bin/env ruby

# threaded fcgi dispatcher: master-worker model

require 'core/lib/fcgi-native-ext';

FCGI.listen($ipaddr, $port);

require 'site_initialization';

$0 = "Nexopia Parent";
initialize_site();

$threads = [];

# Creates a child process and returns its pid.
def create_thread()
	thread = Thread.new {
		Thread.current['exit_next'] = false;
		Thread.current['handling_request'] = false;
		Thread.current['request_num'] = 0;
		Thread.current['cgi'] = nil;

		#keep trying to process requests
		while(true)
			#wait for a request to come in
			while(!Thread.current['cgi'])
				if(Thread.current['exit_next'])
					Thread.exit();
				end
				sleep(0.001);
			end
			$log.reassert_stderr();

			Thread.current['request_num'] += 1;
			Thread.current['handling_request'] = true;

			$log.reassert_stderr();
			PageRequest.new_from_cgi(Thread.current['cgi']) {|req|
				PageHandler.execute(req);
			}

			Thread.current['cgi'] = nil;
			Thread.current['handling_request'] = false;

			$log.info("Page done");
		end
	}
	return thread;
end

$num_children.times {
	$threads.push(create_thread());
}

FCGI.each_cgi { |cgi|
	$log.reassert_stderr();

	#Find a thread to deal with this request
	catch(:thread_found) {
		while(true)
			$threads.each { |thread|
				if(!thread['cgi'])
					#pass it the cgi object
					thread['cgi'] = cgi;

#					thread.run();

					throw :thread_found;
				end
			}
			sleep(0.001); #wait a millisecond before trying again
		end
	}
}

# tell each thread to stop after the next connection
def shutdown()
	#stop listening so hopefully they close if they're waiting to accept or else on next accept
	IO.for_fd(0).close();

	#tell each thread to exit when it's done this request
	$threads.each { |thread|
		thread['exit_next'] = true;
	}

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

while(sleep(0.1))
	if($threads.length < 1)
		break;
	end
end
