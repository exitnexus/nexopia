	
	
module Dispatcher

	# Kill all children in the pids list, forcefully if needed
	def self.kill_children(pids, name = "child")
		pids.compact!;

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
	def self.kill_pids(pids, name = "child", sig = "SIGTERM")
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
	def self.catch_pids(pids, name = "child")
		begin
			#iterate over duplicate so deleting from the array doesn't break, and the reference to pids stays valid
			itpids = pids.dup;
			itpids.each {|pid|
				if(pid && exitpid = Process.wait(pid, Process::WNOHANG))
					pids.delete(exitpid);
					$log.info("#{name} process #{exitpid} killed successfully", :debug);
				end
			}
		rescue SystemCallError #no pids to wait on
			pids.clear();
		end
	end
end
	
