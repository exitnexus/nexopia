//To use dom manipulated paging wrap each page in an element with the class page
//To use custom forward/back buttons add the class page_forward and page_backward to them.
//To use custom positioned control bars add the class control_bar to the element that should contain
//control bar information
Paginator = function(element, options) {
	this.element = YAHOO.util.Dom.get(element);
	//we need an element to do anything
	if (!this.element) {
		return;
	}
	
	this.onPageChange = new YAHOO.util.CustomEvent("onPageChange");
	
	var color = this.deducePaginatorIconColor();
	
	this.img_up = "<img class='color_icon' src='" + Site.coloredImgURL(color) + "/paginator/images/arrow_up.gif'/>";
	this.img_down = "<img class='color_icon' src='" + Site.coloredImgURL(color) + "/paginator/images/arrow_down.gif'/>";
	this.img_left = "<img class='color_icon' src='" + Site.coloredImgURL(color) + "/paginator/images/arrow_left.gif'/>";
	this.img_right = "<img class='color_icon' src='" + Site.coloredImgURL(color) + "/paginator/images/arrow_right.gif'/>";
	this.img_selected_page = "<img class='color_icon' src='" + Site.coloredImgURL(color) + "/core/images/circle_on.gif'/>";
	this.img_unselected_page = "<img class='color_icon' src='" + Site.coloredImgURL(color) + "/core/images/circle_off.gif'/>";

	this.horizontal_paging_buttons_innerhtml = "\
		<a href='javascript:void(0);' class='page_backward'>"+this.img_left+"</a>\
		<a href='javascript:void(0);' class='page_forward'>"+this.img_right+"</a>\
	";
	this.vertical_paging_buttons_innerhtml = "\
		<a href='javascript:void(0);' class='page_forward'>"+this.img_down+"</a>\
		<a href='javascript:void(0);' class='page_backward'>"+this.img_up+"</a>\
	";

	//if the element passed in contains an element with class pages that is the root pages element
	//otherwise element passed in is the pages element
	this.pages = YAHOO.util.Dom.getElementsByClassName("pages", null, this.element);
	if (this.pages.length == 0) {
		this.pages = this.element;
	} else {
		this.pages = this.pages[0];
	}
	
	//apply options to the object to override defaults we have set
	for (var key in options) {
		this[key] = options[key];
	}
	
	this.initializeOrientation();

	//default page size
	if (!this[this.primaryDimension]) {
		this[this.primaryDimension] = this.pages["offset" + this.capitalizedPrimaryDimension];
	}
	
	this.initializeDomPaging(); //if there are elements with class page they will be used to do dom based paging

	this.initializeAjaxPaging(); //if we have options available to load missing pages, setup everything necessary to load them
	
	//If we have a page list then we have already generated where page breaks should occur and
	//we just need to use DOM manipulation to pull pages into place
	if (this.pageList) {
		this.hiddenPages = document.createElement("div");
		this.hiddenPages.style.display = "none";
		document.body.appendChild(this.hiddenPages);
		this.hideExtraPages();
		for(var i = 0; i < this.pageList.length; i++){
			if (this.pageList[i].loaded) {
				this.pageList[i].page.style[this.primaryDimension] = this[this.primaryDimension] + "px";
			}
		}
		// HACK: dirty dirty IE6 hack
		// This will basically disable the animation for sliding between pages.
		// IE6 had a problem rendering the page properly after an animation, thus, hack.
		if (YAHOO.env.ua.ie > 0 && YAHOO.env.ua.ie < 7) {
			this.pages.style[this.primaryDimension] = this[this.primaryDimension] + "px";
		} else {
			this.pages.style[this.primaryDimension] = this[this.primaryDimension]*3 + "px";
		}
		
	} else { //if we don't have a page list then we assume we just have one dom element that we scroll within a frame for paging
		this.initializeSlidePaging();
	}


	//wrap it in a container so we can scroll it and wrap the wrapper in another div so we have a nice root node for everything
	this.pagesRoot = document.createElement("div");
	this.pagesContainer = document.createElement("div");
	this.pagesRoot.appendChild(this.pagesContainer);
	this.pagesContainer.style[this.primaryDimension] = this[this.primaryDimension] + "px";
	this.pagesContainer.style[this.secondaryDimension] = "auto";
	this.pagesContainer.style.overflow = "hidden"; //we handle scrolling through javascript magic so scroll bars would be bad
	this.pages.parentNode.replaceChild(this.pagesRoot, this.pages);
	this.pagesContainer.appendChild(this.pages);
	YAHOO.util.Dom.addClass(this.pagesRoot, "paginator");
	this.pagesRoot.setAttribute('minion_name', this.pagesRoot.getAttribute('minion_name') + " paginator");
	this.pagesRoot.id = "paginator_"+Paginator.nextID++;
	Paginator.paginators[this.pagesRoot.id] = this;
	
	//figure out which is our actual root
	if (YAHOO.util.Dom.isAncestor(this.pagesRoot, this.element)) {
		this.root = this.pagesRoot;
	} else {
		this.root = this.element;
	}
	
	//add paging buttons, page indicators, etc.
	this.addControlBar(this.currentPage);
	this.initializeCustomColors();	
};

Paginator.paginators = {};
Paginator.nextID = 0;

Paginator.CONTROL_BAR_INNERHTML = "";

Paginator.prototype = {
	//properties can be overriden by including them in the options object
	duration: 0.5, //animation duration
	pageTag: null, //type of tag that pages are (if using page hinting)
	orientation: "horizontal", //horizontal or vertical
	primaryDimension: "width", //don't override this
	pageIndicatorStyle: "dots", //dots or numbers
	currentPage: 0, //set this to change the starting page
	ajaxLoadURL: null, //set this to a url to use ajax requests to pull in future pages
	numPages: null, //this value is required with ajaxLoadURL, don't use it otherwise
	queueAnimation: 0, //Number of animations currently in the queue, shouldn't be set manually
	prefetchPages: 0, //Number of pages beyond the currently viewed one that should be loaded when viewing the current one
	pageGrouping: 1, //Number of pages that should be fetched at a time
	ajaxGetParams: [], //Other params to be affixed to the ajaxLoadURL, they should be complete strings excluding ? or & characters, passed in as an array
	
	// If the given page isn't loaded yet get it using the load method for that object
	getPageAt: function(index) {
		for (var i=0; i<this.prefetchPages+1; i++) {
			if (this.pageList[this.currentPage+i] && !this.pageList[this.currentPage+i].loaded) {
				this.pageList[this.currentPage+i].load();
				//assume that if we find one page that we need to load we should stop
				//because loading it should deal with loading enough pages to keep us moving
				break;
			}
		}
		return this.pageList[index].page;
	},
	
	
	hideExtraPages: function() {
		if (this.pageList) {
			for (var i = 0; i < this.pageList.length; i++) {
				if (i != this.currentPage) {
					if (this.pageList[i].loaded) {
						this.hiddenPages.appendChild(this.getPageAt(i));
					}
				}
			}
			if (this.pagesContainer) {
				if (this.orientation == "horizontal") {
					this.pagesContainer.scrollLeft = 0;
				} else {
					this.pagesContainer.scrollTop = 0;
				}
			}
		}
	},
	initializeOrientation: function() {
		//setup dimensions (width or height) these are used to access attributes by key
		//(eg. someElement[this.primaryDimension] to access it's width or height)
		if (this.orientation == "horizontal") {
			this.primaryDimension = "width";
			this.secondaryDimension = "height";
			this.capitalizedPrimaryDimension = "Width";
			this.capitalizedSecondaryDimension = "Height";
		} else {
			this.primaryDimension = "height";
			this.secondaryDimension = "width";
			this.capitalizedPrimaryDimension = "Height";
			this.capitalizedSecondaryDimension = "Width";
		}
	},
	initializeDomPaging: function() {
		var that = this;
		YAHOO.util.Dom.getElementsByClassName("page", this.pageTag, this.pages, function(page) {
			page.style[that.primaryDimension] = that[that.primaryDimension] + "px";

			if (!that.pageList) {
				that.pageList = [];
			}
			that.pageList.push({
				page: page,
				loaded: true,
				load: function(){ alert("Bad!"); }
			});
		});
	},
	initializeAjaxPaging: function() {
		if (this.ajaxLoadURL && this.numPages > 0){
			if (!this.pageList) {
				this.pageList = [];
			}
			var strings = [];
			for (var i = 0; i < (this.numPages); i++){
				if (i != this.currentPage){
					var that = this;
					var div = document.createElement("div");
					div.style[that.primaryDimension]=that[that.primaryDimension] + "px";
					var newPage = {
						index: i,
						load: function(){
							if (that.pageGrouping == 1) {
								new AJAXDiv([this], that.ajaxLoadURL, that.generateGetParams(this.index));
								return div;
							} else {
								var pagesToFetch = [];
								for (var i=0; i<that.pageGrouping; i++) {
									if (that.numPages > this.index+i) {
										pagesToFetch.push(that.pageList[this.index+i]);
										that.pageList[this.index+i].loaded = true;
										that.pageList[this.index+i].load = null;
									}
								}
								new AJAXDiv(pagesToFetch, that.ajaxLoadURL, ["page_list=" + escape(pagesToFetch.join(','))]);
								return this.page;
							}
						},
						loaded: false,
						page: div,
						toString: function() {return this.index.toString();}
					};
					if (i < this.currentPage) {
						this.pageList.unshift(newPage);
					} else {
						this.pageList.push(newPage);
					}
				}
			}
		} 
	},
	generateGetParams: function(pageNum)
	{
		var temp_arr = [];
		for(var i=0; i < this.ajaxGetParams.length; i++)
		{
			temp_arr.push(this.ajaxGetParams[i]);
		}
		temp_arr.push("page="+pageNum);
		
		return temp_arr;
	},
	reinitializeSlidePaging: function() {
		this.initializeSlidePaging();
		this.correctSlideForCurrentPage();
		this.updateControlBar();
	},
	correctSlideForCurrentPage: function() {
		if (this.orientation == "horizontal") {
			this.pagesContainer.scrollLeft = this.currentPage * this.width;
		} else {
			this.pagesContainer.scrollTop = this.currentPage * this.height;
		}
	},
	initializeSlidePaging: function() {
		//figure out how many pages worth of width we need to add to bring ourselves to be the right height
		if (this.orientation == "horizontal") {
			this.totalLength = this.width;
			this.pages.style.width = this.width + "px";
			var previousHeight = this.pages.offsetHeight;
			var repetition = false;
			while (this.pages.offsetHeight > this.height) {
				this.totalLength *= 2;
				this.pages.style.width = this.totalLength + "px";
				//avoid infinite loops
				if (previousHeight == this.pages.offsetHeight) {
					if (repetition) {
						break;
					}
					repetition = true;
				} else {
					repetition = false;
				}
				previousHeight = this.pages.offsetHeight;
			}
			while (this.height >= this.pages.offsetHeight && this.totalLength >= this.width) {
				this.totalLength -= this.width;
				this.pages.style.width = this.totalLength + "px";
			}
			this.totalLength += this.width;
			this.pages.style.width = this.totalLength + "px";
		} else { //vertical length calculation
			var originalPosition = this.pages.style.position;
			this.pages.style.position = "absolute";
			this.totalLength = this.pages.offsetHeight;
			this.pages.style.position = originalPosition;
		}
	},
	initializeCustomColors: function() {
		// elements = YAHOO.util.Dom.getElementsByClassName("custom_color_icon", null, this.root);
		// init_custom_color_icons(elements);
	},
	registerPageButtons: function(){
		//register hooks to any button
		var that = this;
		YAHOO.util.Dom.getElementsByClassName("page_forward", null, this.root, function(element) {
			YAHOO.util.Event.on(element, "click", function(event) {
				YAHOO.util.Event.preventDefault(event);
				this.pageForward();
			}, that, true);
		});
		YAHOO.util.Dom.getElementsByClassName("page_backward", null,  this.root, function(element) {
			YAHOO.util.Event.on(element, "click", function(event) {
				YAHOO.util.Event.preventDefault(event);
				this.pageBackward();
			}, that, true);
		});
	},
	//Adds a control bar, if you have any subelements of the root element that have class control_bar
	//they will be used, otherwise a new element will be created
	addControlBar: function(currentPage) {
		var controlBars = YAHOO.util.Dom.getElementsByClassName("control_bar", null, this.root);
		var controlBar = null;
		if (controlBars.length == 0) {
			//legacy implementation of how to specify control bars, takes top, top and bottom, or defaults to just bottom
			if (this.controls == "top" || this.controls == "top and bottom"){
				controlBar = document.createElement("div");
				YAHOO.util.Dom.addClass(controlBar, "control_bar");
				controlBar.innerHTML = Paginator.CONTROL_BAR_INNERHTML;
				controlBars.push(controlBar);
				this.pagesContainer.parentNode.insertBefore(controlBar, this.pagesContainer);
			}
			if (this.controls != "top") {
				controlBar = document.createElement("div");
				YAHOO.util.Dom.addClass(controlBar, "control_bar");
				controlBar.innerHTML = Paginator.CONTROL_BAR_INNERHTML;
				this.insertAfter(controlBar, this.pagesContainer);
				controlBars.push(controlBar);
			}
		}
		for (var i=0; i<controlBars.length; i++) {
			controlBar = controlBars[i];
			this.addPagingButtons(controlBar);
			this.addPageIndicator(controlBar, currentPage);

		}
		this.registerPageButtons();
	},
	addPageIndicator: function(controlBar, index) {
		var pageIndicator = document.createElement("div");
		YAHOO.util.Dom.addClass(pageIndicator, "page_list");
		controlBar.appendChild(pageIndicator);
		
		switch (this.pageIndicatorStyle) {
			case "numbers":
				this.addNumbersPageIndicator(pageIndicator, index);
				break;
			case "dots":
				this.addDotsPageIndicator(pageIndicator, index);
				break;
			default:
		}
	},
	//The default page indicator, uses dots to indicate number of pages and current page
	addDotsPageIndicator: function(pageIndicator, index) {
		for (var i=0; i<this.numberOfPages(); i++) {
			if (i == index){
				pageIndicator.appendChild(this.createNode(this.img_selected_page));
			} else {
				pageIndicator.appendChild(this.createNode(this.img_unselected_page));
			}
		}
	},
	//Voodoo that adds numbers as a page indicator, see Thomas if you need to understand it
	addNumbersPageIndicator: function(pageIndicator, index) {
		var list = {};
		list[1] = true;
		list[2] = true;
		list[index] = true;
		list[index + 1] = true;
		list[index + 2] = true;
		var end = this.numberOfPages();
		list[end - 1] = true;
		list[end] = true;
		var last_i = 0;
		for (var i in list) {
			i = parseInt(i, 10);
			if (i > 0 && i <= end){
				if (i != (last_i + 1)) {
					pageIndicator.appendChild(this.createNode("..."));
				}
				last_i = i;
					
				if (i == (index + 1)){
					pageIndicator.appendChild(this.createNode("<span class='selected'>" + (i) + " </span>"));
				} else {
					pageIndicator.appendChild(this.createNode("<a href='#'>" + (i) + " </a>"));
				}
			}
		}
	},
	//Adds paging buttons to the control bar, if you pass pagingButtons through options it will be used rather than creating a new one
	addPagingButtons: function(controlBar) {
		//add buttons if they aren't already on the page and we have multiple pages
		if (this.numberOfPages() > 1 && 
				!YAHOO.util.Dom.getElementsByClassName("page_backward", null, this.element).length > 0 && 
				!YAHOO.util.Dom.getElementsByClassName("page_forward", null, this.element).length > 0) {
			var pagingButtons = document.createElement("div");
			YAHOO.util.Dom.addClass(pagingButtons, "paging_buttons");
			if (this.orientation == "horizontal") {
				pagingButtons.innerHTML = this.horizontal_paging_buttons_innerhtml;
			} else {
				pagingButtons.innerHTML = this.vertical_paging_buttons_innerhtml;
			}
			controlBar.insertBefore(pagingButtons, controlBar.firstChild);
			
		}

	},
	showStrikeZones: function() {
		if (this.numberOfPages() > 1) {
			this.addStrikeZoneLeft();
			this.addStrikeZoneRight();
		}
	},
	hideStrikeZones: function() {
		if (this.strikeZoneLeft && this.strikeZoneLeft.parentNode) {
			this.strikeZoneLeft.parentNode.removeChild(this.strikeZoneLeft);
		}
		if (this.strikeZoneRight && this.strikeZoneRight.parentNode) {
			this.strikeZoneRight.parentNode.removeChild(this.strikeZoneRight);
		}
	},
	addStrikeZoneLeft: function() {
		var strike = this.strikeZoneLeft;
		if (!strike) {
			strike = this.createStrikeZone();
		}
		strike.firstChild.style.left = "-14px";
		YAHOO.util.Dom.addClass(strike.firstChild, "strike_zone_left");
		this.pagesContainer.parentNode.insertBefore(strike, this.pagesContainer);
		YAHOO.util.Event.on(strike.firstChild, "mouseover", this.pageBackward, this, true);
		YAHOO.util.Dom.addClass(strike.firstChild, "left");
		this.strikeZoneLeft = strike;
	},
	addStrikeZoneRight: function() {
		var strike = this.strikeZoneRight;
		if (!strike) {
			strike = this.createStrikeZone();
		}
		strike.firstChild.style.left = this.width-4+"px";
		YAHOO.util.Dom.addClass(strike.firstChild, "strike_zone_right");
		this.pagesContainer.parentNode.insertBefore(strike, this.pagesContainer);
		YAHOO.util.Event.on(strike.firstChild, "mouseover", this.pageForward, this, true);
		YAHOO.util.Dom.addClass(strike.firstChild, "right");
		this.strikeZoneRight = strike;
	},
	createStrikeZone: function() {
		var strike = document.createElement("div");
		strike.style.position = "absolute";
		var strike2 = document.createElement("div");
		strike2.style.height = this.height + "px";
		strike2.style.width = "20px";
		strike2.style.position = "relative";
		strike2.style.zIndex = "1000";
		var dd = new YAHOO.util.DDTarget(strike2);
		strike.appendChild(strike2);
		return strike;
	},
	addNextPage: function() {
		if (this.pageList) {
			var current = this.getPageAt(this.currentPage);
			var next = this.getPageAt(this.nextPage());
			this.insertAfter(next, current);
		}
	},
	nextPage: function() {
		return (this.currentPage+1)%this.numberOfPages();
	},
	addPreviousPage: function() {
		if (this.pageList) {
			this.getPageAt(this.currentPage).parentNode.insertBefore(this.getPageAt(this.previousPage()), this.getPageAt(this.currentPage));
			if (this.orientation == "horizontal") {
				this.pagesContainer.scrollLeft = this.width;
			} else {
				this.pagesContainer.scrollTop = this.height;
			}
		}
	},
	previousPage: function() {
		return (this.numberOfPages()+this.currentPage-1)%this.numberOfPages();
	},
	pageForward: function(event, callback) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		if (this.currentPage == this.numberOfPages()-1) {
			return; //don't wrap
		}
		this.normalPageForward(callback);
	},
	normalPageForward: function(event, callback) {
		var oldCurrentPage = this.currentPage;
		//if we're already moving instantly finish it so we don't screw up positioning
		//XXX: ACTUALLY if we're already moving just return and make them wait because firefox 3 is fucking up.
		//This needs a better solution but this prevents major breakage until we get one
		if (this.currentAnimation) {
			if (!callback) {
				this.queueAnimation++;
			}
			return;
			// this.currentAnimation.stop(true);
		}
		if (this.pageList) {
			this.addNextPage();
		}
		if (this.orientation == "horizontal") {
			this.currentAnimation = new YAHOO.util.Scroll(this.pagesContainer, { scroll: { by: [this.width, 0] } }, this.duration);
		} else {
			this.currentAnimation = new YAHOO.util.Scroll(this.pagesContainer, { scroll: { by: [0, this.height] } }, this.duration);
		}
		this.currentAnimation.onComplete.subscribe(this.finishAnimation, this, true);
		if (this.beforePageCallback) {
			this.beforePageCallback();
		}
		if (callback) {
			this.currentAnimation.onComplete.subscribe(callback);
		}
		if (this.afterPageCallback) {
			this.currentAnimation.onComplete.subscribe(this.afterPageCallback, this, true);
		}
				
		this.currentAnimation.animate();
		this.currentPage = this.nextPage();
		this.updateControlBar();
		
		this.onPageChange.fire({page: this.currentPage, previousPage: oldCurrentPage});
	},
	pageBackward: function(event, callback) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		if (this.currentPage == 0) {
			return; //don't wrap
		}
		this.normalPageBackward(callback);
	},
	normalPageBackward: function(callback) {
		var oldCurrentPage = this.currentPage;
		//if we're already moving instantly finish it so we don't screw up positioning
		//XXX: ACTUALLY if we're already moving just return and make them wait because firefox 3 is fucking up.
		//This needs a better solution but this prevents major breakage until we get one
		if (this.currentAnimation) {
			if (!callback) {
				this.queueAnimation--;
			}
			return;
			// this.currentAnimation.stop(true);
		}

		if (this.pageList) {
			this.addPreviousPage();
		}
		if (this.orientation == "horizontal") {
			this.currentAnimation = new YAHOO.util.Scroll(this.pagesContainer, { scroll: { by: [-this.width, 0] } }, this.duration);
		} else {
			this.currentAnimation = new YAHOO.util.Scroll(this.pagesContainer, { scroll: { by: [0, -this.height] } }, this.duration);
		}
		this.currentAnimation.onComplete.subscribe(this.finishAnimation, this, true);
		if (this.beforePageCallback) {
			this.beforePageCallback();
		}
		if (callback) {
			this.currentAnimation.onComplete.subscribe(callback);
		}
		if (this.afterPageCallback) {
			this.currentAnimation.onComplete.subscribe(this.afterPageCallback, this, true);
		}
		this.currentAnimation.animate();
		this.currentPage = this.previousPage();
		this.updateControlBar();
		
		this.onPageChange.fire({page: this.currentPage, previousPage: oldCurrentPage});
	},
	
	// Update the little dots at the bottom of the page that tell us what page we're on.
	updateControlBar: function(page) {
		if (!page) {
			page = this.currentPage;
		}
		var that = this;
		YAHOO.util.Dom.getElementsByClassName("control_bar", "div", this.root, function(element) {
			YAHOO.util.Dom.getElementsByClassName("page_list", "div", element, function(child){
				child.parentNode.removeChild(child);
			});
			that.addPageIndicator(element, page);
		});	
		this.initializeCustomColors();
	},
	finishAnimation: function() {
		this.hideExtraPages();
		this.currentAnimation = null;
		if (this.queueAnimation > 0) {
			this.queueAnimation--;
			this.pageForward();
		} else if (this.queueAnimation < 0) {
			this.queueAnimation++;
			this.pageBackward();
		}
		this.initializeCustomColors();
	},
	currentAnimation: null,
	numberOfPages: function() {
		if (this.pageList) {
			return this.pageList.length;
		} else {
			return this.totalLength/this[this.primaryDimension];
		}
	},
	//Returns the DOM node for a given page, used primarily by things outside of paginator that need to modify offscreen pages
	//Returns null if pages aren't broken up into their own dom elements.
	getPage: function(pageNumber) {
		if (this.pageList) {
			return this.pageList[pageNumber];
		} else {
			return null;
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
	},
	deducePaginatorIconColor: function()
	{
		// Build the fake hierarchy of elements down to the paginator icon
		var paginatorElement = document.createElement("div");
		paginatorElement.className = "paginator";
		var controlBarElement = document.createElement("div");
		controlBarElement.className = "control_bar";
		var pagingButtonsElement = document.createElement("div");
		pagingButtonsElement.className = "paging_buttons";
		var aElement = document.createElement("a");
		var imgElement = document.createElement("img");
		imgElement.className = "color_icon";

		aElement.appendChild(imgElement);
		pagingButtonsElement.appendChild(aElement);
		controlBarElement.appendChild(pagingButtonsElement);
		paginatorElement.appendChild(controlBarElement);
		
		// Append the fake hierarchy to the main element
		this.element.appendChild(paginatorElement);
		
		// Get the color of the fake image element
		var color = YAHOO.util.Dom.getStyle(imgElement, "color");
		
		// Remove the fake hierarchy
		this.element.removeChild(paginatorElement);
		
		// Return the color
		return color;	
	}
};

AJAXDiv = function(pages, url, args){
	this.contentURL = url;
	this.pages = pages;
	this.args = args;
	this.elements = [];
	for (var i=0; i<this.pages.length;i++) {
		this.elements.push(this.pages[i].page);
	}
	
	//TODO: More robust and general purpose spinner code
	if (Nexopia && Nexopia.JSONData) {
		this.spinner = document.createElement("div");
		this.spinner.innerHTML = "<img class='script' id='spinner' src='" + Site.staticFilesURL + "/nexoskel/images/spinner.gif" + "'/>";
		this.elements[0].appendChild(this.spinner);
	}
	this.load();
	
};

AJAXDiv.prototype = {
	load: function() {
		YAHOO.util.Connect.asyncRequest('GET', this.contentURL + "?" + this.args.join("&"), {
			success: function(o) {
				if (this.spinner) {
					this.elements[0].removeChild(this.spinner);
				}
				var temp = document.createElement("temp");
				temp.innerHTML = o.responseText;
				var children = [];
				//we're going to remove nodes so we need to copy the array first
				for (var i = 0; i < temp.childNodes.length; i++) {
					children.push(temp.childNodes[i]);
				}
				
				// children will probably have nodes that we don't care about.
				// Only append those with nodeType 1 to the elements array.
				for (i = 0, j = 0; j < this.elements.length; i++) {
					if (children[i].nodeType != 1) { //Node.ELEMENT_NODE (ie6 doesn't have this constant)
						continue;
					}
					this.elements[j].appendChild(children[i]);
					this.pages[j].loaded = true;
					j++; //do this here rather than in the loop declaration so that continues don't increment
				}
				Overlord.summonMinions(this.elements);
			},
			failure: function(o) {
			},
			scope: this
		});
	}
};

Overlord.assign({
	minion: "paginator",
	unload: function(element) {
		delete Paginator.paginators[element.id];
	}
});