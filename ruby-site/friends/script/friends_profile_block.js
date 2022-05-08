FriendsProfileBlock = {
	init: function() {
		this.list = YAHOO.util.Dom.get("friends_list");
		YAHOO.util.Event.on("arrow_up", "click", this.scrollUp, this, true);
		YAHOO.util.Event.on("arrow_down", "click", this.scrollDown, this, true);
		YAHOO.util.Dom.getElementsByClassName("add_friend", "a", "friends_list", function(element) {
			new YAHOO.widget.Tooltip(document.createElement("div"), {context: element, text: "Add as Friend"});
		});
		YAHOO.util.Dom.getElementsByClassName("send_message", "a", "friends_list", function(element) {
			new YAHOO.widget.Tooltip(document.createElement("div"), {context: element, text: "Send Message"});
		});
		YAHOO.util.Dom.getElementsByClassName("comment", "a", "friends_list", function(element) {
			new YAHOO.widget.Tooltip(document.createElement("div"), {context: element, text: "Comment"});
		});
	},
	scrollUp: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		//if we're already moving instantly finish it so we don't screw up positioning
		if (this.currentAnimation) {
			this.currentAnimation.stop(true);
		}
		this.currentAnimation = new YAHOO.util.Scroll(this.list, { scroll: { by: [0, -450] } }, 1, YAHOO.util.Easing.easeOutStrong);
		this.currentAnimation.onComplete.subscribe(this.finishAnimation, this, true);
		this.currentAnimation.animate();
	},
	scrollDown: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		//if we're already moving instantly finish it so we don't screw up positioning
		if (this.currentAnimation) {
			this.currentAnimation.stop(true);
		}
		this.currentAnimation = new YAHOO.util.Scroll(this.list, { scroll: { by: [0, 450] } }, 1, YAHOO.util.Easing.easeOutStrong);
		this.currentAnimation.onComplete.subscribe(this.finishAnimation, this, true);
		this.currentAnimation.animate();
	},
	finishAnimation: function() {
		this.currentAnimation = null;
	},
	currentAnimation: null
};

GlobalRegistry.register_handler("friends_profile_block", FriendsProfileBlock.init, FriendsProfileBlock, true);