module Polls
	class FrontPagePoll < PageHandler
		declare_handlers("polls/front") {
			area :Public

			page :GetRequest, :Full, :front_results, "results"
			page :GetRequest, :Full, :front_vote, "vote"
		}
		
		def front_results
			# TODO: Ruby side implementation of the front page polls
			if(request.session.anonymous?)
				site_redirect( url / :account / :join & {:referer => "/polls/front/results"})
			else
				puts "Not supported yet."
			end
		end
		
		
		def front_vote
			# TODO: Ruby side implementation of the front page polls
			if(request.session.anonymous?)
				site_redirect( url / :account / :join & {:referer => "/polls/front/vote"})
			else
				puts "Not supported yet."
			end
		end
	end
end