lib_require :Core, "storable/storable"
lib_require :Core, 'typeid'

=begin
Post Process Queue (PPQ)

The PPQ is a database backed persistent, distributed task handling system.
=end
module Worker
	class PostProcessQueue
		
		@@machine_name = $site.config.queue_identifier;
		if (@@machine_name.nil? or @@machine_name.length < 1)
			@@machine_name = "unnamed machine"
		end
		def self.machine_name
			return @@machine_name;
		end
	

		
		def PostProcessQueue.queue(klass, func, args, request_notification = false)
			Task.queue(klass, func, args);
		end
	
		# Encapsulate a single "task" that is backed by the database.
		class Task < Storable
			init_storable(:processqueue, "postprocessqueue")

			# Get the next item off of the queue.
			def Task.get_first()
				modules = [];
				SiteModuleBase.loaded(){|mod|
					modules << SiteModuleBase.get(mod).class.typeid;
				}
				task = nil; 
				$log.log_minlevel_raise(:sql, :warning){
					self.db.query("UPDATE #{self.table} SET `lock` = ?, `owner` = ?, `status` = 'queued' WHERE `expiry` > ? && `lock` < ? && `status` != 'done' && (cluster = ? or cluster = '') && module IN ? LIMIT 1", Time.now.to_i+30, PostProcessQueue.machine_name, Time.now.to_i, Time.now.to_i, PostProcessQueue.machine_name, modules);
					task = find(:first, :conditions => ["`expiry` > ? && `owner` = ?  && `status` = 'queued'", Time.now.to_i, PostProcessQueue.machine_name], :limit => 1)
				}
				task
			end
	
			# Queue a long-term worker process.
			#
			# klass is the name of the object on which the process will act.
			# func is the string name of the class method to call.
			# args is a string of the arguments that will be used.
			def self.queue(klass, func, args)
				
				identifier = MD5.new(func.to_s + Marshal.dump(args) + PostProcessQueue.machine_name).to_s;
				self.db.query "INSERT IGNORE INTO #{self.table}
								SET
								`module` = ?,
								`time` = ?,
								`func` = ?,
								`params` = ?,
								`unique` = ?,
								`expiry` = ?,
								`cluster` = ?,
								`status` = 'queued'",
								klass.typeid,
								Time.now.to_i,
								func,
								CGI::escape(Marshal.dump(args)),
								identifier,
								Time.now.to_i + 86400,
								PostProcessQueue.machine_name;
			
				return find(:first, :promise, :refresh, :conditions => ["`unique` = ?", identifier]);
				
			end
			
			# Run a task. When its done running, delete any tasks that are finished or expired.
			# Tasks are re-queued (with a delay) on failure.
			def run(worker)
				require "observations/lib/observable"
				self.status = "inprogress";
				self.store();
				#klass = TypeID.get_class(self.module);
	
				begin
					worker.run(self.module, self.func, self.params)
				rescue Exception => e
					self.class.db.query(
						%Q| UPDATE #{self.class.table} SET `status` = 'queued', `owner` = 'Errored', msg = '#{CGI::escape($!.to_s)} #{CGI::escape($!.backtrace.to_s)}', `lock` = ? WHERE id = ?|,
						Time.now.to_i + 600, @id);
					raise e;
				end
		
				self.status = "done";
				self.store;
				self.class.db.query "DELETE from #{self.class.table} WHERE `status` = 'done'";
				self.class.db.query "DELETE from #{self.class.table} WHERE `expiry` < ?", Time.now.to_i;
			end

		end

		# Encapsulate a Worker Process.  The worker process has abilities added
		# to it by the worker module.
		class Worker
			@@abilities = {};
			def work()
				begin
					$log.reassert_stderr();
					job = Task::get_first()
					if job != nil
						$site.cache.use_context({}) {
							begin
							job.run(self);
							rescue
								$log.info $!, :error;
								$!.backtrace.each{|line|
									$log.info line, :error;
								}
							end
						}
						job.delete();
						return true
					end
					return false;
				rescue
					$log.info $!, :error;
					$!.backtrace.each{|line|
						$log.info line, :error;
					}
				end
			end
			
			def add_ability(mod, name, &block)
				@@abilities[[mod,name]] = block;
			end
			
			def run(mod_id, func, params)
				mod_class = TypeID.get_class(mod_id);
				if mod_class.name =~ /(\w+)Module$/
					mod = SiteModuleBase.get($1)
					mod.load_all_rb;
					key = [:"#{mod.name}", :"#{func}"]
					if (!@@abilities[key])
						$log.info "Trying to handle an event that has no handler:", :error
						$log.object key, :error
						$log.object @@abilities.keys, :error
						raise "Event is not handled."
					end
					ret = Marshal.load(@@abilities[key].call(params));
					if (!ret.kind_of? Array)
						$log.info ret, :error
						$log.info ret.backtrace, :error
					end
				end
			end
		
		end
	
	end
end