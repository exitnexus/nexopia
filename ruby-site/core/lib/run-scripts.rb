module Core
	class RunScripts
		# Pass in a prefix or set of prefixes to run.
		def self.run(prefixes, should_fork = false, status = nil)
			runfiles = [];
			prefix_found = false;
			prefixes.each {|type|
				prefix_found = false;
				site_modules {|mod|
					Dir["#{mod.run_path}/#{type}*.rb"].each {|file|
						if (File.ftype(file) != 'directory')
							runfiles.push(file);
							prefix_found = true;
						end
					}
				}
				if(!prefix_found)
					$log.info("Run script '#{type}' not found in any module.", :error, :run);
					raise ArgumentError.new("Script '#{type}' not found");
				end
			}

			run = proc {
				$log.info("Running #{runfiles.join(',')} in forked process #{Process.pid}") if (should_fork)
				start = Time.now
				
				instance = Thread.current[:run_script_runner] = self.new
				begin
					runfiles.each {|file|
						begin
							$0 = "nexopia-runner task:#{file}" if (should_fork)
							$site.cache.use_context(nil) {
								instance.run_script(file);
							}
						rescue
							$log.info("Run script #{file} failed with error:", :error, :run)
							$log.error()
						end
					}
				ensure
					$log.info("Finished running #{runfiles.join(',')} in forked process #{Process.pid}. Took #{Time.now.to_i - start.to_i}s.") if (should_fork)
					Thread.current[:run_script_runner] = nil
				end
			}
			if (should_fork)
				fork(&run)
			else
				run.call
			end
		end
		
		if (site_module_loaded? :Worker)
			register_task CoreModule, :run, :lock_time => 120
		end

		def initialize()
			@regex = /([^-\/]+)-(.+)\.rb$/;
			@running = nil;
			@running_info = {};
			@depth = 0;
			@ran = {};
		end

		def run_script(file)
			@running, file = file, @running; # swap them so we can restore them after this is done.

			res = @running.match(@regex);

			if(!res)
				$log.info("#{@running} not found, must be named as category-task.rb", :info, :run)
				exit 1
			end

			info = {:type => res[1], :name => res[2]};

			@running_info, info = info, @running_info;

			begin
				if (!@ran.key?(@running))
					@ran[@running] = true;
					$log.info("#{'|'*@depth}Running #{@running_info[:type]}-#{@running_info[:name]}...", :info, :run);
					require(@running);
				end
			ensure
				@running, file = file, @running; # swap back
				@running_info, info = info, @running_info;
			end
		end
		def depends_on(name)
			new_file = @running.sub(@regex, '\1-' + name + '.rb');
			@depth += 1;
			$log.info("#{'|'*@depth}#{@running_info[:type]}-#{@running_info[:name]} depends on #{@running_info[:type]}-#{name}", :info, :run);
			run_script(new_file);
			@depth -= 1;
		end
	end
end

def depends_on(name)
	runner = Thread.current[:run_script_runner]
	if (!runner)
		raise "Must be inside a runscript to call depends_on"
	end
	
	runner.depends_on(name)
end