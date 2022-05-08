//require user_gallery.js
UserGallery.NormalView = {
	thumbs: [],
	loadImages: function() {
		for (var i=0;i<this.thumbs.length;i++) {
			this.thumbs[i].loadImage();
		}
	}
};

UserGallery.NormalView.Thumb = function(element) {
	this.element = YAHOO.util.Dom.get(element);
	this.url = this.element.attributes.url.value;
	var ids = UserGallery.parsePath(this.url);
	this.userid = ids[0];
	this.id = ids[1];
	
	//on click for the link surrounding the thumbnail
	YAHOO.util.Event.on(this.element.parentNode, 'click', this.makeCurrent, this, true);
};

UserGallery.NormalView.Thumb.prototype = {
	loadImage: function() {
		if (!this.loaded) {
			this.element.src = this.url;
		}
		this.loaded = true;
	},
	makeCurrent: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		UserGallery.FilmView.setCurrentById(this.id);
	}
};
Overlord.assign({
	minion: "normal_view_thumb",
	load: function(element) {
		UserGallery.NormalView.thumbs.push(new UserGallery.NormalView.Thumb(element));
	}	
});

Overlord.assign({
	minion: "thumb_view_link",
	mouseover: UserGallery.NormalView.loadImages,
	focus: UserGallery.NormalView.loadImages,
	scope: UserGallery.NormalView
});