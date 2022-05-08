GalleryManagement = {
	initialized: false,
	init: function() {
		if (!this.initialized) {
			this.initialized = true;
		}
		var galleryid = document.getElementById("galleryid");
		if (galleryid) {
			this.galleryid = galleryid.value;
		}
		
		// find all elements under the element with id 'action_bar' 
		// that have both an element name that has "function" in it 
		// and have an id that matches a function name somewhere in GalleryManagement
		var actions = YAHOO.util.Dom.getElementsBy(function(element) {
			return (/^function/.test(element.name) && GalleryManagement[element.id]);
		}, "", document.getElementById("action_bar"));
		
		// For each element that has an action associated with it add an 'onclick' listener to it.
		for (var i=0;i<actions.length;i++) {
			YAHOO.util.Event.addListener(actions[i], "click", function(event) {
				YAHOO.util.Event.preventDefault(event);
				GalleryManagement[this.id]();
			});
		}
		YAHOO.util.Event.on("selected_gallery", "change", this.move_to, this, true);
		this.updateOptions();
		this.initialized = true;
	},
	reinit: function() {
		if (this.initialized) { //don't reinitialize if we were never initialized to begin with
			this.galleries = {};
			this.init();
			if (GallerySelect) {
				GallerySelect.init();
			}
		}
	},
	album_cover: function() {
		var id = this.getSelected()[0];
		this.startSpinner();
		YAHOO.util.Connect.setForm("gallery_form");
		YAHOO.util.Connect.asyncRequest('POST', Nexopia.JSONData['areaBaseUri'] + '/gallery/album_cover', new ResponseHandler({
			success: function(o) {
				this.unselectAll();
				GalleryManagement.stopSpinner();
			},
			scope: this
		}), "galleryid=" + this.galleryid + "&id="+id);
	},
	make_profile_pic: function() {
		var id = this.getSelected()[0];
		this.startSpinner();
		YAHOO.util.Connect.setForm("gallery_form");
		YAHOO.util.Connect.asyncRequest('POST', Nexopia.JSONData['areaBaseUri'] + '/gallery/make_profile_pic', new ResponseHandler({
			success: function(o) {
				this.unselectAll();
				GalleryManagement.stopSpinner();
			},
			failure: function(o) {
				this.unselectAll();
				GalleryManagement.stopSpinner();
			},
			scope: this
		}), "id="+id);
		
	},
	remove: function() {
		if (confirm("Delete selected pictures?")) {
			var ids = this.getSelected().join(',');
			this.startSpinner();
			YAHOO.util.Connect.setForm("gallery_form");
			YAHOO.util.Connect.asyncRequest('POST', Nexopia.JSONData['areaBaseUri'] + '/gallery/delete_group', new ResponseHandler({
				success: function(o, argument) {
					var selected = this.getSelected();
					for (var i=0;i<selected.length; i++) {
						this.images[selected[i]].remove();
					}
					this.unselectAll();
					GalleryManagement.stopSpinner();
				},
				scope: this
			}), "ids=" + ids);
		}
	},
	deleteGallery: function(deleteLink) {
		if (confirm("Delete gallery?")) {
			this.startSpinner();
			YAHOO.util.Connect.asyncRequest('POST', deleteLink.href, new ResponseHandler({
				success: function(o) {
					var id = GalleryManagement.parseID(this.id);
					var galleryid = document.getElementById("galleryid");
					if (galleryid) {
						galleryid = galleryid.value;
					} else {
						galleryid = 0;
					}
					if (galleryid == id) {
						var galleryBody = document.getElementById("gallery_information");
						galleryBody.parentNode.removeChild(galleryBody);
					}
					var gallery = document.getElementById("gallery_"+id);
					GalleryManagement.stopSpinner();
					gallery.parentNode.removeChild(gallery);
				},
				failure: function(o) {
				},
				scope: deleteLink
			}), "form_key[]=" + SecureForm.getFormKey());
		}
	},
	edit_description: function(descriptionElement) {
		var id = this.parseID(descriptionElement.id);
		this.images[id].edit();
	},
	save_description: function(descriptionElement) {
		var id = this.parseID(descriptionElement.id);
		this.images[id].save();
	},
	move_to: function() {
		var idArray = this.getSelected();
		var ids = idArray.join(',');
		var targetGallery = document.getElementById("selected_gallery").value;
		var currentGallery = document.getElementById("galleryid").value;
		if (targetGallery != currentGallery && targetGallery > 0) {
			this.startSpinner();
			YAHOO.util.Connect.setForm("gallery_form");
			YAHOO.util.Connect.asyncRequest('POST', Nexopia.JSONData['areaBaseUri'] + '/gallery/change_gallery', new ResponseHandler({
				success: function(o) {
					var selected = o.argument;
					for (var i=0;i<selected.length; i++) {
						this.images[selected[i]].remove();
					}
					this.unselectAll();
					this.updateOptions();
					var selectElement = document.getElementById("selected_gallery");
					var currentGallery = document.getElementById("galleryid").value;
					for (var i=0; i<selectElement.options.length; i++) {
						if (selectElement.options[i].value == currentGallery) {
							selectElement.options[i].selected = "selected";
							break;
						}
					}
					GalleryManagement.stopSpinner();
				},
				failure: function(o) {
					GalleryManagement.stopSpinner();
				},
				scope: this,
				argument: idArray
			}),	"ids=" + ids + "&targetgallery=" + targetGallery + "&galleryid=" + document.getElementById("galleryid").value);
		}
	},
	delete_pic: function(deleteLink, id) {
		//if the delete link is an element we pull the data from it, otherwise we assume the proper link and id were passed in
		if (deleteLink.href) {
			id = this.parseID(deleteLink.id);
			deleteLink = deleteLink.href;
		}
		if (confirm("Delete picture?")) {
			this.startSpinner();
			YAHOO.util.Connect.setForm("gallery_form");
			YAHOO.util.Connect.asyncRequest('POST', deleteLink, new ResponseHandler({
				success: function(o) {
					this.remove();
					this.unselectAll();
					GalleryManagement.stopSpinner();
				},
				failure: function(o) {
				},
				scope: this.images[id]
			}), "void=void");
			return true;
		}
		return false;
	},
	select: function(checkBoxElement) {
		this.updateOptions();
	},
	parseID: function(id_string) {
		return id_string.match(/\[(\w+)\]/)[1];
	},
	images: {},
	galleries: {},
	updateOptions: function() {
		var count = this.countSelected();
		var album_cover = document.getElementById("album_cover");
		var profile_pic = document.getElementById("profile_pic");
		var remove = document.getElementById("remove");
		var move_to = document.getElementById("move_to");
		var move_to_select = document.getElementById("selected_gallery");
		//TODO: make the profile pic count based upon the number of currently available profile pic slots.
		
		if (count == 1) {
			if (album_cover) album_cover.disabled = "";
		} else {
			if (album_cover) album_cover.disabled = "disabled";
		}
		if (count >= 1) {
			if (remove) remove.disabled = "";
			if (move_to_select) move_to_select.disabled = "";
		} else {
			if (remove) remove.disabled = "disabled";
			if (move_to_select) move_to_select.disabled = "disabled";
		}
	},
	countSelected: function() {
		var count = 0;
		for (var imageid in this.images) {
			if (this.images[imageid].isSelected()) {
				count++;
			}
		}
		return count;
	},
	selectAll: function(event) {
		for (var imageid in this.images) {
			this.images[imageid].select();
		}
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		this.updateOptions();
	},
	unselectAll: function(event) {
		for (var imageid in this.images) {
			this.images[imageid].unselect();
		}
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		this.updateOptions();
	},
	getSelected: function() {
		var selected = new Array();
		for (var imageid in this.images) {
			if (this.images[imageid].isSelected()) {
				selected.push(imageid);
			}
		}
		return selected;
	},
	startSpinner: function() {
		if (EditGallery) {
			EditGallery.startSpinner();
		}
	},
	stopSpinner: function() {
		if (EditGallery) {
			EditGallery.stopSpinner();
		}
	},
	columns: 6
};

//Setup all of the GalleryImage objects on the page, delete them when they are removed
Overlord.assign({
	minion: "gallery:pic_checkbox",
	load: function(element) {
		var image = new GalleryImage(element);
		if (image && image.node) {
			element.galleryImageID = image.id;
			GalleryManagement.images[image.id] = image;
		}
	},
	unload: function(element) {
		delete GalleryManagement.images[element.galleryImageID];
	},
	order: -1 // need to run 'load' before paginator splits the pages otherwise it doesn't happen for images on pages after the first.

});

Overlord.assign({
	minion: "gallery_management",
	load: GalleryManagement.init,
	scope: GalleryManagement	
});

Overlord.assign({
	minion: "gallery_management:select_all",
	click: GalleryManagement.selectAll,
	scope: GalleryManagement
});
Overlord.assign({
	minion: "gallery_management:select_none",
	click: GalleryManagement.unselectAll,
	scope: GalleryManagement
});
