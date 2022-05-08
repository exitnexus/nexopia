// /* 
// 	The purpose of this file is to bypass the scriptmanager loading process for the profile page.
// 	This is known to be very brittle and any changes to dom or javascript structures may force 
// 	changes to this file.
// */
// YAHOO.util.Event.onAvailable("disable_script_manager", function() {
// 	YAHOO.util.Event.onContentReady('profile', function() {
// 		FriendsProfileBlock.ListView.init(document.getElementById("list_view"));
// 		ClassicFilmStrip.init();
// 		FilmStripInstance.init();
// 		var eti = YAHOO.util.Dom.getElementsByClassName('enhanced_text_input', 'textarea', 'comment_write_form');
// 		for (var i=0; i<eti.length;i++) {
// 			new EnhancedTextInput(eti[i]);
// 		}
// 		// initialize_auto_shading(eti[0].parentNode);
// 		YAHOO.profile.ControlBlock.init();
// 		var rpv = document.getElementById("request_profile_view");
// 		if (rpv) {
// 			YAHOO.profile.Profile.init(rpv);
// 		}
// 		var fpb = document.getElementById('friends_pages');
// 		YAHOO.util.Dom.getElementsByClassName('add_friend', 'a', fpb, function(element) {
// 			new YAHOO.widget.Tooltip(document.createElement("div"), {context: element, text: "Add as Friend"});
// 			new FriendsProfileBlock.AddLink(element, "<img class=\"color_icon\" src=\""+Site.coloredImgURL(Nexopia.Utilities.deduceImgColor(element))+
// 				"/friends/images/icon_friend_true.gif\"/>");
// 		});
// 		YAHOO.util.Dom.getElementsByClassName('send_message', 'a', fpb, function(element) {
// 			new YAHOO.widget.Tooltip(document.createElement("div"), {context: element, text: "Send Message"});
// 		});
// 		YAHOO.util.Dom.getElementsByClassName('comment', 'a', fpb, function(element) {
// 			new YAHOO.widget.Tooltip(document.createElement("div"), {context: element, text: "Comment"});
// 		});
// 		var friendsPages = document.getElementById("friends_pages");
// 		var pageCount = YAHOO.util.Dom.get("friends_pages_count").value;
// 		var ajaxPath = YAHOO.util.Dom.get("friends_ajax_path").value;
// 		if (pageCount > 1) {
// 			var paginator = new Paginator(friendsPages, {
// 				orientation: "vertical", 
// 				height: 440, 
// 				pageIndicatorStyle: "none",
// 				ajaxLoadURL: ajaxPath,
// 				numPages: pageCount,
// 				prefetchPages: 3,
// 				pageGrouping: 10,
// 				duration: 0.5
// 			});
// 			//This will start the paginator loading the second page as soon as you hover
// 			//over the paginator the first time.
// 			YAHOO.util.Event.on(friendsPages, 'mouseover', function() {paginator.getPageAt(1);});
// 		} else {
// 			YAHOO.util.Dom.setStyle("friends_arrow_btns", "display", "none");
// 		}
// 		var hoverOn = function(event, element) {
// 			YAHOO.util.Dom.addClass(element, "block_hover");
// 			YAHOO.util.Dom.removeClass(element, "block_regular");			
// 		};
// 		var hoverOff = function(event, element) {
// 			YAHOO.util.Dom.addClass(element, "block_regular");
// 			YAHOO.util.Dom.removeClass(element, "block_hover");
// 		};
// 		YAHOO.util.Dom.getElementsByClassName('block_hover', 'a', fpb, function(element) {
// 			YAHOO.util.Event.addListener(element, "mouseover", hoverOn);
// 			YAHOO.util.Event.addListener(element, "mouseout", hoverOff);
// 		});
// 		FriendsProfileBlock.init(document.getElementById('friends_toolbar'));
// 		//This is currently broken but only affects there being a hover color on the utility block rows
// 		//in IE6 so for now I'm commenting it out and moving on to more critical tasks -Nathan
// 		// var rows = document.getElementById("profile_control_block").getElementsByTagName('tr');
// 		// for (var i = 0; i< rows.length; i++) {
// 		// 	YAHOO.util.Event.on(rows[i], 'mouseover', function(event, row) {
// 		// 		YAHOO.util.Dom.addClass(row, 'hover');		
// 		// 		YAHOO.util.Dom.getElementsByClassName('custom_color_icon', null, row, function() {
// 		// 			this.setColor(YAHOO.util.Dom.getStyle(this.parentNode, "color"));
// 		// 		});
// 		// 	});
// 		// 	YAHOO.util.Event.on(rows[i], 'mouseout', function(event, row) {
// 		// 		YAHOO.util.Dom.removeClass(row, 'hover');		
// 		// 		YAHOO.util.Dom.getElementsByClassName('custom_color_icon', null, row, function() {
// 		// 			this.resetColor();			
// 		// 		});
// 		// 	});
// 		// }
// 		var commentsBlock = document.getElementById("comment_write");
// 		if (commentsBlock) {
// 			CommentsTextArea.init(commentsBlock);
// 		}
// 		// var icons = YAHOO.util.Dom.getElementsByClassName('custom_color_icon', 'img', 'profile');
// 		// init_custom_color_icons(icons);
// 		YAHOO.util.Dom.getElementsByClassName('gallery_profile_block', 'div', 'profile', function(element) {
// 			new Paginator(element, {height:105, width: 492});
// 			YAHOO.util.Event.on(element, 'mouseover', function() {
// 				Nexopia.DelayedImage.loadImages(this);
// 			});
// 		});
// 		YAHOO.util.Dom.getElementsByClassName('description', 'div', 'recent_galleries', function(element) {
// 			new Truncator(element, {height: 40, width: 149});
// 		});
// 		if (document.getElementById("blog_profile_block")) {
// 			YAHOO.profile.BlogBlock.init();
// 		}
// 		YAHOO.util.Dom.getElementsByClassName('user_content_image', 'img', 'profile', Nexopia.DelayedImage.loadImage);
// 	});	
// });
