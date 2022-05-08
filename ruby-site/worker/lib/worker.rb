# The functions in this file help do distributed task processing. 
# You can declare any class method as a workable task by doing the following:
# class X
#  def self.do_stuff
#   # do stuff here
#  end
#  register_task(SiteModule, :do_stuff) # Simple, defaults to 60 second lock time and 24 hour expire time
#  register_task(SiteModule, :do_stuff, :lock_time => 120, :expire_time => 60*60*48) # Change lock time to 2 minutes and expire time to 2 days.
# end

# You can then call the task with one of the following methods defined on class X:
# X.do_stuff_defer()
# X.do_stuff_run()
# X.do_stuff_run_multi([])
# See below for more details. There are also more generic versions of those defined on
# the Worker module itself (the metaprogrammed functions above just call those).

require 'yaml'
lib_require :Worker, "gearman"

# Adds worker_task method to Class so you can register info about your worker function
class Class
	def register_task(site_module, method_name, opts = {})
		lock_time = opts[:lock_time] || 60
		expire_time = opts[:expire_time] || 60*60*24
		
		raise Exception.new("Attempt to register a task with a lock time and expire time of 0.") if (lock_time.zero? && expire_time.zero?)
		
		Worker.register_worker_task(site_module, self, method_name, lock_time, expire_time)
		
		class_eval(%Q{
			def self.#{method_name}_run_multi(*args_list)
				args_list = args_list.collect {|args|
					[#{site_module.name}, #{self.name}, :#{method_name}, *args]
				}
				Worker::run_multi(args_list)
			end
			def self.#{method_name}_run(*args)
				Worker::run(#{site_module.name}, #{self.name}, :#{method_name}, *args)
			end
			def self.#{method_name}_defer(*args)
				Worker::defer(#{site_module.name}, #{self.name}, :#{method_name}, nil, *args)
			end
			def self.#{method_name}_defer_delay(delay, *args)
				Worker::defer(#{site_module.name}, #{self.name}, :#{method_name}, delay, *args)
			end
		})
	end
end

module Worker
	def self.workers()
		@workers ||= {}
	end
	
	def self.register_worker_task(site_module, klass, method_name, lock_time, expire_time)
		workers[[site_module, klass.name, method_name.to_sym]] = {:lock_time => lock_time, :expire_time => expire_time}
	end
	
	def self.task_description(klass, method_name, args)
		{
			:class => klass.name,
			:method_name => method_name,
			:args => args
		}.to_yaml
	end
	
	def self.task_type(site_module)
		"ruby-site:#{$site.config.worker_queue_name}:#{site_module}"
	end
	
	class DeferredTask < Storable
		init_storable(:processqueue, "deferred_tasks")
		
		# for stats page
		def self.total()
			db.query("SELECT COUNT(*) AS count FROM deferred_tasks").fetch['count'].to_i
		end
		
		def self.pending_tasks
			db.query("SELECT COUNT(*) AS count FROM deferred_tasks WHERE status = 'new'").fetch['count'].to_i
		end
		
		def self.failed_tasks
			db.query("SELECT COUNT(*) AS count FROM deferred_tasks WHERE status = 'new' AND result != ''").fetch['count'].to_i
		end
	end
	
	# Runs the task later without waiting for a response. Uses the more reliable postprocessqueue.
	def self.defer(site_module, klass, method_name, delay, *args)
		worker_info = workers[[site_module, klass.name, method_name.to_sym]]
		raise "No such task to defer" if !worker_info
		
		task = DeferredTask.new
		task.type = task_type(site_module_get(site_module))
		task.description = task_description(klass, method_name, args)
		task.expire = Time.now.to_i + worker_info[:expire_time]
		task.lock_time = worker_info[:lock_time]
		task.lock_expire = Time.now.to_i + delay if (delay)
		task.result = ''
		task.store()
	end
	
	# Runs the task now through gearman if it can. Raises an error if it failed to run.
	def self.run(site_module, klass, method_name, *args)
		result = *run_multi([[site_module, klass, method_name, *args]])
		if (!result)
			raise "No worker was able to handle the job."
		elsif (result[:status] == :ok)
			return result[:result]
		else
			raise result[:error]
		end
	end
	
	# Like run, but lets you run multiple functions simultaneously. run_args is an
	# array arrays of arguments you would pass to run().
	def self.run_multi(run_args)
		client = Gearman::Client.new
		client.job_servers = $site.config.gearman_servers
		ts = Gearman::TaskSet.new(client)
		
		lock_times = []
		
		task_status = [false] * run_args.length
		run_args.each_with_index {|i, task_num|
			if (i.kind_of? Gearman::Task)
				task = i
				lock_times.push(task.timeout)
			else
				site_module, klass, method_name, *args = *i
				
				worker_info = workers[[site_module, klass.name, method_name.to_sym]]
				raise "No such task #{klass.name}.#{method_name}" if !worker_info
				
				type = task_type(site_module_get(site_module))
				desc = task_description(klass, method_name, args)
				
				$log.info("Dispatching task: #{type}, #{desc}", :debug, :worker)
				task = Gearman::Task.new(type, desc, :timeout => worker_info[:lock_time])
				
				lock_times.push(worker_info[:lock_time])
			end
			task.on_complete {|res|
				$log.info("Task #{task_num} completed: #{res}", :debug, :worker)
				task_status[task_num] = YAML::load(res)
				if (task_status[task_num][:status] == :fail)
					# Deal with a problem in YAML loading of exception objects: it doesn't take along any of the extra information, only the type.
					err = task_status[task_num][:error].exception(task_status[task_num][:message])
					err.set_backtrace(task_status[task_num][:backtrace])
					task_status[task_num][:error] = err
				end
			}
			task.on_fail {
				$log.info("Task #{task_num} failed", :debug, :worker)
				task_status[task_num] = false
			}
			ts.add_task(task)
		}
		$log.info("Running tasks for #{lock_times.max}s", :debug, :worker)
		ts.wait(lock_times.max)
		task_status = task_status.collect {|status|
			# fill in status' that are false (and thus are basically timed out) with error information
			if (!status)
				{:status => :fail, :error => :timeout, :message => 'Timed Out', :backtrace => [] }
			else
				status
			end
		}
		return task_status
	end
	
	# Dispatches gearman tasks to their handlers.
	def self.dispatch_tasks()
		worker = Gearman::Worker.new
		worker.job_servers = $site.config.gearman_servers
		
		terminated = false
		working = false

		site_modules {|mod|
			type = task_type(mod)
			$log.info("Ability Registered: #{type}", :info, :worker)
			worker.add_ability(task_type(mod), &lambda {|data, job|
				working = true
				
				result = {
					:status => :fail,
					:result => nil,
					:error => nil,
					:message => nil,
					:backtrace => nil,
				}
				begin
					oob = {}
					desc = nil
					YAML::load_documents(data) {|doc|
						if (!desc)
							desc = doc
						else
							oob = doc
						end
					}
					# Do some validation
					if (!desc.kind_of?(Hash) ||
						  !/^([A-Z][a-zA-Z]*)(::[A-Z][a-zA-Z]*)*$/.match(desc[:class]))
						raise "Invalid task description: #{desc[:class]}."
					end
					klass = eval(desc[:class])
					method = klass.method(desc[:method_name])
					if (method.arity > desc[:args].length || method.arity < -desc[:args].length)
						desc[:args].push(job) # if the function can take more args, tack on the job object so it can update things.
					end
					$site.cache.use_context({}) {
						result[:result] = method.call(*desc[:args])
					}
					result[:status] = :ok
				rescue Object => err
					$log.error
					result[:status] = :fail
					result[:error] = err
					result[:message] = err.message
					result[:backtrace] = err.backtrace
				end
				result_yaml = result.to_yaml
				
				if (oob && oob[:task_id])
					# this came from the dispatcher. Update the deferred task with result information.
					db = DeferredTask.db
					if (result[:status] == :ok)
						db.query("UPDATE deferred_tasks SET status = ?, lock_expire = 0, result = ? WHERE id = ?", 'done', result_yaml, oob[:task_id])
					else
						db.query("UPDATE deferred_tasks SET status = ?, lock_expire = ?, result = ? WHERE id = ?", 'new', Time.now().to_i + 5*60, result_yaml, oob[:task_id])
					end
				end
				
				return result_yaml
			})
		}
		trap("SIGTERM") {
			terminated = true
			exit if !working
		}
		loop {
			begin
				worker.work()
			ensure
				working = false
			end
			break if (terminated)
		}
	end
	
	def self.dispatch_deferred_tasks()
		db = DeferredTask.db
		terminated = false
		trap("SIGTERM") {
			terminated = true
		}
		loop {
			begin
				items = []
				$log.log_minlevel_raise(:sql, :warning) {
					begin
						db.query("BEGIN")
						# grab up to 50 items from the deferred task table in order by expire time
						items = db.query("SELECT * FROM deferred_tasks WHERE status IN ('new', 'working') AND lock_expire < ? AND expire > ? ORDER BY expire ASC LIMIT 50 FOR UPDATE", Time.now().to_i, Time.now().to_i).collect {|i| i}
						max = items.collect {|row| row['lock_time'].to_i }.max
						if (items.length > 0)
							# Mark them as in progress
							db.query("UPDATE deferred_tasks SET status = 'working', lock_expire = ? WHERE id IN ?", Time.now.to_i + max + 15, items.collect {|item| item['id'] })
						end
						db.query("COMMIT")
					rescue
						$log.error
						db.query("ROLLBACK")
					end
				}
				if (items.length < 1)
					# no tasks to run. So instead, wait a second and then check again.
					sleep(1)
					next
				end
				$log.info("Got #{items.length} tasks from deferred task list", :info, :worker)
				# Run them
				results = run_multi(items.collect {|item|
					oob = {:task_id => item['id']}.to_yaml
					Gearman::Task.new(item['type'], item['description'] + "\n" + oob, :timeout => item['lock_time'].to_i)
				})
				$log.object(results, :info, :sql)
				items.each_with_index {|item, i|
					begin # keep it from blowing up the whole thing if one of these queries errors.
						result = results[i]
						if (!result)
							db.query("UPDATE deferred_tasks SET status = ?, lock_expire = ? WHERE id = ? AND status IN ('new', 'working')", 'new', Time.now().to_i + 5*60, item['id']) # wait a minute before trying again.
						elsif (result[:status] == :fail && result[:error] == :timeout)
							db.query("UPDATE deferred_tasks SET status = ?, lock_expire = ?, result = ? WHERE id = ? AND status IN ('new', 'working')", 'new', Time.now().to_i + 5*60, result.to_yaml, item['id'])
						end
						# We let the gearman dispatcher deal with updating success status rather than doing it here. This way
						# if the -d runner gives up on the task, it still marks it as successful in the db whenever it's actually done. But
						# if the task really did fail/timeout, it will still register it as timed out. To prevent the task from getting marked
						# as 'new' again when it's already been marked as done, we check that status != 'done' when updating the status
						# to indicate failure above.
					rescue
						$log.error
					end
				}
			rescue
				$log.error
			ensure
				break if terminated
			end
		}
	end
	
	class Test
		def self.ok(arg1)
			return "blah: #{arg1}"
		end
		register_task(WorkerModule, :ok)
		def self.error(arg1)
			raise "blah: #{arg1}"
		end
		register_task(WorkerModule, :error)
		def self.timeout()
			sleep(5*60)
		end
		register_task(WorkerModule, :timeout)
	end
end