module Worker
	class Status < PageHandler
		declare_handlers("worker") {
			area :Public
			access_level :DebugInfo
			page :GetRequest, :Full, :status, "status"
			page :GetRequest, :Full, :failed, "failed"
			page :GetRequest, :Full, :success, "success"
		
			page :GetRequest, :Full, :task, input(Integer)
		}
	
		def status()
			total_tasks = DeferredTask.total
			pending = DeferredTask.pending_tasks
			failed = DeferredTask.failed_tasks
			new_tasks = pending - failed
			done_tasks = total_tasks - pending
		
			puts(%Q{
				<ul>
					<li>Total tasks: #{total_tasks}</li>
					<li>Tasks that haven't been run: #{new_tasks}</li>
					<li>Tasks that have been run and <a href="/worker/failed">failed: #{failed}</a></li>
					<li>Tasks that have been completed <a href="/worker/success">successfuly: #{done_tasks}</a></li>
				</ul>			
			})
		end
	
		def failed()
			page = params["page", Integer, 0]
			tasks = DeferredTask.find(:conditions => "status IN ('new', 'working') AND result != ''", 
			                          :order => "expire ASC",
			                          :limit => 20,
			                          :page => page + 1)
			puts(%Q{
			<a href="/worker/failed?page=#{page+1}">Next Page</a>
			<style>
				.expired { color: red; }
				.fresh {}
			</style>
			<table border="1">
				<tr><th>id</th><th>Expire Time</th><th>Type</th><th>Class</th><th>Method</th><th>Error Message</th></tr>
				})
				tasks.each {|task|
					desc = YAML::load(task.description)
					result = YAML::load(task.result)
					exp_class = Time.at(task.expire) < Time.now ? "expired" : "fresh"
					puts(%Q{
						<tr class="task_row #{exp_class}">
							<td><a href="/worker/#{task.id}">#{task.id}</a></td>
							<td>#{UserTime.at(task.expire)}</td>
							<td>#{htmlencode(task.type)}</td>
							<td>#{desc[:class] && htmlencode(desc[:class])}</td>
							<td>#{desc[:method_name] && htmlencode(desc[:method_name].to_s)}</td>
							<td>#{result[:message] && htmlencode(result[:message])}</td>
						</tr>
					})
				}
				puts(%Q{
			</table>
			<a href="/worker/failed?page=#{page+1}">Next Page</a>
			})
		end
		def success()
			page = params["page", Integer, 0]

			tasks = DeferredTask.find(:conditions => "status = 'done'", 
			                          :order => "expire DESC",
			                          :limit => 20,
			                          :page => page + 1)
			puts(%Q{
			<a href="/worker/success?page=#{page+1}">Next Page</a>
			<style>
				.expired { color: red; }
				.fresh {}
			</style>
			<table border="1">
				<tr><th>id</th><th>Expire Time</th><th>Type</th><th>Class</th><th>Method</th></tr>
				})
				tasks.each {|task|
					desc = YAML::load(task.description)
					exp_class = Time.at(task.expire) < Time.now ? "expired" : "fresh"
					puts(%Q{
						<tr class="task_row #{exp_class}">
							<td><a href="/worker/#{task.id}">#{task.id}</a></td>
							<td>#{UserTime.at(task.expire)}</td>
							<td>#{htmlencode(task.type)}</td>
							<td>#{desc[:class] && htmlencode(desc[:class])}</td>
							<td>#{desc[:method_name] && htmlencode(desc[:method_name].to_s)}</td>
						</tr>
					})
				}
				puts(%Q{
			</table>
			<a href="/worker/success?page=#{page+1}">Next Page</a>
			})
		end
	
		def task(task_num)
			task = DeferredTask.find(:first, task_num)
			
			puts(%Q{
				<table>
					<tr><th>Type</th><td>#{htmlencode(task.type)}</td></tr>
					<tr><th>Status</th><td>#{task.status}</td></tr>
					<tr><th>Expire Time</th><td>#{UserTime.at(task.expire)}</td></tr>
					<tr><th>Lock Timeout</th><td>#{task.lock_time}s</td></tr>
					<tr><th>Lock Expire (if currently running)</th><td>#{Time.at(task.lock_expire) > Time.now && UserTime.at(task.lock_expire)}</td></tr>
					<tr><th>Description</th><td><pre>#{htmlencode(task.description)}</pre></td></tr>
					<tr><th>Result</th><td><pre>#{htmlencode(task.result)}</pre></td></tr>
				</table>
			})
		end
	end
end