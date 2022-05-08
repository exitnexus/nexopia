lib_require :Core, 'pagehandler'
lib_require :Forums, 'thread'

module Forum
	class ForumBlocks < PageHandler
		declare_handlers("/") {
			area :Public
			access_level :Any

			handle :GetRequest, :randforum, "randforum"
		}

		def randforum()
			$log.info "Random forum."
			#thread = Thread.get_updated();
			#if (thread)
				#puts %Q| #{thread.forum.category.name} > #{thread.forum.name}<br/>|;
				#puts %Q| #{thread.title}<br/><br/> |;
			#end
			$log.info "Done."
		end
	end
end
