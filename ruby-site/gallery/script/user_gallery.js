UserGallery = {
	init: function() {
		this.tabs = new YAHOO.widget.TabView('user_gallery');
		YAHOO.util.Event.on('film_view_link', 'click', function(event) {this.blur();});
		YAHOO.util.Event.on('thumb_view_link', 'click', function(event) {this.blur();});
	},
	//returns [userid, id] based on an image path
	parsePath: function(path) {
		var match = path.match(/\/gallery\w*\/\d+\/(\d+)-*[^\/]*\/(\d+)-*[^\/]*\./);
		
		return [match[1],match[2]];
	}
};

Overlord.assign({
	minion: "user_gallery",
	load: UserGallery.init,
	scope: UserGallery
});