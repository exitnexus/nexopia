Paginator = function(element, options) {
	this.pages = YAHOO.util.Dom.get(element);
	//default width and height
	this.width = this.pages.offsetWidth;
	this.height = this.pages.offsetHeight;
	//apply options to the object to override defaults we have set
	for (var key in options) {
		this[key] = options[key];
	}
	
	var that = this;
	YAHOO.util.Dom.getElementsByClassName("page", this.pageTag, this.pages, function(page) {
		if (!that.pageList) {
			that.pageList = [];
		}
		that.pageList.push(page);
	});
	
	if (this.pageList) {
		this.hiddenPages = document.createElement("div");
		this.hiddenPages.style.display = "none";
		document.body.appendChild(this.hiddenPages);
		this.hideExtraPages();
		this.pages.style.width = this.width*3 + "px";
	} else {
		//figure out how many pages worth of width we need to add to bring ourselves to be the right height
		this.totalWidth = this.width;
		this.pages.style.width = this.width + "px";
		var previousHeight = this.pages.offsetHeight;
		while (this.pages.offsetHeight > this.height) {
			this.totalWidth += this.width;
			this.pages.style.width = this.totalWidth + "px";
			//avoid infinite loops
			if (previousHeight == this.pages.offsetHeight) {
				break;
			}
			previousHeight = this.pages.offsetHeight;
		}
	}
	
	//wrap it in a container so we can scroll it
	this.pagesContainer = document.createElement("div");
	this.pagesContainer.style.width = this.width + "px";
	this.pagesContainer.style.height = this.height + "px";
	this.pagesContainer.style.overflow = "hidden"; //we handle scrolling through javascript magic so scroll bars would be bad
	this.pages.parentNode.replaceChild(this.pagesContainer, this.pages);
	this.pagesContainer.appendChild(this.pages);
	YAHOO.util.Dom.addClass(this.pagesContainer, "paginator");
	this.pagesContainer.id = "paginator_"+Paginator.nextID++;
	Paginator.paginators[this.pagesContainer.id] = this;
	this.addControlBar();
};

Paginator.paginators = {};
Paginator.nextID = 0;

Paginator.IMG_UP = "<img src='" + Site.staticFilesURL + "/Core/images/arrow_up.gif'/>";
Paginator.IMG_DOWN = "<img src='" + Site.staticFilesURL + "/Core/images/arrow_down.gif'/>";
Paginator.IMG_LEFT = "<img src='" + Site.staticFilesURL + "/Core/images/arrow_left.gif'/>";
Paginator.IMG_RIGHT = "<img src='" + Site.staticFilesURL + "/Core/images/arrow_right.gif'/>";
Paginator.IMG_SELECTED_PAGE = "<img src='" + Site.staticFilesURL + "/Core/images/circle_on.gif'/>";
Paginator.IMG_UNSELECTED_PAGE = "<img src='" + Site.staticFilesURL + "/Core/images/circle_off.gif'/>";

Paginator.PAGING_BUTTONS_INNERHTML = "\
	<a href='javascript:void(0);' class='page_left'>"+Paginator.IMG_LEFT+"</a>\
	<a href='javascript:void(0);' class='page_right'>"+Paginator.IMG_RIGHT+"</a>\
";
Paginator.CONTROL_BAR_INNERHTML = "";

Paginator.prototype = {
	//properties can be overriden by including them in the options object
	duration: 0.5, //animation duration
	controlBar: null, //control bar element
	pagingButtons: null, //paging buttons element
	pageTag: null, //type of tag that pages are (if using page hinting)
	currentPage: 0, //don't override this
	hideExtraPages: function() {
		if (this.pageList) {
			for (var i=0; i<this.pageList.length; i++) {
				if (i!=this.currentPage) {
					this.hiddenPages.appendChild(this.pageList[i]);
				}
			}
			if (this.pagesContainer) {
				this.pagesContainer.scrollLeft = 0;
			}
		}
	},
	//Adds a control bar, if you set a controlBar element through options it will be used rather than creating a new one
	addControlBar: function() {
		if (!this.controlBar) {
			this.controlBar = document.createElement("div");
			YAHOO.util.Dom.addClass(this.controlBar, "control_bar");
			this.controlBar.innerHTML = Paginator.CONTROL_BAR_INNERHTML;
			this.insertAfter(this.controlBar, this.pagesContainer);
		} else {
			this.controlBar = YAHOO.util.Dom.get(this.controlBar);
		}
		this.addPageIndicator();
		this.addPagingButtons();
	},
	addPageIndicator: function() {
		this.pageIndicator = document.createElement("div");
		YAHOO.util.Dom.addClass(this.pageIndicator, "page_list");
		this.controlBar.appendChild(this.pageIndicator);
		var numberOfPages = this.numberOfPages();
		this.pageIndicator.appendChild(this.createNode(Paginator.IMG_SELECTED_PAGE));
		for (var i=1; i<numberOfPages; i++) {
			this.pageIndicator.appendChild(this.createNode(Paginator.IMG_UNSELECTED_PAGE));
		}
	},
	//Adds paging buttons to the control bar, if you pass pagingButtons through options it will be used rather than creating a new one
	addPagingButtons: function() {
		if (this.numberOfPages() == 1) {
			return; //No need for paging buttons if we only have 1 page.
		}
		if (!this.pagingButtons) {
			this.pagingButtons = document.createElement("div");
			YAHOO.util.Dom.addClass(this.pagingButtons, "paging_buttons");
			this.pagingButtons.innerHTML = Paginator.PAGING_BUTTONS_INNERHTML;
			this.controlBar.insertBefore(this.pagingButtons, this.controlBar.firstChild);
		} else {
			this.pagingButtons = YAHOO.util.Dom.get(this.pagingButtons);
		}
		var that = this;
		YAHOO.util.Dom.getElementsByClassName("page_right", "a", this.pagingButtons, function(element) {
			YAHOO.util.Event.on(element, "click", function() {
				this.pageRight();
			}, that, true);
		});
		YAHOO.util.Dom.getElementsByClassName("page_left", "a", this.pagingButtons, function(element) {
			YAHOO.util.Event.on(element, "click", function() {
				this.pageLeft();
			}, that, true);
		});
	},
	showStrikeZones: function() {
		this.addStrikeZoneLeft();
		this.addStrikeZoneRight();
	},
	hideStrikeZones: function() {
		this.strikeZoneLeft.parentNode.removeChild(this.strikeZoneLeft);
		this.strikeZoneRight.parentNode.removeChild(this.strikeZoneRight);
	},
	addStrikeZoneLeft: function() {
		var strike = this.createStrikeZone();
		strike.firstChild.style.left = "-10px";
		this.pagesContainer.parentNode.insertBefore(strike, this.pagesContainer);
		YAHOO.util.Event.on(strike.firstChild, "mouseover", this.pageLeft, this, true);
		YAHOO.util.Dom.addClass(strike.firstChild, "left");
		this.strikeZoneLeft = strike;
	},
	addStrikeZoneRight: function() {
		var strike = this.createStrikeZone();
		strike.firstChild.style.left = this.width+"px";
		this.pagesContainer.parentNode.insertBefore(strike, this.pagesContainer);
		YAHOO.util.Event.on(strike.firstChild, "mouseover", this.pageRight, this, true);
		YAHOO.util.Dom.addClass(strike.firstChild, "right");
		this.strikeZoneRight = strike;
	},
	createStrikeZone: function() {
		var strike = document.createElement("div");
		strike.style.position = "absolute";
		var strike2 = document.createElement("div");
		strike2.style.height = this.height + "px";
		strike2.style.width = "10px";
		strike2.style.position = "relative";
		var dd = new YAHOO.util.DDTarget(strike2);
		YAHOO.util.Dom.addClass(strike2, "strike_zone");
		strike.appendChild(strike2);
		return strike;
	},
	addNextPage: function() {
		if (this.pageList) {
			this.insertAfter(this.pageList[this.nextPage()], this.pageList[this.currentPage]);
		}
	},
	nextPage: function() {
		return (this.currentPage+1)%this.numberOfPages();
	},
	addPreviousPage: function() {
		if (this.pageList) {
			this.pageList[this.currentPage].parentNode.insertBefore(this.pageList[this.previousPage()], this.pageList[this.currentPage]);
			this.pagesContainer.scrollLeft = this.width;
		}
	},
	previousPage: function() {
		return (this.numberOfPages()+this.currentPage-1)%this.numberOfPages();
	},
	pageRight: function(event, callback) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		if (this.currentPage == this.numberOfPages()-1) {
			return; //don't wrap
		}
		//if we're already moving instantly finish it so we don't screw up positioning
		if (this.currentAnimation) {
			this.currentAnimation.stop(true);
		}
		if (this.pageList) {
			this.addNextPage();
		}
		this.currentAnimation = new YAHOO.util.Scroll(this.pagesContainer, { scroll: { by: [this.width, 0] } }, this.duration, YAHOO.util.Easing.easeOutStrong);
		this.currentAnimation.onComplete.subscribe(this.finishAnimation, this, true);
		if (callback) {
			this.currentAnimation.onComplete.subscribe(callback);
		}
		this.currentAnimation.animate();
		this.pageIndicator.insertBefore(this.pageIndicator.lastChild, this.pageIndicator.firstChild);
		this.currentPage = this.nextPage();
	},
	pageLeft: function(event, callback) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		if (this.currentPage == 0) {
			return; //don't wrap
		}
		//if we're already moving instantly finish it so we don't screw up positioning
		if (this.currentAnimation) {
			this.currentAnimation.stop(true);
		}
		if (this.pageList) {
			this.addPreviousPage();
		}
		this.currentAnimation = new YAHOO.util.Scroll(this.pagesContainer, { scroll: { by: [-this.width, 0] } }, this.duration, YAHOO.util.Easing.easeOutStrong);
		this.currentAnimation.onComplete.subscribe(this.finishAnimation, this, true);
		if (callback) {
			this.currentAnimation.onComplete.subscribe(callback);
		}
		this.currentAnimation.animate();
		this.pageIndicator.appendChild(this.pageIndicator.firstChild);
		this.currentPage = this.previousPage();
	},
	finishAnimation: function() {
		this.hideExtraPages();
		this.currentAnimation = null;
	},
	currentAnimation: null,
	numberOfPages: function() {
		if (this.pageList) {
			return this.pageList.length;
		} else {
			return this.totalWidth/this.width;
		}
	},
	//this behavior should be extracted somewhere
	//does the same as node.insertBefore except inserts after
	insertAfter: function(newChild, oldChild) {
		if (oldChild.nextSibling) {
			oldChild.parentNode.insertBefore(newChild, oldChild.nextSibling);
		} else {
			oldChild.parentNode.appendChild(newChild);
		}
	},
	createNode: function(inner) {
		var temp = document.createElement("temp");
		temp.innerHTML = inner;
		return temp.firstChild;
	}
};