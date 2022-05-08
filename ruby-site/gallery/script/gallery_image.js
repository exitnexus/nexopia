function GalleryImage(checkBoxElement) {
	this.checkBox = checkBoxElement;
	function checkBoxOnClick(){GalleryManagement.select(this);}
	YAHOO.util.Event.addListener(this.checkBox, "click", checkBoxOnClick); 
	
	this.id = GalleryManagement.parseID(checkBoxElement.id);
	
	this.node = document.getElementById(this.id);
	if (!this.node) {
		return false;
	}

	//some checkboxes might be checked by default by the browser, show them
	if (this.checkBox.checked) {
		this.select();
	}
	this.deleteLink = document.getElementById("delete_link["+this.id+"]");
	
	YAHOO.util.Event.addListener(this.deleteLink, "click", function(event) {
		YAHOO.util.Event.preventDefault(event);
		GalleryManagement.delete_pic(this);
	}); 

	this.node = document.getElementById(this.id);
	YAHOO.util.Event.on("draggable_image["+this.id+"]", "click", YAHOO.util.Event.preventDefault);
	this.dragdrop = new ImageDrag(this.node);
	this.calculatePosition();
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
	    YAHOO.util.Connect.asyncRequest('GET', Nexopia.JSONData['areaBaseUri'] + "/gallery/jsonuser/", callbacks);
	},
	showEditPanel: function(e) {
		YAHOO.util.Event.preventDefault(e);
		var editPanel = new YAHOO.widget.Panel('edit_photo_panel', { 
			fixedcenter: true,
			constraintoviewport: true,  
			underlay:"shadow",  
			modal:true,
			close:false,  
			visible:false,  
			draggable:false
		});
		
		editPanel.render(document.body);
		
		var uri = Nexopia.JSONData['areaBaseUri'] +"/gallery/pic/" + this.id + "/edit";
		var id = this.id;
		GalleryManagement.startSpinner();
		YAHOO.util.Connect.asyncRequest('GET', uri, new ResponseHandler({
			success: function() {
				GalleryManagement.EditPhotoPanel.panel = editPanel;
				Nexopia.Utilities.withImage('edit_img', {
					load: function(img) {
						editPanel.show();
						if (YAHOO.env.ua.ie) {
							//XXX: I hate using a magic number here but everything else I do seems to make ie angry.
							editPanel.element.firstChild.style.width = Math.max(img.width, 400) + "px";
						}
						editPanel.center();
						GalleryManagement.stopSpinner();
					}
				});
			},
			failure: function() {
				GalleryManagement.stopSpinner();
			}
		}));
	},
	isSelected: function() {
		return this.checkBox.checked;
	},
	select: function() {
		this.checkBox.checked = "checked";
	},
	unselect: function() {
		this.checkBox.checked = "";
	},
	remove: function() {
		this.node.parentNode.removeChild(this.node);
		if (GalleryManagement.images) {
			delete GalleryManagement.images[this.id];
		}
	},
	move: function() {
		this.debug = true;
		var originalPosition = this.position;
		this.calculatePosition();
		if (this.position != originalPosition) {
			GalleryManagement.startSpinner();
			YAHOO.util.Connect.setForm("gallery_form");
			YAHOO.util.Connect.asyncRequest('POST', Nexopia.JSONData['areaBaseUri'] + '/gallery/'+document.getElementById("galleryid").value + '/move_pic', {
				success: function(o, argument) {
					GalleryManagement.stopSpinner();
				},
				failure: function(o) {
					alert("failure");
				},
				scope: this
			}, "id=" + this.id + "&position=" + this.position + "&form_key[]=" + SecureForm.getFormKey());
		}
	},
	calculatePosition: function() {
		var list = this.node.parentNode;
		var found = false;
		for (var i=0; i<list.childNodes.length; i++) {
			if (list.childNodes[i].id == this.node.id) {
				this.position = i+1;
				found = true;
			} else {
				var gimage = GalleryManagement.images[list.childNodes[i].id];
				if(gimage) {
					gimage.position = i+1;
				}
			}
		}
		if(found) {
			return this.position;
		}
		throw "Unable to calculate the position of image " + this.node.id;
	}
};