module Moderator
	class Display < PageHandler
		declare_handlers("moderate/display") {
			area :Internal
			access_level :LoggedIn

			handle :GetRequest, :default, input(String)
		}
		
		def default(queue_type)
			if (match = /^([^:]+)::(.+)$/.match(queue_type)) # queue class has a module name in it, so use that to try and generate a template name.
				name = match[2].to_s.gsub(/^[A-Z]/) {|a| a.downcase }.gsub(/(::)?([A-Z])/) { "_#{$2.downcase}" }
				begin
					t = Template.instance(match[1].to_sym, name)
					t.queue_items = params['items', [ModItem]]
					t.queue = params['queue', QueueBase]
					t.prefs = params['prefs', Moderator]
					puts(t.display)
					return
				rescue Errno::ENOENT => err
					$log.info("Queue #{queue_type} does not have its own pagehandler or a matching template. Was looking for :#{match[1]}, '#{name}'", :error, :moderator)
					# otherwise ignore this.
				end
			end
			puts("No queue display handler registered for this queue type.")
		end
	end
end