EditGallery  = {
	init: function () {
		this.galleryName = document.getElementById("gallery_name");
		this.galleryNameInput = document.getElementById("gallery_name_input");
		this.galleryDescription = document.getElementById("gallery_description");
		this.galleryDescriptionInput = document.getElementById("gallery_description_input");
		this.galleryPermissions = document.getElementById("current_gallery_permission");
		this.galleryPermissionsSelect = document.getElementById("gallery_permission_select");
		this.form = document.getElementById("edit_gallery_form");
		this.root = document.getElementById("edit_gallery");
		YAHOO.util.Event.on(this.galleryName, "click", function(e) {this.editName();}, this, true);
		YAHOO.util.Event.on(this.galleryNameInput, "blur", function(e) {this.scheduleSave();}, this, true);
		YAHOO.util.Event.on(this.galleryDescription, "click", function(e) {this.editDescription();}, this, true);
		YAHOO.util.Event.on(this.galleryDescriptionInput, "blur", function(e) {this.scheduleSave();}, this, true);
		YAHOO.util.Event.on(this.galleryPermissions, "click", function(e) {this.editPermissions();}, this, true);
		YAHOO.util.Event.on(this.galleryPermissionsSelect, "change", function(e) {this.scheduleSave();}, this, true);
		YAHOO.util.Event.on(this.galleryPermissionsSelect, "blur", function(e) {this.scheduleSave();}, this, true);
		YAHOO.util.Event.on(document.getElementsByTagName("body")[0], "click", function(e) {this.considerSaving();}, this, true);
	},
	saveOnClick: false,
	reinit: function() {
		this.init();
	},
	editName: function() {
		this.cancelSave();
		this.galleryName.tabIndex=2;
		this.galleryName.style.display = "none";
		this.galleryNameInput.parentNode.style.display = "block";
		this.galleryNameInput.focus();
		this.galleryNameInput.select();
	},
	editDescription: function() {
		this.cancelSave();
		this.galleryDescription.style.display = "none";
		this.galleryDescriptionInput.parentNode.style.display = "block";
		this.galleryDescriptionInput.focus();
		this.galleryDescriptionInput.select();
	},
	editPermissions: function() {
		this.galleryPermissions.style.display = "none";
		this.galleryPermissionsSelect.style.display = "block";
		this.galleryPermissionsSelect.focus();
	},
	scheduleSave: function() {
		this.saveOnClick = true;
	},
	considerSaving: function() {
		if (this.saveOnClick) {
			this.save();
		}
	},
	cancelSave: function() {
		this.saveOnClick = false;
	},
	save: function() {
		YAHOO.util.Connect.setForm(this.form);
		this.startSpinner();
		YAHOO.util.Connect.asyncRequest('POST', this.form.attributes.action.value, new ResponseHandler({
			success: function(o) {
				if (GalleryManagement) {
					GalleryManagement.reinit();
				}
				this.stopSpinner();
			},
			failure: function(o) {
				
			},
			scope: this
		}), "ajax=true");
		this.cancelSave();
	},
	startSpinner: function() {
		var spinner = document.getElementById("edit_gallery_spinner");
		if (spinner) {
			spinner.style.display = "block";
		}
	},
	stopSpinner: function() {
		var spinner = document.getElementById("edit_gallery_spinner");
		if (spinner) {
			spinner.style.display = "none";
		}
	}
};

GlobalRegistry.register_handler("edit_gallery", EditGallery.init, EditGallery, true);
ResponseHandler.registerIDHandler("edit_gallery", EditGallery.reinit, EditGallery, true);