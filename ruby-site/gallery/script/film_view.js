//require user_gallery.js
UserGallery.FilmView = {
	COMMENT_CACHE_TIME: 300000, //time in milliseconds to client-side cache comments blocks for
	COMMENT_DELAY: 250,
	init_film_view: function(element) {
		//use the paginator to setup paging for the gallery filmstrip, doesn't do
		//dom paging, just slides one long element in a window element
		this.filmStripPaginator = new Paginator("gallery_film_strip", {
			width: 702,
			height: 80
		});
		this.fullLink = YAHOO.util.Dom.get("full_view");
		this.shareLink = YAHOO.util.Dom.get("share_link");
		this.imageDescription = YAHOO.util.Dom.get("image_description");
		this.reportAbuseLink = YAHOO.util.Dom.get("report_abuse");
		
		var currentPicsElement = document.getElementById("current_profile_pics");
		if (currentPicsElement && currentPicsElement.value != "")
		{
			this.currentProfilePics = document.getElementById("current_profile_pics").value.split(",");
		}
		else
		{
			this.currentProfilePics = [];
		}
		this.makeProfilePictureLink();
	},
	//change the main picture to the next thumbnail picture, wraps at the end
	next: function(event) {
		YAHOO.util.Event.preventDefault(event);
		YAHOO.util.Event.getTarget(event).blur();
		for (var i=0; i<this.thumbs.length; i++) {
			if (this.thumbs[i].id == this.currentPicture.pictureID) {
				this.thumbs[(i+1)%this.thumbs.length].makeCurrent();
				break;
			}
		}
		this.preloadImages(0,5);
	},
	//change the main picture to the previous thumbnail picture, wraps at the beginning
	previous: function(event) {
		YAHOO.util.Event.preventDefault(event);
		YAHOO.util.Event.getTarget(event).blur();
		for (var i=0; i<this.thumbs.length; i++) {
			if (this.thumbs[i].id == this.currentPicture.pictureID) {
				if (i-1 < 0) {
					this.thumbs[this.thumbs.length-1].makeCurrent();
				} else {
					this.thumbs[i-1].makeCurrent();
				}
				break;
			}
		}
		this.preloadImages(5,0);
	},
	//preload the specified number of images before and after the current image
	preloadImages: function(backward, forward) {
		for (var i=0; i<this.thumbs.length; i++) {
			if (this.thumbs[i].id == this.currentPicture.pictureID) {
				var currentThumb = i;
			}
		}
		for (i=1; i<=forward; i++) {
			var index = (currentThumb+i)%this.thumbs.length;
			this.getImage(this.thumbs[index]);
		}
		for (i=1; i<=backward; i++) {
			index = currentThumb - i;
			if (index < 0) {
				index = this.thumbs.length + index;
			}
			this.getImage(this.thumbs[index]);
		}
	},
	getImage: function(thumb) {
		if (!thumb.picture) {
			thumb.picture = new Image();
			thumb.picture.src = thumb.normal_link;
			thumb.picture.id = "current_picture";
		}
		return thumb.picture;
	},
	//list of Thumb objects in the order they appear on the page
	thumbs: [],
	//wrapper for adding to the thumbs array
	registerThumb: function(thumb) {
		this.thumbs.push(thumb);
	},
	//remove by thumb id from the thumbs array
	unregisterThumb: function(thumbID) {
		for (var i=0; i<this.thumbs.length; i++) {
			if (this.thumbs[i].id == thumbID) {
				this.thumbs.splice(i,1); //delete the element from the array
			}
		}
	},
	getThumb: function(thumbID) {
		for (var i=0; i<this.thumbs.length; i++) {
			if (this.thumbs[i].id == thumbID) {
				return this.thumbs[i];
			}
		}
	},
	//wrapper for set current used by normal view
	setCurrentById: function(thumbID) {
		var thumb = this.getThumb(thumbID);
		this.setCurrent(thumb);
		UserGallery.tabs.set("activeIndex", 0, false);
	},
	//set the main image based on a Thumb object
	setCurrent: function(thumb) {
		this.getImage(thumb);
		this.currentPicture.setPicture(thumb.picture);
		YAHOO.util.Dom.getElementsByClassName("current", "img", "gallery_film_strip", function(element) {
			YAHOO.util.Dom.removeClass(element, "current");
		});
		YAHOO.util.Dom.addClass(thumb.img, "current");
		YAHOO.util.Dom.get("current_index").innerHTML = thumb.getIndex();
		this.updateFullPath(thumb);
		this.updateSharePath(thumb);
		this.updateReportAbusePath(thumb);
		this.imageDescription.innerHTML = thumb.description;
		this.loadComments(thumb);
		
		this.makeProfilePictureLink();
	},
	makeProfilePictureLink: function() {
		if(this.currentPicIsProfilePic())
		{
			YAHOO.util.Dom.setStyle("make_profile_picture", "display", "none");
		}
		else
		{
			YAHOO.util.Dom.setStyle("make_profile_picture", "display", "inline");
		}
	},
	updateFullPath: function(thumb) {
		this.fullLink.href = thumb.full_link;
	},
	updateSharePath: function(thumb) {
		// Remove all previous listeners
		YAHOO.util.Event.removeListener(this.shareLink, 'click');		
		
		// Update the link
		this.shareLink.id = "share_link";
		this.shareLink.href = thumb.share_link;
		this.shareLink.attributes["path"].value = thumb.share_link;
		
		// Build a new share panel
		var panel = new AsyncPanel( { path: this.shareLink.attributes["path"].value } );
		NexopiaPanel.linkMap[this.shareLink.id] = panel;
		
		// Set it to open upon clicking the share link
		YAHOO.util.Event.on(this.shareLink, 'click', panel.open, panel, true);
	},
	updateReportAbusePath: function(thumb) {
		this.reportAbuseLink.href = Site.wwwURL + "/reportabuse.php?type=22&uid="+thumb.userid+"&id=" + thumb.id;
	},
	//update the current comments section
	loadComments: function(thumb) {
		var container = YAHOO.util.Dom.get("gallery_comments_block");
		//comments container only exists on logged in page views
		if (container) {
			if (thumb.container && thumb.container.loaded && thumb.container.loaded + this.COMMENT_CACHE_TIME > (new Date()).getTime()) {
				container.parentNode.replaceChild(thumb.container, container);
				return; //abort load we already had it cached
			} else {
				var spinner = container.cloneNode(false);
				spinner.innerHTML = "<img class='script' id='spinner' src='" + Site.staticFilesURL + "/nexoskel/images/spinner.gif" + "'/>";
				container.parentNode.replaceChild(spinner, container);
			}
			//timeout so that we don't actually make a request for a comments if people aren't going to stay and read them
			setTimeout(function(){
				if (UserGallery.FilmView.currentPicture.pictureID == thumb.id) { //only if we're still on the picture
					YAHOO.util.Connect.asyncRequest('GET', Nexopia.JSONData['commentLink'] + "/" + thumb.id, new ResponseHandler({
						success: function(o) {
						},
						failure: function(o) {
						},
						scope: this
					}), "ajax=true");
				}
			}, this.COMMENT_DELAY);
		}
	},
	makeProfilePic: function() {
		YAHOO.util.Dom.setStyle("make_profile_picture", "display", "none");
		
		var id = this.currentPicture.pictureID;
		var formKey = document.getElementById('manage_gallery_form_key').value;
		YAHOO.util.Connect.asyncRequest('POST', Site.selfURL + '/gallery/make_profile_pic', new ResponseHandler({
			success: function(o) {
				this.currentProfilePics[this.currentProfilePics.length] = id; 
			},
			failure: function(o) {
			},
			scope: this
		}), "id="+id+"&form_key[]="+formKey);
		
	},
	editPic: function() {
		EditPicturePanel.init(this.currentPicture.pictureID, 'gallery', {baseURI: '/my'});
	},
	currentPicIsProfilePic: function()
	{
		for(var i = 0; i < this.currentProfilePics.length; i++)
		{
			if(parseInt(this.currentProfilePics[i], 10) == parseInt(this.currentPicture.pictureID, 10))
			{
				return true;
			}
		}
		
		return false;
	}
};

Overlord.assign({
	minion: "user_gallery_film_view",
	load: UserGallery.FilmView.init_film_view,
	scope: UserGallery.FilmView
});

Overlord.assign({
	minion: "gallery_film_strip",
	mouseover: function(event, element) {
		Nexopia.DelayedImage.loadImages(element);
	},
	order: 1 //This should occur after the rest of the page has initialized, it depends on thumbnail objects having already been created
});

Overlord.assign({
	minion: "gallery_comments:view",
	load: function(element) {
		var thumb = UserGallery.FilmView.getThumb(UserGallery.FilmView.currentPicture.pictureID);
		thumb.container = element;
		element.loaded = (new Date()).getTime();
	},
	order: 1 //This should occur after the rest of the page has initialized, it depends on thumbnail objects having already been created
});

Overlord.assign({
	minion: "film_view:make_profile_picture",
	load: function(element) {
		YAHOO.util.Event.on(element, 'click', UserGallery.FilmView.makeProfilePic, null, UserGallery.FilmView);
	},
	order: 1 //This should occur after the rest of the page has initialized, it depends on thumbnail objects having already been created
});

Overlord.assign({
	minion: "film_view:edit_picture",
	load: function(element) {
		YAHOO.util.Event.on(element, 'click', UserGallery.FilmView.editPic, null, UserGallery.FilmView);
	},
	order: 1 //This should occur after the rest of the page has initialized, it depends on thumbnail objects having already been created
});