EditGallery  = {
	init: function () {
		this.galleryName = YAHOO.util.Dom.get("gallery_name");
		this.galleryNameInput = YAHOO.util.Dom.get("gallery_name_input");
		this.galleryDescription = YAHOO.util.Dom.get("gallery_description");
		this.galleryDescriptionInput = YAHOO.util.Dom.get("gallery_description_input");
		this.galleryPermissions = YAHOO.util.Dom.get("current_gallery_permission");
		this.galleryPermissionsSelect = YAHOO.util.Dom.get("gallery_permission_select");
		this.form = YAHOO.util.Dom.get("edit_gallery_form");
		this.root = YAHOO.util.Dom.get("edit_gallery");
		YAHOO.util.Event.on(this.galleryName, "click", this.editName, this, true);
		YAHOO.util.Event.on(this.galleryNameInput, "blur", this.scheduleSave, this, true);
		YAHOO.util.Event.on(this.galleryNameInput, "keypress", this.checkKeys, this, true);
		YAHOO.util.Event.on(this.galleryDescription, "click", this.editDescription, this, true);
		YAHOO.util.Event.on(this.galleryDescriptionInput, "blur", this.scheduleSave, this, true);
		YAHOO.util.Event.on(this.galleryDescriptionInput, "keypress", this.checkKeys, this, true);
		YAHOO.util.Event.on(this.galleryPermissions, "click", this.editPermissions, this, true);
		YAHOO.util.Event.on(this.galleryPermissionsSelect, "change", this.scheduleSave, this, true);
		YAHOO.util.Event.on(this.galleryPermissionsSelect, "blur", this.scheduleSave, this, true);
		YAHOO.util.Event.on(this.galleryPermissionsSelect, "keypress", this.checkKeys, this, true);
		YAHOO.util.Event.on(document.getElementsByTagName("body")[0], "click", this.considerSaving, this, true);
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
	checkKeys: function(event) {
		switch (YAHOO.util.Event.getCharCode(event)) {
			case 13: //enter
				this.save();
				YAHOO.util.Event.preventDefault(event);
				break;
			case 27: //escape
				this.cancel();
				YAHOO.util.Event.preventDefault(event);
				break;
		}
	},
	scheduleSave: function() {
		this.saveOnClick = true;
	},
	considerSaving: function() {
		if (this.saveOnClick) {
			this.save();
		}
	},
	cancel: function() {
		this.galleryName.style.display = "block";
		this.galleryNameInput.parentNode.style.display = "none";
		this.galleryNameInput.blur();
		this.galleryDescription.style.display = "block";
		this.galleryDescriptionInput.parentNode.style.display = "none";
		this.galleryDescriptionInput.blur();
		this.galleryPermissions.style.display = "block";
		this.galleryPermissionsSelect.style.display = "none";
		this.galleryPermissionsSelect.blur();
		this.cancelSave();
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
		var spinner = YAHOO.util.Dom.get("edit_gallery_spinner");
		if (spinner) {
			spinner.style.display = "block";
		}
	},
	stopSpinner: function() {
		var spinner = YAHOO.util.Dom.get("edit_gallery_spinner");
		if (spinner) {
			spinner.style.display = "none";
		}
	}
};

Overlord.assign({
	minion: "edit_gallery",
	load: EditGallery.init,
	scope: EditGallery
});