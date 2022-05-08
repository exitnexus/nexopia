/////////////////////////////////////////////////////////////////

function GalleryPic(jsonObj){
	for (key in jsonObj){
		this[key] = jsonObj[key];
	}
	this.form_key = YAHOO.util.Dom.get("manage_profile_pictures_form_key").value;
	this.eventHandlers = {changeDescription: [], changeImage: [], remove: []};
}

GalleryPic.prototype = {
	addHandler: function(name, fn, scope){
		this.eventHandlers[name].push({fn: fn, scope: scope});
	},
	createEvent: function(name){
		for(index in this.eventHandlers[name]){
			handler = this.eventHandlers[name][index];
			handler.fn.call(handler.scope);
		}
	},
	changeDescription: function(value){
		var args = "id=" + this.id + "&description=" + value;
		this.sendCommand('/my/gallery/pic/update_description', args);
		this.createEvent("changeDescription");
	},
	crop: function(x,y,w,h){
		var display = "x=" + x + "&y=" + y + "&w=" + w + "&h=" + h;
		this.sendCommand("/my/gallery/crop/" + this.id, display);
		this.createEvent("changeImage");
	},
	moveTo: function(gallery){
		alert("Moving " + this.id + " to " + gallery.name);
		
		var args = "ids=" + this.id + "&targetgallery=" + gallery.id + "&galleryid=" + document.getElementById("galleryid").value;
		this.sendCommand('/my/gallery/change_gallery', args);
		this.createEvent("remove");
	},
	deletePic: function(){
		this.sendCommand("/my/gallery/delete/" + this.id, "");
		this.createEvent("remove");
	},
	setAsAlbumCover: function(){
		this.sendCommand("/my/gallery/album_cover", "id=" + this.id);
	},
	sendCommand: function (uri, variables){
		variables += "&form_key=" + this.form_key;
		var transaction = YAHOO.util.Connect.asyncRequest('POST', uri, null, variables); 
	}	
};

function GalleryImage(checkBoxElement) {
	this.checkBox = checkBoxElement;
	function checkBoxOnClick(){GalleryManagement.select(this);};
	YAHOO.util.Event.addListener(this.checkBox, "click", checkBoxOnClick); 
	
	this.id = GalleryManagement.parseID(checkBoxElement.id);
	
	this.galleryPic = galleryPics[this.id];
	this.galleryPic.addHandler("remove", this.remove, this);
	this.galleryPic.addHandler("changeDescription", this.changeDescription, this);
	
	
	this.node = document.getElementById(this.id);
	if (!this.node) {
		return false;
	}
	this.editBar = new GalleryManagement.EditBar(YAHOO.util.Dom.getAncestorByClassName(this.checkBox,"edit_bar"));
	//some checkboxes might be checked by default by the browser, show them
	if (this.checkBox.checked) {
		this.select();
	}
	this.deleteLink = document.getElementById("delete_link["+this.id+"]");
	
	function deleteLinkOnClick() {GalleryManagement.delete_pic(this);return false;};
	YAHOO.util.Event.addListener(this.deleteLink, "click", deleteLinkOnClick); 

	//var myTooltip = new YAHOO.widget.Tooltip("myTooltip", { context:"myContextEl", text:"Some tooltip text" } ); 
	new YAHOO.widget.Tooltip(document.createElement("div"), {context: this.deleteLink, text: "Delete"});
	this.descriptionText = document.getElementById("description["+this.id+"]");
	new Truncator(this.descriptionText, {fudgeFactor: 4});
	
	function descTextOnClick(){GalleryManagement.edit_description(this);};
	YAHOO.util.Event.addListener(this.descriptionText, "click", descTextOnClick);
	
	this.editBox = document.getElementById("edit["+this.id+"]");
	function editBoxOnBlur(){GalleryManagement.save_description(this);};
	YAHOO.util.Event.addListener(this.editBox, "blur", editBoxOnBlur); 
	
	this.node = document.getElementById(this.id);
	YAHOO.util.Event.on("draggable_image["+this.id+"]", "click", YAHOO.util.Event.preventDefault);
	this.dragdrop = new ImageDrag(this.node);
	this.calculatePosition();
	
	this.editButton = document.getElementById("edit_link_" + this.id);
	YAHOO.util.Event.on(this.editButton, "click", this.showEditPanel, this, true);
	
}

GalleryImage.prototype = {
	getUserData: function(func) {
	    var callbacks = {
	        success : function (o) {
	            var messages = [];
	            try {
	                messages = YAHOO.lang.JSON.parse(o.responseText);
	            }
	            catch (x) {
	                alert("JSON Parse failed!");
	                return;
	            }
	            o.argument.call(this, messages);
	        },
	
	        failure : function (o) {
	            if (!YAHOO.util.Connect.isCallInProgress(o)) {
	                alert("Async call failed!");
	            }
	        },
	        argument: func,
	        timeout : 3000,
	        scope: this
	    };
	
	    // Make the call to the server for JSON data
	    YAHOO.util.Connect.asyncRequest('GET', "/my/gallery/jsonuser/", callbacks);
	},
	showEditPanel: function(e) {
		YAHOO.util.Event.preventDefault(e);

		imgSrc = "/images/gallery/" + (Math.floor(Nexopia.JSONData.jsonPageOwner.userid/1000)) + "/" + Nexopia.JSONData.jsonPageOwner.userid + "/" + this.id + ".jpg";
		var editPanel = new YAHOO.profile.EditPhotoDialog("edit_photo_panel", this.id, imgSrc, Nexopia.JSONData.jsonPageOwner);
			
		editPanel.render(document.body);
	
		editPanel.show();
			
		// After the panel is shown and centered, keep it from repositioning as the user scrolls 
		// down the page.
		editPanel.cfg.setProperty("fixedcenter", false);
	},
	isSelected: function() {
		return this.checkBox.checked;
	},
	select: function() {
		this.checkBox.checked = "checked";
		this.editBar.show();
	},
	unselect: function() {
		this.checkBox.checked = "";
		this.editBar.hide();
	},
	remove: function() {
		this.node.parentNode.removeChild(this.node);
		if (GalleryManagement.images) {
			delete GalleryManagement.images[this.id];
		}
	},
	move: function() {
		GalleryManagement.rebalanceTable();
		this.debug = true;
		var originalPosition = this.position;
		this.calculatePosition();
		if (this.position != originalPosition) {
			GalleryManagement.startSpinner();
			YAHOO.util.Connect.setForm("gallery_form");
			YAHOO.util.Connect.asyncRequest('POST', '/my/gallery/'+document.getElementById("galleryid").value + '/move_pic', {
				success: function(o, argument) {
					GalleryManagement.stopSpinner();
				},
				failure: function(o) {
					alert("failure");
				},
				scope: this
			}, "id=" + this.id + "&position=" + this.position);
		}
	},
	calculatePosition: function() {
		var currentPage = this.node.parentNode;
		//number of previous pages times number of images per page minus the gallery edit box pics spaces
		var page = parseInt(currentPage.id.match(/page_(\d+)/)[1]);
		if (page == 0) {
			this.position = -1;
		} else {
			this.position = page*24-4;
		}
		for (var i=0; i<currentPage.childNodes.length; i++) {
			if (currentPage.childNodes[i].id == this.id) {
				break;
			} 
			if (currentPage.childNodes[i].tagName == "LI") {
				this.position++;
			}
		}
	},
	edit: function() {
		this.editBox.style.display = "block";
		this.descriptionText.style.display = "none";
		this.editBox.select();
		this.editBox.focus();
	},
	changeDescription: function() {
		this.descriptionText.innerHTML = this.editBox.value;
		this.descriptionText.truncator = false;
		new Truncator(this.descriptionText);
	},
	save: function() {
		this.editBox.style.display = "none";
		this.editBox.value = this.editBox.value.replace(/^\s+|\s+$/g, '');
		this.descriptionText.style.display = "block";
		if (this.editBox.value != this.descriptionText) {
			GalleryManagement.startSpinner();
			YAHOO.util.Connect.setForm("gallery_form");
			YAHOO.util.Connect.asyncRequest('POST', '/my/gallery/pic/update_description', {
				success: function(o) {
					this.changeDescription();
					GalleryManagement.stopSpinner();
				},
				failure: function(o) {
				},
				scope: this
			}, "id="+this.id+"&description="+this.editBox.value);
		}
	}
};