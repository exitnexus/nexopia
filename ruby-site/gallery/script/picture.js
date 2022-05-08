//require film_view.js
Overlord.assign({
	minion:"film_view:current_picture",
	load: function(element) {
		UserGallery.FilmView.currentPicture = new UserGallery.FilmView.Picture(element);
	},
	order: -2
});

/* !here */
UserGallery.FilmView.Picture = function(pictureElement) {
	this.picture = YAHOO.util.Dom.get(pictureElement);
	//the picture id will be the server side id of the image
	this.pictureID = UserGallery.parsePath(this.picture.src)[1];
	
	// Resize the 'photo_full_view' to be the width it needs to be.
	YAHOO.util.Dom.setStyle('photo_full_view', 'width', YAHOO.util.Dom.get("current_picture").width+'px');

	this.leftPanel = document.getElementById('left_arrow_panel');
	if(this.leftPanel) {
		YAHOO.util.Event.on(this.leftPanel, 'click', UserGallery.FilmView.previous, UserGallery.FilmView, true);
		this.leftArrow = document.getElementById('left_arrow_img');
		this.leftArrow.originalSource = this.leftArrow.src;
		this.leftArrow.hoverSource = this.leftArrow.getAttribute('hover_src');
		YAHOO.util.Event.on(this.leftPanel, 'mouseover', this.highlightLeft, this, true);
		YAHOO.util.Event.on(this.leftPanel, 'mouseout', this.unhighlightLeft, this, true);
	}
	this.rightPanel = document.getElementById('right_arrow_panel');
	if(this.rightPanel) {
		YAHOO.util.Event.on(this.rightPanel, "click", UserGallery.FilmView.next, UserGallery.FilmView, true);
		this.rightArrow = document.getElementById('right_arrow_img');
		this.rightArrow.originalSource = this.rightArrow.src;
		this.rightArrow.hoverSource = this.rightArrow.getAttribute('hover_src');
		YAHOO.util.Event.on(this.rightPanel, 'mouseover', this.highlightRight, this, true);
		YAHOO.util.Event.on(this.rightPanel, 'mouseout', this.unhighlightRight, this, true);
	}

	this.initArrows();
	YAHOO.util.Dom.get("current_picture").loaded = true;
};

UserGallery.FilmView.Picture.prototype = {
	//change the picture being displayed, takes a dom node
	setPicture: function(imgTag) {
		this.picture.parentNode.replaceChild(imgTag, this.picture);
		this.picture = imgTag;
		this.pictureID = UserGallery.parsePath(imgTag.src)[1];
		// Resize the 'photo_full_view' to be the width it needs to be.
		YAHOO.util.Dom.setStyle('photo_full_view', 'width', imgTag.width+'px');
		this.initArrows();
		
		YAHOO.util.Event.on(imgTag, 'load', function(e, imgTag)
		{
			YAHOO.util.Dom.setStyle('photo_full_view', 'width', imgTag.width+'px');
		}, imgTag);

		Nexopia.Utilities.withImage('current_picture', {
			load: function(img) {
				this.initArrows();
			},
			scope: UserGallery.FilmView.currentPicture
		});
	}, 
	
	initArrows: function() {
		if(this.leftPanel) {
			YAHOO.util.Dom.setStyle('left_arrow_panel', 'height', YAHOO.util.Dom.get("current_picture").height+'px');
			YAHOO.util.Dom.setStyle('left_arrow_panel', 'left', ((YAHOO.util.Dom.get("current_picture").width/2)-360)+'px');
			YAHOO.util.Dom.setStyle(this.leftArrow, 'top', YAHOO.util.Dom.get("current_picture").height/2+'px');
		}
		if(this.rightPanel) {
			YAHOO.util.Dom.setStyle('right_arrow_panel', 'height', YAHOO.util.Dom.get("current_picture").height+'px');
			YAHOO.util.Dom.setStyle('right_arrow_panel', 'left', (YAHOO.util.Dom.get("current_picture").width/2)+'px');
			YAHOO.util.Dom.setStyle(this.rightArrow, 'top', YAHOO.util.Dom.get("current_picture").height/2+'px');
		}
	},

	highlightRight: function() {
		YAHOO.util.Dom.addClass(this.rightPanel, 'hover');
		this.rightArrow.src = this.rightArrow.hoverSource;
	},
	highlightLeft: function() {
		YAHOO.util.Dom.addClass(this.leftPanel, 'hover');
		this.leftArrow.src = this.leftArrow.hoverSource;
	},
	unhighlightRight: function() {
		YAHOO.util.Dom.removeClass(this.rightPanel, 'hover');
		this.rightArrow.src = this.rightArrow.originalSource;
	},
	unhighlightLeft: function() {
		YAHOO.util.Dom.removeClass(this.leftPanel, 'hover');
		this.leftArrow.src = this.leftArrow.originalSource;
	}
};
