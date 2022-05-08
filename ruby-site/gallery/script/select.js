GallerySelect = {
	classicUploader : false,
	init: function() {
		if (!this.initialized) {
			this.initialized = true;
			YAHOO.util.Event.on("selected_gallery", "click", function(e) {GallerySelect.updateDisplay(e);});
			YAHOO.util.Event.on("create_gallery", "click", function(e) {
				YAHOO.util.Event.preventDefault(e);
				GallerySelect.updateDisplay(e);
				if (this.innerHTML == "CREATE GALLERY") {
					this.innerHTML = "CANCEL";
				} else {
					this.innerHTML = "CREATE GALLERY";
				}
			});
			YAHOO.util.Event.on("cancel_link", "click", function(e) {GallerySelect.cancelCreate(e);});
			galleryCreateSubmit = YAHOO.util.Dom.get("gallery_create_submit");
			galleryOverlay = YAHOO.util.Dom.get("gallery_overlay");
			if (galleryCreateSubmit && galleryOverlay && YAHOO.util.Dom.isAncestor(galleryOverlay, galleryCreateSubmit)) {
				YAHOO.util.Event.on("gallery_create_submit", 'click', function(e) {
					YAHOO.util.Event.preventDefault(e);
					GallerySelect.submitNewGallery(this);
				});
			}
			this.updateDisplay();
		}
		if(!YAHOO.util.Event.getListeners("selected_gallery", "click"))
		{
			YAHOO.util.Event.on("selected_gallery", "click", function(e) {GallerySelect.updateDisplay(e);});
		}
	},
	reinit: function() {
		if (GalleryManagement && GalleryManagement.initialized) {
			GalleryManagement.updateOptions();
		}
		this.init();
	},
	updateDisplay: function(e) {
		var clicked = false;
		if (e) {
			var clicked = YAHOO.util.Event.getTarget(e).innerHTML;
		}
		var selectedGallery = document.getElementById("selected_gallery");
		if ((selectedGallery && selectedGallery.value == 0) || clicked == "CREATE GALLERY") {
			this.showOverlay(e);
		} else {
			this.hideOverlay(e);
		}
	},
	showOverlay: function(e) {
		var overlay = document.getElementById("gallery_overlay");
		YAHOO.util.Dom.setStyle(overlay, "display", "block");
		if(this.classicUploader) { 
			YAHOO.util.Event.addListener(document.getElementById('file_upload'), "click", ProfilePictureUploader.disabled);
		} else {
			YAHOO.util.Dom.setStyle('uploader_overlay', "display", "none");
		}
	},
	cancelCreate: function(e) {
		YAHOO.util.Event.preventDefault(e);
		this.hideOverlay(e);
		var selectedGallery = document.getElementById("selected_gallery");
		
		// if there's a gallery selector box reset it to the default
		if (selectedGallery) {
			selectedGallery.options[0].selected=true;
		}

	}, 
	hideOverlay: function(e) {
		document.getElementById("gallery_overlay").style.display = "none";
		if(this.classicUploader) { 
			YAHOO.util.Event.removeListener(document.getElementById('file_upload'), "click", ProfilePictureUploader.disabled);
		} else {
			YAHOO.util.Dom.setStyle('uploader_overlay', "display", "block");
		}
	},
	submitNewGallery: function(e) {
		if (document.getElementById("gallery_create_submit").value == "Creating...") {
			return false; //we're already submitting don't do anything else.
		}
		var overlay = document.getElementById("gallery_overlay");
		var form = YAHOO.util.Dom.getAncestorByTagName(document.getElementById("gallery_create_submit"), "form");
		document.getElementById("gallery_create_submit").value = "Creating...";
		YAHOO.util.Connect.setForm(form);
		this.callback.argument = e;
		YAHOO.util.Connect.asyncRequest('POST', Nexopia.areaBaseURI() + '/gallery/ajax_create', new ResponseHandler(this.callback), "refresh=create_album_popup");
		return false;
	},
	callback: {
		success: function(o) {
			var overlay_create = document.getElementById("gallery_create_submit");
			if (overlay_create) {
				overlay_create.value = "Create Gallery";
			}
			var main_create = document.getElementById("create_gallery");
			if (main_create) {
				main_create.innerHTML = "CREATE GALLERY";
			}
			GallerySelect.updateDisplay();
			GallerySelect.reinit();
			GalleryManagement.move_to();
		},
		failure: function(o) {
			document.getElementById("gallery_overlay").style.display = "none";
			document.getElementById("gallery_create_submit").value = "Create Gallery";
			var newGalleryOption = document.getElementById("new_gallery");
			newGalleryOption.parentNode.removeChild(newGalleryOption);
			var div = document.createElement("div");
			div.innerHTML = "Unable to create gallery dynamically, use the <a class=\"body\" href=\"/my/gallery/create\">gallery creation page</a> to create a new gallery.";
			document.getElementById("selected_gallery").parentNode.appendChild(div);
		},
		timeout: 5000,
		newNode: function(node) {
			if (node.id) {
				var match = node.id.match(/^gallery_folder_(\d+)$/);
				if (match) {
					var albumRow = YAHOO.util.Dom.get("album_row");
					if (albumRow) {
						var li = document.createElement("li");
						li.id = match[1];
						albumRow.appendChild(li);
						li.appendChild(node);
						var paginatorEl = YAHOO.util.Dom.getAncestorByClassName(albumRow, 'paginator');
						if (paginatorEl) {
							var paginator = Paginator.paginators[paginatorEl.id];
							paginator.reinitializeSlidePaging();
						}
					}
				}
			}
		},
		argument: []
	}
};

Overlord.assign({
	minion: "gallery_creation",
	load: GallerySelect.init,
	scope: GallerySelect	
});