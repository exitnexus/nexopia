//require friends_profile_block.js
FriendsProfileBlock.ListView = {
	pageLength: 20,
	json: null,
	page: 0,
	init: function(element) {
		this.sendMessageTooltip = new YAHOO.widget.Tooltip(document.createElement("div"), {context: [], text: "Send Message"});
		this.commentTooltip = new YAHOO.widget.Tooltip(document.createElement("div"), {context: [], text: "Comment"});
		this.addFriendTooltip = new YAHOO.widget.Tooltip(document.createElement("div"), {context: [], text: "Add as Friend"});

		this.sendMessageLinks = [];
		this.commentLinks = [];
		this.addFriendLinks = [];

		this.pages = [];
		
		this.element = element.firstChild;
		this.json = Nexopia.json(element)[0];
		this.viewer = Nexopia.json(element)[1];
		this.form_key = YAHOO.util.Dom.get('list_view_form_key').value;
		for (var i=0; i<this.json.length;i++) {
			var parsedData = {
				friendid: this.json[i][0],
				username: this.json[i][1],
				alreadyfriend: this.json[i][2]
			};
			this.json[i] = parsedData;
		}
		this.lastPage = Math.floor((this.json.length-1)/this.pageLength);
		this.element.innerHTML = "";
		this.appendPage(this.page);
		this.page_backward = YAHOO.util.Dom.getElementsByClassName('page_backward', 'a', element)[0];
		this.page_forward = YAHOO.util.Dom.getElementsByClassName('page_forward', 'a', element)[0];
		YAHOO.util.Event.on(this.page_backward, 'click', this.pageBackward, this, true);
		YAHOO.util.Event.on(this.page_forward, 'click', this.pageForward, this, true);
	},
	pageForward: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		if (this.page >= this.lastPage) {
			return;
		}
		this.page++;
		this.appendPage(this.page);
		this.scrollPageForward();
	},
	pageBackward: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		if (this.page <= 0) {
			return;
		}
		this.page--;
		this.prependPage(this.page);
		this.element.scrollTop = 440;
		this.scrollPageBackward();
		
	},
	appendPage: function(pageNumber) {

		if (!this.pages[pageNumber]) {
			this.pages[pageNumber] = this.buildPage(pageNumber);
		}
		this.element.appendChild(this.pages[pageNumber]);
		this.updateTooltips();
	},
	prependPage: function(pageNumber) {
		if (!this.pages[pageNumber]) {
			this.pages[pageNumber] = this.buildPage(pageNumber);
		}
		this.element.insertBefore(this.pages[pageNumber], this.element.firstChild);
		this.updateTooltips();
	},
	updateTooltips: function() {
		this.sendMessageTooltip.render(this.element);
		this.commentTooltip.render(this.element);
		this.addFriendTooltip.render(this.element);
		this.sendMessageTooltip.cfg.setProperty("context", this.sendMessageLinks);
		this.commentTooltip.cfg.setProperty("context", this.commentLinks);
		this.addFriendTooltip.cfg.setProperty("context", this.addFriendLinks);
	},
	scrollPageForward: function() {
		if (this.currentAnimation) {
			this.currentAnimation.stop(true);
		}
		this.currentAnimation = new YAHOO.util.Scroll(this.element, { scroll: { by: [0, 440] } }, 0.5, YAHOO.util.Easing.easeOutStrong);
		this.currentAnimation.animate();
		this.currentAnimation.onComplete.subscribe(this.cleanUpFront, this, true); 
	},
	scrollPageBackward: function() {
		if (this.currentAnimation) {
			this.currentAnimation.stop(true);
		}
		this.currentAnimation = new YAHOO.util.Scroll(this.element, { scroll: { by: [0, -440] } }, 0.5, YAHOO.util.Easing.easeOutStrong);
		this.currentAnimation.animate();
		this.currentAnimation.onComplete.subscribe(this.cleanUpEnd, this, true);
	},
	cleanUpFront: function() {
		this.element.removeChild(this.element.firstChild);
		this.element.scrollTop = 0;
		this.currentAnimation = null;
	},
	cleanUpEnd: function() {
		childpages = YAHOO.util.Dom.getElementsByClassName("page", "div", this.element);
		this.element.removeChild(childpages[childpages.length-1]);
		this.element.scrollTop = 0;
		this.currentAnimation = null;
	},
	buildPage: function(pageNumber) {
		var div = document.createElement("div");
		div.className = "page";
		var iconColor = this.deduceImgColor();
		for (var i=this.page*this.pageLength;i<(this.page+1)*this.pageLength && i<this.json.length;i++) {
			var friend = this.buildFriendDisplay(this.json[i], iconColor);
			div.appendChild(friend);
		}
		return div;
	},
	buildFriendDisplay: function(jsonData, iconColor) {
		var shortFriend = document.createElement("div");
		shortFriend.className = "short_friend";
		if (!this.viewer.anonymous) {
			var functions = document.createElement("div");
			functions.className = "functions";
			shortFriend.appendChild(functions);
			
			var sendMessage = document.createElement("a");
			sendMessage.className = "send_message hover";
			sendMessage.href = "/messages.php?action=write&to=" + jsonData.friendid;
			functions.appendChild(sendMessage);
			this.sendMessageLinks.push(sendMessage);
			var sendMessageImg = document.createElement("img");
			sendMessageImg.src = Site.coloredImgURL(iconColor) + "/friends/images/icon_send_msg.gif";
			sendMessageImg.className = "color_icon";
			sendMessage.appendChild(sendMessageImg);
			var comment = document.createElement("a");
			comment.className = "comment hover";
			comment.href = Site.userURL + "/" + Nexopia.Utilities.escapeURI(jsonData.username) + "/comments";
			functions.appendChild(comment);
			var commentImg = document.createElement("img");
			commentImg.src = Site.coloredImgURL(iconColor) + "/friends/images/icon_comment.gif";
			commentImg.className = "color_icon last";
			this.commentLinks.push(comment);
			comment.appendChild(commentImg);
			if (jsonData.alreadyfriend) {
				var alreadyFriend = document.createElement("img");
				alreadyFriend.src = Site.coloredImgURL(iconColor) + "/friends/images/icon_friend_true.gif";
				alreadyFriend.className = "color_icon";
				functions.appendChild(alreadyFriend);
			} else {
				var addFriend = document.createElement("a");
				addFriend.className = "add_friend";
				addFriend.href = Site.userURL + "/" + Nexopia.Utilities.escapeURI(this.viewer.username) + "/friends/add/" + jsonData.friendid + "?form_key[]=" + this.form_key;
				new FriendsProfileBlock.AddLink(addFriend, "<img class=\"color_icon\" src=\""+Site.coloredImgURL(iconColor) +"/friends/images/icon_friend_true.gif\"/>");
				var addFriendImg = document.createElement("img");
				addFriendImg.src = Site.coloredImgURL(iconColor) + "/friends/images/icon_add_friend.gif";
				addFriendImg.className = "color_icon";
				addFriend.appendChild(addFriendImg);
				this.addFriendLinks.push(addFriend);
				functions.appendChild(addFriend);
			}
		}
		var usernameLink = document.createElement("a");
		usernameLink.href = Site.userURL + "/" + Nexopia.Utilities.escapeURI(jsonData.username);
		usernameLink.innerHTML = Nexopia.Utilities.escapeHTML(jsonData.username);
		shortFriend.appendChild(usernameLink);
		return shortFriend;
	},
	deduceImgColor: function()
	{
		var iconColor = '000000';
		
		var profileDiv = document.getElementById('profile');
		if (profileDiv)
		{
			var primaryDiv = document.createElement('div');
			primaryDiv.className = 'primary_block';
			var secondaryDiv = document.createElement('div');
			secondaryDiv.className = 'secondary_block';
			primaryDiv.appendChild(secondaryDiv);
			profileDiv.appendChild(primaryDiv);
			iconColor = Nexopia.Utilities.deduceImgColor(secondaryDiv);
			profileDiv.removeChild(primaryDiv);
		}
		return iconColor;
	}
};

Overlord.assign({
	minion: "fpb:list_view",
	load: FriendsProfileBlock.ListView.init,
	scope: FriendsProfileBlock.ListView,
	order: -10
});