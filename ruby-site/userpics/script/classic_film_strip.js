ClassicFilmStrip = {
	init: function() {
		this.element = document.getElementById('classic_film_strip');
		if (!this.element) {
			return;
		}
		this.pictures = Nexopia.json(this.element);
		this.currentPicture = 1;
		this.currentPictureElement = YAHOO.util.Dom.getElementsByClassName('current_profile_picture', 'span', this.element)[0];
		this.profilePicture = YAHOO.util.Dom.getElementsByClassName('profile_picture', 'img', this.element)[0];
		this.profilePictureDescription = YAHOO.util.Dom.getElementsByClassName('profile_picture_description', 'span', this.element)[0];
		this.truncateDescription();
		this.leftPanel = YAHOO.util.Dom.getElementsByClassName('left_panel', 'div', this.element)[0];
		if (this.leftPanel) {
			this.leftArrow = this.leftPanel.firstChild.firstChild;
			this.leftArrow.originalSource = this.leftArrow.src;
			this.leftArrow.hoverSource = this.leftArrow.getAttribute('hover_src');
			YAHOO.util.Event.on(this.leftPanel, 'click', this.previousPicture, this, true);
			YAHOO.util.Event.on(this.leftPanel, 'mouseover', this.highlightLeft, this, true);
			YAHOO.util.Event.on(this.leftPanel, 'mouseout', this.unhighlightLeft, this, true);
		}
		this.rightPanel = YAHOO.util.Dom.getElementsByClassName('right_panel', 'div', this.element)[0];
		if (this.rightPanel) {
			this.rightArrow = this.rightPanel.firstChild.firstChild;
			this.rightArrow.originalSource = this.rightArrow.src;
			this.rightArrow.hoverSource = this.rightArrow.getAttribute('hover_src');
			YAHOO.util.Event.on(this.rightPanel, 'click', this.nextPicture, this, true);
			YAHOO.util.Event.on(this.rightPanel, 'mouseover', this.highlightRight, this, true);
			YAHOO.util.Event.on(this.rightPanel, 'mouseout', this.unhighlightRight, this, true);
		}
	},
	truncateDescription: function() {
		//we use a fixed length to make this as fast as possible
		if (this.profilePictureDescription.offsetHeight > 20) {
			var originalText = this.profilePictureDescription.innerHTML;
			var shortenedText = originalText.substr(0,40);
			shortenedText = shortenedText.replace(/[\s\,\.\?\:]+$/,"");
			shortenedText += "...";
			this.profilePictureDescription.innerHTML = shortenedText;
		}
	},
	nextPicture: function() {
		this.setCurrentPicture(this.currentPicture+1);
	},
	previousPicture: function() {
		this.setCurrentPicture(this.currentPicture-1);
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
	},
	//handles wrapping of this.currentPicture and updates the display
	setCurrentPicture: function(picture) {
		this.currentPicture = picture;
		if (this.currentPicture > this.pictures.length) {
			this.currentPicture = 1;
		} else if (this.currentPicture < 1) {
			this.currentPicture = this.pictures.length;
		}
		this.currentPictureElement.innerHTML = this.currentPicture;
		this.profilePicture.src = this.pictures[this.currentPicture-1][1];
		this.profilePictureDescription.innerHTML = Nexopia.Utilities.escapeHTML(this.pictures[this.currentPicture-1][0]);
		this.truncateDescription();
	}
};

Overlord.assign({
	minion: "userpics:classic_film_strip",
	load: ClassicFilmStrip.init,
	scope: ClassicFilmStrip
});