//require film_view.js
//initialization of individual thumb objects is taken care of by the script manager
Overlord.assign({
	minion: "gallery_film_strip:thumb",
	load: function(element) {
		new UserGallery.FilmView.Thumb(element);
	},
	unload: function(element) {
		UserGallery.FilmView.unregisterThumb(element.thumbID);
	}
});

Overlord.assign({
	minion: "gallery_film_strip",
	load: function(element) {
		UserGallery.FilmView.preloadImages(1,1);
	},
	order: 1	
});

UserGallery.FilmView.Thumb = function(element) {
	this.element = YAHOO.util.Dom.get(element);
	YAHOO.lang.augmentObject(this, Nexopia.json(element));
	this.img = this.element.firstChild.firstChild; //two tags down to get the img tag, this is fragile but fast
	this.calculateIDs(); //pulls the userid and id from the img element and stores them
	this.element.thumbID = this.id; //shortcut to the id primarily used for unregistering thumbs
	UserGallery.FilmView.registerThumb(this);
	//if you click on an image, make it the current main image
	YAHOO.util.Event.on(element, 'click', this.makeCurrent, this, true);
};

UserGallery.FilmView.Thumb.prototype = {
	//call out to UserGallery.FilmView to make this thumb the current main image
	makeCurrent: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		UserGallery.FilmView.setCurrent(this);
	},
	//parses the src attribute from this.img and stores this.userid and this.id
	calculateIDs: function() {
		var src = null;
		if (this.img.src) {
			src = this.img.src;
		} else {
			src = this.img.attributes.url.value;
		}
		var ids = UserGallery.parsePath(src);
		this.userid = ids[0];
		this.id = ids[1];
	},
	getIndex: function() {
		var index = 0;
		var node = this.element;
		while (node.previousSibling != null) {
			if (node.className == "thumb") {
				index++;
			}
			node = node.previousSibling;
		}
		return index;
	}
};