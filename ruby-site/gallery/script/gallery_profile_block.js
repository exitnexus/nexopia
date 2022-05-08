GalleryProfileBlockManager = {
	init: function() {
		YAHOO.util.Dom.getElementsByClassName("gallery_profile_block", "div", null, function(element) {
			new GalleryProfileBlock(element);
		});
	}
}

GalleryProfileBlock = function(element) {
		var alist = YAHOO.util.Dom.getElementsByClassName("gallery_pics_scroller", "div", element);
		this.list = alist[0]; 
		var that = this;
		YAHOO.util.Dom.getElementsByClassName("arrow_left", "a", element, function(child){
			YAHOO.util.Event.on(child, "click", that.ascrollLeft, that, true);
		});
		YAHOO.util.Dom.getElementsByClassName("arrow_right", "a", element, function(child){
			YAHOO.util.Event.on(child, "click", that.ascrollRight, that, true);
		});
}

GalleryProfileBlock.prototype = {
	ascrollLeft: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		//if we're already moving instantly finish it so we do screw up positioning
		if (this.currentAnimation) {
			this.currentAnimation.stop(true);
		}
		this.currentAnimation = new YAHOO.util.Scroll(this.list, { scroll: { by: [-(this.list.clientWidth - 53), 0] } }, 1, YAHOO.util.Easing.easeOutStrong);
		this.currentAnimation.onComplete.subscribe(this.finishAnimation, this, true);
		this.currentAnimation.animate();
	},
	ascrollRight: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		//if we're already moving instantly finish it so we do screw up positioning
		if (this.currentAnimation) {
			this.currentAnimation.stop(true);
		}
		this.currentAnimation = new YAHOO.util.Scroll(this.list, { scroll: { by: [(this.list.clientWidth - 53), 0] } }, 1, YAHOO.util.Easing.easeOutStrong);
		this.currentAnimation.onComplete.subscribe(this.finishAnimation, this, true);
		this.currentAnimation.animate();
	},
	finishAnimation: function() {
		this.currentAnimation = null;
	},
	currentAnimation: null
};

GlobalRegistry.register_handler("gallery_profile_block", GalleryProfileBlockManager.init, GalleryProfileBlockManager, true);