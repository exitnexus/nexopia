module Moderator
	# This class and file should go away once these queues are properly implemented.
	# For now, it just redirects back to the equivalent php queues.
	class UnimplementedQueueHandlers < PageHandler
		declare_handlers("moderate/display") {
			area :Internal
			access_level :LoggedIn

			handle :GetRequest, :use_php_queue, 'Moderator::ForumPostQueue'
			handle :GetRequest, :use_php_queue, 'Moderator::ForumBanQueue'
			handle :GetRequest, :use_php_queue, 'Profile::UserAbuseQueue'
			handle :GetRequest, :use_php_queue, 'Profile::UserAbuseConfirmQueue'
		}
	
		def use_php_queue()
			queue = params.real_hash['queue']
			site_redirect(url/"moderate.php"&{'mode'=>queue.queue_number}, :Public)
		end
	end
end