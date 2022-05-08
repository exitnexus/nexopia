FriendsProfileBlock = {
	init: function(element) {
		this.toolBar = element;
		
		this.sendMessageTooltip = new YAHOO.widget.Tooltip(document.createElement("div"), {context: [], text: "Send Message"});
		this.commentTooltip = new YAHOO.widget.Tooltip(document.createElement("div"), {context: [], text: "Comment"});
		this.addFriendTooltip = new YAHOO.widget.Tooltip(document.createElement("div"), {context: [], text: "Add as Friend"});
		
		this.sendMessageLinks = [];
		this.commentLinks = [];
		this.addFriendLinks = [];
		
		var autoComplete = new YAHOO.widget.AutoComplete("friends_search", "friends_results", new YAHOO.widget.DS_JSArray(Nexopia.json(element), {
			queryMatchContains: true
		}), {
			animHoriz: false,
			animVert: false
		});
		autoComplete.itemSelectEvent.subscribe(function(event, args) {
			var username = args[2][0];
			window.location = "/users/" + encodeURIComponent(username);
		});
		
		this.thumbViewLink = YAHOO.util.Dom.getElementsByClassName('thumb_view', 'a', element, function(thumb_view) {
			YAHOO.util.Event.on(thumb_view, 'click', FriendsProfileBlock.thumbView, FriendsProfileBlock, true);
		})[0];
		if (!YAHOO.util.Dom.hasClass('thumb_view_container', 'hidden')) {
			this.loadThumbViewImages();
		}
		this.listViewLink = YAHOO.util.Dom.getElementsByClassName('list_view', 'a', element, function(list_view) {
			YAHOO.util.Event.on(list_view, 'click', FriendsProfileBlock.listView, FriendsProfileBlock, true);
		})[0];
		if (!YAHOO.util.Dom.hasClass('list_view_container', 'hidden')) {
			this.listView();
		}
		
		var mock_event = new Object();
		mock_event.page = 0;
		mock_event.previous_page = 0;
		FriendsProfileBlock.onPageChangeTooltipUpdate("onPageChange", [mock_event]);
	},
	loadThumbViewImages: function() {
		if (!this.loadedThumbViewImages) {
			Nexopia.DelayedImage.loadImages("thumb_view_container");
			this.loadedThumbViewImages = true;
			//once we load the images once make sure we load the images for any future pages immediately
			Overlord.assign({
				minion: "fpb:friend_pic",
				load: Nexopia.DelayedImage.loadImage,
				limitToContext: true				
			});
		}
	},
	thumbView: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		this.loadThumbViewImages();
		YAHOO.util.Dom.addClass(this.thumbViewLink, 'selected');
		YAHOO.util.Dom.removeClass('thumb_view_container', 'hidden');
		YAHOO.util.Dom.removeClass(this.listViewLink, 'selected');
		YAHOO.util.Dom.addClass('list_view_container', 'hidden');
	},
	listView: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		YAHOO.util.Dom.addClass(this.listViewLink, 'selected');
		YAHOO.util.Dom.removeClass('list_view_container', 'hidden');
		YAHOO.util.Dom.removeClass(this.thumbViewLink, 'selected');
		YAHOO.util.Dom.addClass('thumb_view_container', 'hidden');
	},
	updateTooltips: function() {
		this.sendMessageTooltip.render(this.element);
		this.commentTooltip.render(this.element);
		this.addFriendTooltip.render(this.element);
		this.sendMessageTooltip.cfg.setProperty("context", this.sendMessageLinks);
		this.commentTooltip.cfg.setProperty("context", this.commentLinks);
		this.addFriendTooltip.cfg.setProperty("context", this.addFriendLinks);
	},
	onPageChangeTooltipUpdate: function(type, args)
	{
		var page_info = args[0];

		message_links = YAHOO.util.Dom.getElementsByClassName("send_message", "a", "friends_list_page_"+page_info.page, null);
		FriendsProfileBlock.sendMessageLinks = message_links;
		comment_links = YAHOO.util.Dom.getElementsByClassName("comment", "a", "friends_list_page_"+page_info.page, null);
		FriendsProfileBlock.commentLinks = comment_links;
		add_friend_links = YAHOO.util.Dom.getElementsByClassName("add_friend", "a", "friends_list_page_"+page_info.page, null);
		FriendsProfileBlock.addFriendLinks = add_friend_links;
		FriendsProfileBlock.updateTooltips();
	}
};

FriendsProfileBlock.AddLink = function(element, replacementHTML) {
	this.element = element;
	this.replacementHTML = replacementHTML;
	YAHOO.util.Event.on(this.element, 'click', this.submit, this, true);
};

FriendsProfileBlock.AddLink.prototype = {
	submit: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}

		var spinner = new Spinner({ context: [this.element, "tr"], offset: [-3,-4] , lazyload: true });
		this.element.style.visibility = "hidden";
		spinner.on();
		var that = this;
		YAHOO.util.Connect.asyncRequest('POST', this.element.href, new ResponseHandler({
			success: function() {
				spinner.off();
				if (that.replacementHTML)
				{
					this.parentNode.innerHTML = that.replacementHTML;
					// init_custom_color_icons([YAHOO.util.Dom.getElementsByClassName("custom_color_icon", "img", this.parentNode)[0]]);					
				}
				else
				{
					this.parentNode.removeChild(this);
				}
			},
			failure: function() {
				spinner.off();
				this.style.visibility = "visible";
			},
			scope: this.element
		}), 'ajax=true');
	}
};

Overlord.assign({
	minion: "fpb:add_friend",
	scope: FriendsProfileBlock,
	load: function(element) {
		//new YAHOO.widget.Tooltip(document.createElement("div"), {context: element, text: "Add as Friend"});
		//FriendsProfileBlock.addFriendLinks.push(element);
		new FriendsProfileBlock.AddLink(element, "<img src=\""+Site.coloredImgURL(Nexopia.Utilities.deduceImgColor(element))+"/friends/images/icon_friend_true.gif\" class=\"color_icon\"/>");
	},
	order: 5	
	
});

Overlord.assign({
	minion: "fpb:send_message",
	scope: FriendsProfileBlock,
	load: function(element) {
		//FriendsProfileBlock.sendMessageLinks.push(element);
		//new YAHOO.widget.Tooltip(document.createElement("div"), {context: element, text: "Send Message"});
	},
	order: 5
});

Overlord.assign({
	minion: "fpb:comment",
	scope: FriendsProfileBlock,
	load: function(element) {
		//FriendsProfileBlock.commentLinks.push(element);
		//new YAHOO.widget.Tooltip(document.createElement("div"), {context: element, text: "Comment"});
	},
	order: 5
});

Overlord.assign({
	minion: "fpb:friends_pages",
	load: function(element) {
		var pageCount = YAHOO.util.Dom.get("friends_pages_count").value;
		var ajaxPath = YAHOO.util.Dom.get("friends_ajax_path").value;
		if (pageCount > 1) {
			var paginator = new Paginator(element, {
				orientation: "vertical", 
				height: 440, 
				pageIndicatorStyle: "none",
				ajaxLoadURL: ajaxPath,
				numPages: pageCount,
				prefetchPages: 3,
				pageGrouping: 10,
				duration: 0.5
			});
			//This will start the paginator loading the second page as soon as you hover
			//over the paginator the first time.
			YAHOO.util.Event.on(element, 'mouseover', function() {paginator.getPageAt(1);});
			paginator.onPageChange.subscribe(FriendsProfileBlock.onPageChangeTooltipUpdate);
		} else {
			YAHOO.util.Dom.setStyle("friends_arrow_btns", "display", "none");
		}
	},
	order: -1
});

Overlord.assign({
	minion: "fpb:user_container",
	load: function(element) {
		function hoverOn()
		{
			YAHOO.util.Dom.addClass(element, "block_hover");
			YAHOO.util.Dom.removeClass(element, "block_regular");			
		}
		YAHOO.util.Event.addListener(element, "mouseover", hoverOn);
		
		function hoverOff()
		{
			YAHOO.util.Dom.addClass(element, "block_regular");
			YAHOO.util.Dom.removeClass(element, "block_hover");
		}
		YAHOO.util.Event.addListener(element, "mouseout", hoverOff);
	}
});

Overlord.assign({
	minion: "fpb:friends_toolbar",
	load: FriendsProfileBlock.init,
	scope: FriendsProfileBlock
});
