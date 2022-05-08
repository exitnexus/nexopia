Overlord.assign({
	minion: "blog:year_selector",
	change: function(element){
		var yearURL = this.options[this.selectedIndex].value;
		location.href = yearURL;
	}
});

Overlord.assign({
	minion: "blog:calendar_post_link",
	click: function(event, element){
		var link = YAHOO.util.Dom.getElementsBy(function(el){return true;},'a', element)[0];
		location.href = link.href;
	}
})