Overlord.assign({
	minion: "recent_galleries:description",
	load: function(element) {
		new Truncator(element, {height: 40, width: 149});
	}	
});
