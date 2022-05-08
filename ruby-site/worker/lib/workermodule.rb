=begin
	PPQ Distributed Worker Module, Comrade
                !#########       #
               !########!          ##!
            !########!               ###
         !##########                  ####
       ######### #####                ######
        !###!      !####!              ######
          !           #####            ######!
                        !####!         #######
                           #####       #######
                             !####!   #######!
                                ####!########
             ##                   ##########
           ,######!          !#############
         ,#### ########################!####!
       ,####'     ##################!'    #####
     ,####'            #######              !####!
    ####'                                      #####
    ~##                                          ##~

The distributed worker module encapsulates 2 different types of distributed 
worker: Gearman, and the Post Process Queue (PPQ).

Both PPQ and Gearman are initialized the following way: 
As site initialization begins, include 'kernel_addon.rb' (before modules are 
loaded) to register the "worker_task" method.  Then, initialize the site.  
Finally, call init_gearman() and init_ppq().

Protocols

PPQ: Insert a column into the PostProcessQueue database with cluster-name set to
  the name of the machine you want to run it (or none if any machine will do.) 
  Set the owner to blank, and the time/expiry to 0.  The params should be
  serialized in either Ruby format or PHP format with "php" prepended.

Gearman: Send the command "#{module_name}:#{function_name}" to the server, 
  with the serialized params (Ruby format, or PHP format with "php" prepended) 
  as the data.
=end

class WorkerModule < SiteModuleBase
	
	@@gearman_initialized = false;
	@@worker_initialized = false;
	@@gearman_client = nil;
	
	# As site modules are loaded, they may register their methods as worker tasks.
	# This can happen before gearman or the ppq are initialized, so they are 
	# flagged for delayed load and stored in the following hashes:
	@@gearman_delayed_load = [];
	@@worker_delayed_load = [];
	
	def self.task_protocol(module_sym, func)
		"#{$site.config.gearman_protocol_id}#{module_sym}:#{func}"
	end

	def self.do_task(site_module, func, args)
		lib_require :Worker, 'gearman'
		if (!@@gearman_client)
			init_gearman_client();
		end
		
		mod = site_module.class
		command = CGI::escape(Marshal.dump(args))

		t1 = Gearman::Task.new(task_protocol(site_module.name, func), command, :retry_count => 2)

		# Make the tasks return the data they get back from the server.
		t1.on_complete {|raw_result|
			result = Marshal.load(raw_result)
			if (!result.kind_of? Array)
				raise result
			end
			return result
		}
		t1.on_fail { 
			$log.info "Gearman failed with #{$!}.", :critical;
			raise "Gearman worker returned failure." 
		}

		ts = Gearman::TaskSet.new(@@gearman_client)
		ts.add_task(t1)
		ts.wait(10)
		raise "Timed out waiting for task to complete"

	end

	def self.init_gearman_client()
		lib_require :Worker, 'gearman'
		# Create a new client and tell it about two job servers.
		@@gearman_client = Gearman::Client.new
		@@gearman_client.job_servers = $site.config.gearman_servers;
	end
	
	# Initialize gearman, start a worker process, and register all of the worker
	# tasks that were set for delayed load.  (More tasks can still be added 
	# after)
	def self.init_gearman()
		@@gearman_initialized = true;

		lib_require :Worker, 'gearman'

		@@gearman_worker_pid = fork {
			@@gearman_worker = Gearman::Worker.new($site.config.gearman_servers)
	
			# When either worker receives a task, it receives a Module name, and
			# task name, which are mapped to a specific class and method. The method
			# and task name are always the same.
			@@gearman_delayed_load.each{|(mod_symbol,klass,name)|
				@@gearman_worker.add_ability(task_protocol(mod_symbol, name)){|data,job|
					job.report_status(0, 1);
					work(klass, name, data);
				}
			}
	
			g_exit = false;
			trap("SIGTERM"){
				if @@gearman_worker.state == :working
					g_exit = true;
				else
					exit(0)
				end
			}; # make sure we don't try to reap other child processes when we exit.

			# close the listening socket, we're not going to do anything with it.
			IO.for_fd(0).close();
			$0 = "nexopia-gearman-worker";
			loop {
				begin
					$site.cache.use_context({}) {
						@@gearman_worker.work
					}
					if (g_exit)
						exit(0);
					end
				rescue 
					if (g_exit)
						exit(0);
					end
					$log.info $!, :error;
					$!.backtrace.each{|line|
						$log.info line, :error;
					}
				end
			}
		}
		return @@gearman_worker_pid;
	end

	# Initialize PPQ, start a worker process, and register all of the worker
	# tasks that were set for delayed load.  (More tasks can still be added 
	# after)
	def self.init_ppq()
		@@worker_initialized = true;

		lib_require :Worker, 'post_process_queue';
		@@ppq = Worker::PostProcessQueue::Worker.new();

		# When either worker receives a task, it receives a Module name, and
		# task name, which are mapped to a specific class and method. The method
		# and task name are always the same.
		@@worker_delayed_load.each{|(mod_symbol,klass,name)|
			@@ppq.add_ability(mod_symbol, name){|data|
				work(klass, name, data);
			}
		}
		@@queue_worker_pid = fork {
			begin
				trap("SIGTERM", "DEFAULT"); # make sure we don't try to reap other child processes when we exit.
				# close the listening socket, we're not going to do anything with it.
				IO.for_fd(0).close();
				$0 = "nexopia-worker";
	
				Process.setpriority(Process::PRIO_PROCESS, 0, 19)
	
				loop {	sleep 1 if not @@ppq.work }
			rescue Object
				$log.info $!, :error;
				$log.info $!.backtrace.join("\n"), :error;
			end
		}
		return @@queue_worker_pid;
	end


	# Register a task, which is a mapping [Module name, task name] => [Class, 
	# method]. The method name and task name are restricted to be the same.
	def self.add_handler(mod_symbol, klass, name)
		$log.info "Adding worker #{task_protocol(mod_symbol, name)}", :debug
		if (@@gearman_initialized)
			@@gearman_worker.add_ability(task_protocol(mod_symbol, name)){|data,job|
					work(klass, name, data);
			}
		else
			@@gearman_delayed_load << [mod_symbol, klass, name];
		end

		if (@@worker_initialized)
			@@ppq.add_ability(mod_symbol, name){|data|
				work(klass, name, data);
			}
		else
			@@worker_delayed_load << [mod_symbol, klass, name];
		end

	end

	# Deserialize the parameter data, and run the class method.
	def self.work(klass, name, params)
		$log.reassert_stderr();
		$log.info "Working on task! #{klass} -> #{name}"
		if (params[0...3] == "php")
			require "core/lib/php_serialize"
			deserialized_params = PHP.unserialize(params[3..-1]);
		else
			deserialized_params = Marshal.load(CGI::unescape(params));
		end
		begin
			return Marshal.dump([klass.method(name).call(*deserialized_params)]);
		rescue Object
			return Marshal.dump($!)
		end
	end


end
