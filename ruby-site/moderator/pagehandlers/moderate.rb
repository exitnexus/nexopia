lib_require :Moderator, "moderator"

module Moderator
	class Queue < PageHandler
		declare_handlers("moderate/queue") {
			area :Public
			access_level :LoggedIn
			
			page :GetRequest, :Full, :index
			page :GetRequest, :Full, :queue, input(Integer)
			handle :OpenPostRequest, :vote, input(Integer), :vote
		}
		
		def index()
			queues = request.session.user.get_queue_counts
			has_items = queues.any? {|k,v| v > 0 }
			if (!has_items)
				puts("No Requests")
			else
				t = Template.instance(:Moderator, "list")
				t.queues = queues
				puts t.display
			end
		end
		
		def queue(queue_number)
			queue = QueueBase.by_number(queue_number)
			prefs = request.session.user.mod_prefs(queue)
			if (!queue || !prefs)
				raise PageError.new(403), "Access denied."
			end

			while (true)
				# User has access to this queue, so let's fetch some items.
				items = queue.fetch_votable_items(request.session.user, prefs.picsperpage, params['uid', Integer, nil])
				if (!items || items.empty?)
					rewrite(request.method, url/:moderate/:queue, {'uid'=>params['uid', Integer, nil]}, :Public) # show the list of queues.
				end
			
				# clear out 'dead' items.
				items = items.collect {|i|
					if (!i.validate())
						i.delete()
						nil
					else
						i
					end
				}.compact
			
				break if (!items.empty?) # if there are no items in the queue, we should start over
			end
			
			req = subrequest(nil, request.method, url/:moderate/:display/queue.name, {'queue' => queue, 'items' => items, 'prefs' => prefs}, :Internal) # display the vote items.
			if (req.reply.ok?)
				t = Template.instance(:Moderator, "moderate")
				t.queue = queue
				t.prefs = prefs
				t.item_output = req.reply.out.string
				puts(t.display)
			elsif (req.reply.status == 302 || req.reply.status == 301)
				external_redirect(req.reply.headers['Location'])
			else
				rewrite(request.method, url/:moderate/:queue, {'uid'=>params['uid', Integer, nil]}, :Public)
			end
		end
		
		def vote(queue_number)
			queue = QueueBase.by_number(queue_number)
			prefs = request.session.user.mod_prefs(queue)
			if (!queue || !prefs)
				raise PageError.new(403), "Access Denied"
			end
			
			votes = params['check', {Integer => Boolean}]
			queue.vote_on_items(request.session.user, votes) if (votes)
			if (params['stopModding', Boolean])
				site_redirect(url/:moderate/:queue, :Public)
			else
				site_redirect(url/:moderate/:queue/queue_number, :Public)
			end
		end
	end
end
		