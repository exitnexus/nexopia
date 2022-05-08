EditPicturePanel = {
	form_key: null,
	description: null,
	smallSpinner: null,
	spinnerBody: "<div id='edit_picture_panel' class='spinner'><img src='"+Site.staticFilesURL+"/nexoskel/images/large_spinner.gif'/></div>",
	baseURI: Nexopia.areaBaseURI(), 
	init: function(galleryPicID, functionPanel, config) {
		if(config) {
			YAHOO.lang.augmentObject(this, config, true);
		}
		this.galleryPicID = galleryPicID;
		this.functionPanel = functionPanel;
		this.overlay = new YAHOO.widget.Panel("picture_edit_overlay", {
			fixedcenter: false,
			visible: true,
			modal:true,
			close:false,
			draggable:false,
			underlay: "none"
		});
		this.overlay.setBody(this.spinnerBody);
		this.overlay.render(document.body);
		
		// For some reason, the minified version of container doesn't seem to be able to set the zIndex properly,
		// so we are doing that again after the panel has been initialized.
		this.overlay.cfg.setProperty("zIndex", 500);

		// Fix for a problem in Firefox 2 where the panel won't show.
		if(YAHOO.env.ua.gecko < 1.9)
		{
			YAHOO.util.Dom.setStyle(this.overlay.innerElement, 'position', 'relative');
		}
				
		this.overlay.center();
		YAHOO.util.Connect.asyncRequest('GET', this.baseURI + '/gallery/pic/' + this.galleryPicID + '/edit?function_panel='+this.functionPanel, new ResponseHandler({
			success: function(o) {
				this.overlay.element = this.overlay.element.firstChild; //XXX: This should not be necessary but the original element reports an offsetWidth/Height of 0 in FF3
				if (this.galleryPicID == "first") {
					this.galleryPicID = Nexopia.json(document.getElementById('edit_picture_panel'));
					var thumbnail = YAHOO.util.Dom.get('profile_picture_thumbnail');
					if (thumbnail) {
						thumbnail.src = thumbnail.src.substring(0, thumbnail.src.lastIndexOf('/')+1) + this.galleryPicID + '.jpg';
					}
				}
				this.overlay.center();
			},
			failure: function(o) {
				this.overlay.destroy();
			},
			scope: this
		}));
	},
	initImageCrop: function(img) {
		var imgCrop = Nexopia.json(img);
		var cropperConfig = null;
		if (imgCrop) {
			cropperConfig = {
				ratio: true,
				initWidth: Math.round(imgCrop.w*img.width),
				initHeight: Math.round(imgCrop.h*img.height),
				initialXY: [Math.round(imgCrop.x*img.width), Math.round(imgCrop.y*img.height)],
				status: false
			};
		} else {
			var imgRatio = img.width/img.height;
			if (imgRatio < 1.2) {
				cropperConfig = {
					ratio: true,
					initWidth: img.width,
					initHeight: img.width/1.2,
					initialXY: [0, (img.height-(img.width/1.2))/2],
					status: false
				};
			} else {
				cropperConfig = {
					ratio: true,
					initHeight: img.height,
					initWidth: img.height*1.2,
					initialXY: [(img.width-(img.height*1.2))/2, 0],
					status: false
				};
			}
		}
		this.cropper = new YAHOO.widget.ImageCropper(img, cropperConfig);
	},
	saveImageCrop: function() {
		rawCoords = this.cropper.getCropCoords();
		var width = parseInt(this.cropper.getEl().width, 10);
		var height = parseInt(this.cropper.getEl().height, 10);

		var w = rawCoords.width/width;
		var x = rawCoords.left/width;
		var h = rawCoords.width/1.2/height; //we don't really believe the image cropper preserved 
											//the proper ratio so we'll just take height based on width.
											//If it actually did get it right they should be the same anyhow.
		var y = rawCoords.top/height;
		y = Math.min(y, 1-h); //make sure our fudging hasn't put the total height more than 1

		this.overlay.setBody(this.spinnerBody);
		this.overlay.center();
		YAHOO.util.Connect.asyncRequest('POST', this.baseURI + "/gallery/pic/"+this.galleryPicID+"/crop", new ResponseHandler({
			success: function() {
				var that = this;
				YAHOO.util.Dom.getElementsBy(function(element) {
					var elementsrc;
					var qindex = element.src.lastIndexOf("?reload=");
					if(qindex == -1) {
						elementsrc = element.src;
					} else {
						elementsrc = element.src.substring(0,qindex);
					}
					return (elementsrc.lastIndexOf(that.galleryPicID) == elementsrc.length - (that.galleryPicID.toString().length + 4));
				}, 'img', null, function(element) {
					element.src = element.src + "?reload=" + Math.random();
				});
				this.overlay.destroy();
			},
			failure: function() {
				this.overlay.destroy();
			},
			scope: this
		}), "form_key[]="+this.form_key+"&x="+x+"&y="+y+"&w="+w+"&h="+h);
	},
	makeSignPic: function(event, element) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		this.showSpinner();
		YAHOO.util.Connect.asyncRequest('POST', this.baseURI + "/gallery/pic/"+this.galleryPicID+"/sign", new ResponseHandler({
			success: function() {
				this.stopSpinner();
			},
			failure: function() {
				this.stopSpinner();
			},
			scope: this
		}), "form_key[]="+this.form_key);
	},
	makeAlbumCover: function(event, element) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		this.showSpinner();
		YAHOO.util.Connect.asyncRequest('POST', this.baseURI + "/gallery/pic/"+this.galleryPicID+"/album_cover", new ResponseHandler({
			success: function() {
				this.stopSpinner();
			},
			failure: function() {
				this.stopSpinner();
			},
			scope: this
		}), "form_key[]="+this.form_key+"&refresh=album_info");
	},
	makeProfilePicture: function(event, element) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		this.showSpinner();
		YAHOO.util.Connect.asyncRequest('POST', this.baseURI + "/gallery/pic/"+this.galleryPicID+"/profile_picture", new ResponseHandler({
			success: function() {
				this.stopSpinner();
			},
			failure: function() {
				this.stopSpinner();
			},
			scope: this
		}), "form_key[]="+this.form_key);
	},
	deletePicture: function(event, element) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		if (!confirm("Permanently remove this picture?")) {
			return;
		}
		this.overlay.setBody(this.spinnerBody);
		this.overlay.center();
		YAHOO.util.Connect.asyncRequest('POST', this.baseURI + "/gallery/pic/"+this.galleryPicID+"/delete?source=film_view", new ResponseHandler({
			success: function() {
				this.overlay.destroy();
			},
			failure: function() {
				this.overlay.destroy();
			},
			scope: this
		}), "form_key[]="+this.form_key+"&refresh=gallery");
	},
	saveDescription: function(event, element) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		YAHOO.util.Connect.asyncRequest('POST', this.baseURI + "/gallery/pic/"+this.galleryPicID+"/description", new ResponseHandler({
			success: function() {
				this.stopSpinner();
			},
			failure: function() {
				this.stopSpinner();
			},
			scope: this
		}), "form_key[]="+this.form_key+"&description="+Nexopia.Utilities.escapeURI(this.description.value));
	},
	showSpinner: function() {
		if (this.smallSpinner) {
			YAHOO.util.Dom.setStyle(this.smallSpinner, 'display', 'inline');
		}
	},
	stopSpinner: function() {
		if (this.smallSpinner) {
			YAHOO.util.Dom.setStyle(this.smallSpinner, 'display', 'none');
		}
	}
};

//If you click done, close the panel.
Overlord.assign({
	minion: "edit_picture:done",
	click: EditPicturePanel.saveImageCrop,
	scope: EditPicturePanel
});

//Setup the click handler for the sign pic link
Overlord.assign({
	minion: "edit_picture:signpic",
	click: EditPicturePanel.makeSignPic,
	scope: EditPicturePanel
});

Overlord.assign({
	minion: "edit_picture:album_cover",
	click: EditPicturePanel.makeAlbumCover,
	scope: EditPicturePanel
});

Overlord.assign({
	minion: "edit_picture:profile_picture",
	click: EditPicturePanel.makeProfilePicture,
	scope: EditPicturePanel
});

Overlord.assign({
	minion: "edit_picture:delete",
	click: EditPicturePanel.deletePicture,
	scope: EditPicturePanel
});


//Once the image has loaded, center the overlay and load the image cropper
Overlord.assign({
	minion: "edit_picture:image",
	load: function(element) {
		Nexopia.Utilities.withImage(element, {
			load: function(img) {
				EditPicturePanel.overlay.center();
				EditPicturePanel.initImageCrop(img);
			}
		});
	}
});

Overlord.assign({
	minion: "edit_picture:form_key",
	load: function(el) {
		EditPicturePanel.form_key = el.value;
	}
});

Overlord.assign({
	minion: "edit_picture:spinner",
	load: function(el) {
		EditPicturePanel.smallSpinner = el;
	}
});


Overlord.assign({
	minion: "edit_picture:description",
	load: function(element) {
		EditPicturePanel.description = element;
		if (element.innerHTML == "") {
			element.innerHTML = "Enter description here...";
		}
	},
	click: function(event, element) {
		if (element.innerHTML == "Enter description here...") {
			element.innerHTML = "";
		}
	},
	blur: EditPicturePanel.saveDescription,
	scope: EditPicturePanel
});

EditPicturePanel.EditLink = function(linkElement) {
	this.linkElement = linkElement;
	this.pictureID = Nexopia.json(linkElement)[0];
	this.functionPanel = Nexopia.json(linkElement)[1];
};

EditPicturePanel.EditLink.prototype = {
	edit: function(event, element) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		EditPicturePanel.init(this.pictureID, this.functionPanel);
	}
};

Overlord.assign({
	minion: "edit_picture:edit_link",
	load: function(element) {
		var edit_link = new EditPicturePanel.EditLink(element);
		YAHOO.util.Event.on(element, 'click', edit_link.edit, null, edit_link);
	}
});