Truncator = function(element, options) {
	if (options) {
		YAHOO.lang.augmentObject(this, options, true);
	}
	
	this.element = YAHOO.util.Dom.get(element);

	this.shortenedText = this.element.innerHTML;
	this.originalText = this.element.innerHTML;
	this.words = this.originalText.split(/\s/);
	
	if (!this.width) {
		this.width = this.element.offsetWidth;
	}
	if (!this.height) {
		this.height = this.element.offsetHeight;
	}
	
	this.truncate();
};

Truncator.prototype = {
	tooltip: true, //add a tooltip with the original text content
	expandable: false, //the content can be expanded by clicking on it
	collapsible: false, //the expanded content can be collapsed by clicking on it
	suffix: "...", //what should be added to the end of truncated text
	expandedSuffix: "", //if there should be a suffix on the expanded text this is it
	fixed_length: -1, // if we want truncator to be fast and immediately cut to a given size use this option.
	
	toggleExpanded: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		if (this.collapsed) {
			this.expand();
		} else if (this.collapsible) {
			this.collapse();
		}
	},
	expand: function() {
		this.element.innerHTML = this.originalText + this.expandedSuffix;
		this.collapsed = false;
	},
	collapse: function() {
		this.element.innerHTML = this.shortenedText;
		this.collapsed = true;
	},
	//private - don't overwrite this stuff in options
	truncate: function() {
		var originalVisibility = this.element.style.visibility;
		var originalPosition = this.element.style.position;
		var originalDisplay = this.element.style.display;
		var originalWidth = this.element.style.width;
		var originalHeight = this.element.style.height;
		var originalOverflow = this.element.style.overflow;

		YAHOO.util.Dom.setStyle(this.element, 'visibility', 'hidden');
		YAHOO.util.Dom.setStyle(this.element, 'position', 'absolute');
		YAHOO.util.Dom.setStyle(this.element, 'display', 'block');
		YAHOO.util.Dom.setStyle(this.element, 'width', this.width + "px");
		YAHOO.util.Dom.setStyle(this.element, 'height', this.height + "px");
		YAHOO.util.Dom.setStyle(this.element, 'overflow', 'auto');

		// if we're not dealing with a fixed length we'll do this dynamically.
		if (this.fixed_length == -1) {
			while (this.shortenedText.length > this.suffix.length && 
					(this.element.clientWidth < this.element.scrollWidth || this.element.clientHeight < this.element.scrollHeight)) {
				this.shorten();
			}
		} else {
			this.quick_shorten();
		}

		YAHOO.util.Dom.setStyle(this.element, 'visibility', originalVisibility);
		YAHOO.util.Dom.setStyle(this.element, 'position', originalPosition);
		YAHOO.util.Dom.setStyle(this.element, 'display', originalDisplay);
		YAHOO.util.Dom.setStyle(this.element, 'width', originalWidth);
		YAHOO.util.Dom.setStyle(this.element, 'height', originalHeight);
		YAHOO.util.Dom.setStyle(this.element, 'overflow', originalOverflow);
		if (this.shortened) {
			if (this.tooltip) {
				this.addTooltip();
			}
			if (this.expandable) {
				this.makeExpandable();
			}
		}
	},

	quick_shorten: function() {
		if (this.shortenedText.length > this.fixed_length) {
			this.shortened = true;
			this.shortenedText = this.shortenedText.substr(0,this.fixed_length);

			this.shortenedText = this.shortenedText.replace(/[\s\,\.\?\:]+$/,"");
			this.shortenedText += this.suffix;
			this.element.innerHTML = this.shortenedText;
		}
	},

	shorten: function() {
		this.shortened = true;
		if (this.words.length > 1) {
			this.shortenedText = this.shortenedText.substr(0, this.shortenedText.lastIndexOf(this.words.pop().replace(/[\s\,\.\?\:]+$/,"")));
		} else {
			if (this.shortenedText.lastIndexOf(this.suffix) > 0) {
				this.shortenedText = this.shortenedText.substr(0,this.shortenedText.lastIndexOf(this.suffix)-1);
			} else {
				this.shortenedText = this.shortenedText.substr(0,this.shortenedText.length-1);
			}
		}
		this.shortenedText = this.shortenedText.replace(/[\s\,\.\?\:]+$/,"");
		this.shortenedText += this.suffix;
		this.element.innerHTML = this.shortenedText;
	},
	addTooltip: function() {
		new YAHOO.widget.Tooltip(document.createElement("div"), {
			text: this.originalText,
			context: this.element,
			width: this.width + "px"
		});
	},
	makeExpandable: function() {
		YAHOO.util.Event.on(this.element, 'click', this.toggleExpanded, this, true);
	},
	shortened: false,
	collapsed: true
};