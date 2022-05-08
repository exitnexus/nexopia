YouTubeSearch = {
	
	find_videos: function(event, element) {
		
		if( event ) { YAHOO.util.Event.preventDefault(event); }
		
		var search_terms = document.getElementById("search_terms"); 
		if( (search_terms != "") && (search_terms != null)) {

			var spinner = document.getElementById("search_spinner");
			if( spinner ) { YAHOO.util.Dom.setStyle(spinner, "display", "block"); }

			var search_form = document.getElementById("youtube_search_form");
			YAHOO.util.Connect.setForm(search_form);
			YAHOO.util.Connect.asyncRequest(search_form.method, search_form.action, new ResponseHandler({
				success: function(o) {
					var spinner = document.getElementById("search_spinner");
					if( spinner ) { YAHOO.util.Dom.setStyle(spinner, "display", "none"); }
				},
				failure: function(o) {
					var spinner = document.getElementById("search_spinner");
					if( spinner ) { YAHOO.util.Dom.setStyle(spinner, "display", "none"); }
				},
				scope: this
			}), "");
			
		}
	},
	
	add_video: function(event, element) {

		var result_container = YAHOO.util.Dom.getAncestorByClassName(element, 'video_container');
		// remove the 'video_' bit from the container id to get the index
		var video_id = parseInt(result_container.getAttribute('id').slice(6), 10);
		var embed_code = document.getElementById("embed_" + video_id).value;

		var return_location = document.getElementById("return_location");
		var embed_input = document.getElementById(return_location.value);
		embed_input.value = embed_code;

		NexopiaPanel.current.close();
	}
	
};

Overlord.assign({
	minion: "youtube:add_video",
	click: YouTubeSearch.add_video,
	scope: YouTubeSearch
});

Overlord.assign({
	minion: "youtube:find_videos",
	submit: YouTubeSearch.find_videos,
	scope: YouTubeSearch
	
});