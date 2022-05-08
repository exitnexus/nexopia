require 'youtube_g'

module YouTubeSearch
	class YouTubePageHandler < PageHandler
		declare_handlers("youtube") {
			area :Public
			handle :GetRequest, :search_panel, "search_panel"
			handle :PostRequest, :search, "search"
		}
		
		def search()			
			search_terms = params['search_terms', String, ""]
			
			if( search_terms != "" )
				client = YouTubeG::Client.new
				response = client.videos_by(:query => search_terms)
			
				t = Template::instance('youtube_search', 'search_results')
				t.videos = response.videos
			
				puts t.display
			end
			
		end
		
		def search_panel()
			t = Template::instance('youtube_search', 'search_panel')
			t.return_location = params['return_location', String, ""]
			puts t.display
		end

	end # class YouTubePageHandler < PageHandler
end # module YouTubeSearch