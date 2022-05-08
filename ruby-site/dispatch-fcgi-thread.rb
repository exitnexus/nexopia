#!/bin/env ruby

# threaded fcgi dispatcher: spawn several threads, each in an fcgi accept loop

require 'core/lib/fcgi-native-ext';

FCGI.listen($ipaddr, $port);

require 'site_initialization';

$0 = "Nexopia Parent";
initialize_site();

$threads = [];

# Creates a child process and returns its pid.
def create_thread()
	thread = Thread.new {
		$0 = "Nexopia Child";
		Thread.current['exit_next'] = false;
		Thread.current['handling_request'] = false;
		Thread.current['request_num'] = 0;


		catch(:end_fcgi) {
			FCGI.each_cgi {|$cgi|
				Thread.current['request_num'] += 1;
				Thread.current['handling_request'] = true;

				$log.reassert_stderr();
				begin
					PageRequest.new_from_cgi($cgi) {|req|
						PageHandler.execute(req);
					}
				rescue
					$log.error
					raise
				end

				Thread.current['handling_request'] = false;

				if(Thread.current['exit_next'])
					throw :end_fcgi;
				end
			}
		}
	}
	return thread;
end


$num_children.times {
	$threads.push(create_thread());
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
