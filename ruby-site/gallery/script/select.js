GallerySelect = {
	init: function() {
		YAHOO.util.Event.on("selected_gallery", "click", function(e) {GallerySelect.updateDisplay(e);});
		YAHOO.util.Event.on("create_gallery", "click", function(e) {
			GallerySelect.updateDisplay(e);
			YAHOO.util.Event.preventDefault(e);
			if (this.value == "Create Gallery") {
				this.value = "Cancel";
			} else {
				this.value = "Create Gallery";
			}
		});
		document.getElementById("gallery_create_submit").onclick = function(e) {GallerySelect.submitNewGallery(e);return false;};
		this.updateDisplay();
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
			var clicked = YAHOO.util.Event.getTarget(e).value;
		}
		var selectedGallery = document.getElementById("selected_gallery");
		if ((selectedGallery && selectedGallery.value == 0) || clicked == "Create Gallery") {
			this.showOverlay(e);
		} else {
			this.hideOverlay(e);
		}
	},
	showOverlay: function(e) {
		var overlay = document.getElementById("gallery_overlay");
		overlay.style.display = "block";
		var xy = YAHOO.util.Dom.getXY(YAHOO.util.Event.getTarget(e).parentNode);
		overlay.style.position = "absolute";
		overlay.style.left = xy[0];
		overlay.style.top = xy[1]+15;
	},
	hideOverlay: function(e) {
		document.getElementById("gallery_overlay").style.display = "none";
	},
	submitNewGallery: function(e) {
		if (document.getElementById("gallery_create_submit").value == "Creating...") {
			return false; //we're already submitting don't do anything else.
		}
		var overlay = document.getElementById("gallery_overlay");
		var form = document.getElementById("gallery_create_form");
		document.getElementById("gallery_create_submit").value = "Creating...";
		YAHOO.util.Connect.setForm(form);
		this.callback.argument = e;
		YAHOO.util.Connect.asyncRequest('POST', '/my/gallery/ajax_create', new ResponseHandler(this.callback));
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
				main_create.value = "Create Gallery";
			}
			GallerySelect.reinit();
		},
		failure: function(o) {
			document.getElementById("gallery_overlay").style.display = "none";
			document.getElementById("gallery_create_submit").value = "Create Gallery";
			var newGalleryOption = document.getElementById("new_gallery");
			newGalleryOption.parentNode.removeChild(newGalleryOption);
			var div = document.createElement("div");
			div.innerHTML = "Unable to create gallery dynamically, use the <a class=\"body\" href=\"/my/gallery/create\">gallery creation page</a> to create a new gallery.";
			document.getElementById("selected_gallery").parentNode.appendChild(div);
		},
		timeout: 5000,
		argument: []
	}
};

GlobalRegistry.register_handler(["gallery_select", "gallery_header"], GallerySelect.init, GallerySelect, true);