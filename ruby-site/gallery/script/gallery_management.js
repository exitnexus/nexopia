/*
GlobalRegistry.register_handler("gallery_body", processJsonData, this);
*/

GalleryManagement = {
	initialized: false,
	init: function() {
		if (!this.initialized) {
			this.initialized = true;
			YAHOO.util.Event.on("select_all", "click", this.selectAll, this, true);
			YAHOO.util.Event.on("select_none", "click", this.unselectAll, this, true);
			new YAHOO.widget.TabView("gallery_header_tabs");
			new Paginator("manage_profile_pictures", {width:570, height:123});
			new Paginator("album_page", {width: 570, height:440});
		}

		var galleryid = document.getElementById("galleryid");
		if (galleryid) {
			this.galleryid = galleryid.value;
		}
		var inputs = document.getElementsByTagName("input");
		for (var i=0;i<inputs.length;i++) {
			if (inputs[i].type == "checkbox") {
				var image = new GalleryImage(inputs[i]);
				if (image && image.node) {
					this.images[image.id] = image;
				}
			}
		}
		var actions = YAHOO.util.Dom.getElementsBy(function(element) { return (/^action/.test(element.name) && GalleryManagement[element.id]);}, "", document.getElementById("action_bar"));
		for (var i=0;i<actions.length;i++) {
			function onClick() {GalleryManagement[this.id](); return false;};
			YAHOO.util.Event.addListener(actions[i], "click", onClick);
		}
		YAHOO.util.Event.on("selected_gallery", "change", this.move_to, this, true);
		var galleries = YAHOO.util.Dom.getElementsBy(function(element) {return element.id.match(/gallery\[\d+\]/);}, "div", document.getElementById("gallery_header"));
		for (var i=0;i<galleries.length;i++) {
 			var folder = new GalleryFolder(galleries[i]);
			this.galleries[folder.id] = folder;
		}
		YAHOO.util.Dom.getElementsByClassName("profile_pic_frame", "div", "manage_profile_pictures", function(element) {
			new GalleryManagement.ProfilePicture(element);
		});
		this.updateOptions();
		this.initialized = true;
		YAHOO.util.Dom.setStyle("gallery_header_tabs", "visibility", "visible");
		YAHOO.util.Dom.setStyle("gallery_header_tabs", "position", "relative");
	},
	reinit: function() {
		if (this.initialized) { //don't reinitialize if we were never initialized to begin with
			this.galleries = {};
			this.images = {};
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
		YAHOO.util.Connect.asyncRequest('POST', '/my/gallery/album_cover', new ResponseHandler({
			success: function(o) {
				GalleryManagement.stopSpinner();
			},
			scope: this
		}), "galleryid=" + this.galleryid + "&id="+id);
	},
	profile_pic: function() {},
	remove: function() {
		if (confirm("Delete selected pictures?")) {
			var ids = this.getSelected().join(',');
			this.startSpinner();
			YAHOO.util.Connect.setForm("gallery_form");
			YAHOO.util.Connect.asyncRequest('POST', '/my/gallery/delete_group', new ResponseHandler({
				success: function(o, argument) {
					var selected = this.getSelected();
					for (var i=0;i<selected.length; i++) {
						this.images[selected[i]].remove();
					}
					GalleryManagement.stopSpinner();
					GalleryManagement.rebalanceTable();
				},
				scope: this
			}), "ids=" + ids);
		}
	},
	rebalanceTable: function() {
		var album = YAHOO.util.Dom.get("album_page");
		YAHOO.util.Dom.getElementsByClassName("gallery_pics_page", "ul", album, function(page) {
			var desiredChildren = 0;
			if (page.id == "page_0") {
				desiredChildren = 21;
			} else {
				desiredChildren = 24;
			}
			if (page.childNodes.length > desiredChildren) {
				for (var i=0; i<page.childNodes.length-desiredChildren;i++) {
					page.nextSibling.insertBefore(page.lastChild, page.nextSibling.firstChild);
				}
			} else if (page.childNodes.length < desiredChildren && page.nextSibling) {
				for (var i=0; i<page.childNodes.length-desiredChildren;i++) {
					page.appendChild(page.nextSibling.firstChild);
				}
			}
		});
		
		this.redoRebalance = false;
		var table = document.getElementById("gallery_pics");
		YAHOO.util.Dom.getElementsByClassName("gallery_pics_page", "table", null, function(table) {
			var rows = YAHOO.util.Dom.getElementsBy(function(el) {return true;}, "tr", table);
			for (var i=0;i<rows.length;i++) {
				var cells = YAHOO.util.Dom.getElementsBy(function(el) {return true;}, "td", rows[i]);
				var offBy = this.columns - cells.length;
				if (offBy > 0 && i+1 < rows.length) {
					var nextCells = YAHOO.util.Dom.getElementsBy(function(el) {return true;}, "td", rows[i+1]);
					for (var j=0; j<offBy; j++) {
						if (nextCells[j]) {
							rows[i].appendChild(nextCells[j]);
						} else {
							rows[i+1].parentNode.removeChild(rows[i+1]);
							i++;
							this.redoRebalance = true;
						}
					}
				}
				if (offBy < 0) {
					for (var j=0; j>offBy; j--) {
						rows[i+1].insertBefore(cells[this.columns-j], rows[i+1].firstChild);
					}
				}
			}
		});
		if (this.redoRebalance) {
			this.rebalanceTable();
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
					alert("failure");
				},
				scope: deleteLink
			}), "form_key=" + SecureForm.getFormKey());
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
			YAHOO.util.Connect.asyncRequest('POST', '/my/gallery/change_gallery', new ResponseHandler({
				success: function(o) {
					var selected = o.argument;
					for (var i=0;i<selected.length; i++) {
						this.images[selected[i]].remove();
					}
					this.updateOptions();
					var selectElement = document.getElementById("selected_gallery");
					var currentGallery = document.getElementById("galleryid").value;
					for (var i=0; i<selectElement.options.length; i++) {
						if (selectElement.options[i].value == currentGallery) {
							selectElement.options[i].selected = "selected";
							break;
						}
					}
					GalleryManagement.rebalanceTable();
					GalleryManagement.stopSpinner();
				},
				scope: this,
				argument: idArray
			}),	"ids=" + ids + "&targetgallery=" + targetGallery + "&galleryid=" + document.getElementById("galleryid").value);
		}
	},
	delete_pic: function(deleteLink) {
		if (confirm("Delete picture?")) {
			this.startSpinner();
			YAHOO.util.Connect.setForm("gallery_form");
			YAHOO.util.Connect.asyncRequest('POST', deleteLink.href, new ResponseHandler({
				success: function(o) {
					this.remove();
					GalleryManagement.stopSpinner();
					GalleryManagement.rebalanceTable();
				},
				failure: function(o) {
					alert("failure");
				},
				scope: this.images[this.parseID(deleteLink.id)]
			}), "void=void");
		}
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

GlobalRegistry.register_handler("gallery_header", GalleryManagement.init, GalleryManagement, true);
ResponseHandler.registerIDHandler("gallery_header", GalleryManagement.reinit, GalleryManagement, true);

GalleryManagement.ProfilePicture = function(element) {
	this.dragDropTarget = new YAHOO.util.DDTarget(element);
	if (!GalleryManagement.ProfilePicture.registeredElements) {
		GalleryManagement.ProfilePicture.registeredElements = {};
	}
	GalleryManagement.ProfilePicture.registeredElements[element.id] = this;
	this.element = element;
	if (YAHOO.util.Dom.getElementsByClassName('empty_image', 'div', element).length == 0) {
		var that = this;
		YAHOO.util.Dom.getElementsByClassName("edit_link", "a", element, function(anchor) {
			YAHOO.util.Event.on(anchor, "click", that.showEditPanel, that, true);
		});
		YAHOO.util.Dom.getElementsByClassName('description', 'div', element, function(description) {
			new Truncator(description);
		});
		YAHOO.util.Dom.getElementsByClassName("edit_bar", "div", this.element, function(editBar) {
			new GalleryManagement.EditBar(editBar);
			this.dragDrop = new YAHOO.util.DDProxy(element);
			this.dragDrop.startDrag = function() {
				var dragEl = this.getDragEl();
				YAHOO.util.Dom.setStyle(element.parentNode, "visibility", "hidden");
				GalleryManagement.dragInProgress = true;
			};
			this.dragDrop.onDragDrop = function(e, targetID) {
				var target = YAHOO.util.Dom.get(targetID);
				target = YAHOO.util.Dom.getAncestorByTagName(target, 'li');
				target.parentNode.insertBefore(this.getEl().parentNode, target);
				this.onDragOut(e, targetID);
				GalleryManagement.startSpinner();
				var priority = element.id.match(/profile_pic_frame_(\d+)/)[1];
				var target_priority = YAHOO.util.Dom.get(targetID).id.match(/profile_pic_frame_(\d+)/)[1];
				var form_key = YAHOO.util.Dom.get("manage_profile_pictures_form_key").value;
				GalleryManagement.startSpinner();
				YAHOO.util.Connect.asyncRequest('POST', "/my/gallery/profile/pic/move", {
					success: function() {
						GalleryManagement.stopSpinner();
					}
				}, "target_priority="+target_priority+"&priority="+priority+"&form_key="+form_key);
			};
			this.dragDrop.onDragEnter = function(e, targetID) {
				YAHOO.util.Dom.setStyle(targetID, "border", "2px solid gray");
			};
			this.dragDrop.onDragOut = function(e, targetID) {
				YAHOO.util.Dom.setStyle(targetID, "border", "");
			};
			this.dragDrop.endDrag = function() {
				YAHOO.util.Dom.setStyle(element.parentNode, "visibility", "");
				GalleryManagement.dragInProgress = false;
			};
		});
	}
	var profilePicture = this;
	YAHOO.util.Dom.getElementsByClassName("delete_profile_pic", "a", this.element, function(deleteLink) {
		profilePicture.deleteLink = deleteLink;
		YAHOO.util.Event.on(deleteLink, "click", profilePicture.remove, profilePicture, true);
	});
};
GalleryManagement.ProfilePicture.registeredElements = [];

GalleryManagement.ProfilePicture.prototype = {
	showEditPanel: function(e) {
		alert(4);
		YAHOO.util.Event.preventDefault(e);

		imgSrc = "/images/gallery/" + (Math.floor(Nexopia.JSONData.jsonPageOwner.userid/1000)) + "/" + Nexopia.JSONData.jsonPageOwner.userid + "/" + this.id + ".jpg";
		var editPanel = new YAHOO.profile.EditPhotoDialog("edit_photo_panel", this.id, imgSrc, Nexopia.JSONData.jsonPageOwner);
			
		editPanel.render(document.body);
	
		editPanel.show();
			
		// After the panel is shown and centered, keep it from repositioning as the user scrolls 
		// down the page.
		editPanel.cfg.setProperty("fixedcenter", false);
	},
	//Send an ajax request to set the picture as a profile pic with the priority of the drop point
	handleDrop: function(gallery_pic_id) {
		var priority = this.element.id.match(/profile_pic_frame_(\d+)/)[1];
		var form_key = YAHOO.util.Dom.get("manage_profile_pictures_form_key").value;
		GalleryManagement.startSpinner();
		YAHOO.util.Connect.asyncRequest('POST', "/my/gallery/profile/pic/set", new ResponseHandler({
			success: function() {
				GalleryManagement.stopSpinner();
				GalleryManagement.reinit();
			}
		}), "gallery_pic_id="+gallery_pic_id+"&priority="+priority+"&form_key="+form_key);
	},
	
	//send an ajax request to remove this profile picture
	remove: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		var form_key = YAHOO.util.Dom.get("manage_profile_pictures_form_key").value;
		GalleryManagement.startSpinner();
		YAHOO.util.Connect.asyncRequest('POST', this.deleteLink.href, new ResponseHandler({
			success: function() {
				GalleryManagement.stopSpinner();
				GalleryManagement.reinit();
			}
		}), "form_key="+form_key);
	}
};

GalleryManagement.EditBar = function(element) {
	this.element = element;
	YAHOO.util.Event.on(element.parentNode, "mouseover", this.show, this, true);
	YAHOO.util.Event.on(element.parentNode, "mouseout", this.hide, this, true);
	this.checkBox = YAHOO.util.Dom.getElementsBy(function(el) {return el.type == "checkbox";},"input", this.element)[0];
};

GalleryManagement.EditBar.prototype = {
	//I don't know why but for some reason the inner div centers vertically in the outer div.
	//The result is that we need to position the firstChild element with that in mind.
	show: function(event) {
		//don't show edit bars while dragging another image
		if (!GalleryManagement.dragInProgress) {
			this.element.style.display = "block";
			this.stopAnimation();
			this.animHeight = new YAHOO.util.Anim(this.element, {height: {to: 19}}, this.duration);
			this.animPosition = new YAHOO.util.Anim(this.element.firstChild, {top: {to: 10}}, this.duration);
			this.startAnimation();
		}
	},
	hide: function(event) {
		var ignore = false;
		if (event) {
			var containerXY = YAHOO.util.Dom.getXY(this.element.parentNode);
			var eventXY = YAHOO.util.Event.getXY(event);
			if (eventXY[0] > containerXY[0] &&
					eventXY[1] > containerXY[1] &&
					eventXY[0] < containerXY[0]+this.element.parentNode.offsetWidth &&
					eventXY[1] < containerXY[1]+this.element.parentNode.offsetHeight) {
				ignore = true;
			}
		}
		if ((!this.checkBox || !this.checkBox.checked) && !ignore) {
			this.stopAnimation();
			this.animHeight = new YAHOO.util.Anim(this.element, {height: {to: 0}}, this.duration);
			this.animPosition = new YAHOO.util.Anim(this.element.firstChild, {top: {to: -10}}, this.duration);
			this.startAnimation(true); //pass in true here to hide the element at the end of the animation
		}
	},
	startAnimation: function(hide) {
		this.animHeight.animate();
		this.animPosition.animate();
		this.animHeight.onComplete.subscribe(function() {
			this.animHeight = null;
		}, this, true);
		this.animPosition.onComplete.subscribe(function() {this.animPosition = null;}, this, true);
	},
	stopAnimation: function() {
		if (this.animHeight) {
			this.animHeight.stop();
		}
		if (this.animPosition) {
			this.animPosition.stop();
		}
	},
	getPage: function() {
		var page = YAHOO.util.Dom.getAncestorByClassName("gallery_pics_page");
		if (page) {
			return parseInt(page.id.match(/page_(\d+)/)[0]);
		} else {
			return null;
		}
	},
	duration: 0.1 //animation duration in seconds
};
