//require select.js

var ProfilePictureUploader = {
	flashUploader: null,
	init: function() {
		this.uploadQueue = [];
		if (this.hasSuitableFlashVersion()) {
			YAHOO.widget.Uploader.SWFURL = Site.staticFilesURL + "/gallery/flash/uploader.swf";
			this.flashUploader = new YAHOO.widget.Uploader("uploader_overlay");

			var overlay = document.getElementById('uploader_overlay');
			var button = document.getElementById('choose_picture-button').parentNode;  // .parentNode added to make IE7 happy.
			var uiLayer = YAHOO.util.Dom.getRegion(button); 
						
			YAHOO.util.Dom.setStyle(overlay, 'top', button.offsetTop + "px");
			YAHOO.util.Dom.setStyle(overlay, 'left', button.offsetLeft + "px");

			YAHOO.util.Dom.setStyle(overlay, 'width', (uiLayer.right-uiLayer.left+2) + "px"); 
			YAHOO.util.Dom.setStyle(overlay, 'height', (uiLayer.bottom-uiLayer.top+2) + "px"); 
			
			this.flashUploader.addListener('contentReady', this.configUploader, this, true);
			this.flashUploader.addListener('fileSelect', this.startUpload, this, true);
			this.flashUploader.addListener('uploadCompleteData', this.uploadComplete, this, true);
			
			// If the uploader overlay is hidden, as in the gallery overlay code, the underlying button should do nothing
			YAHOO.util.Event.addListener(document.getElementById('choose_picture-button'), "click", this.disabled);
		} else {
			this.activateClassicUploader();
		}
	},
	configUploader: function() {
		this.flashUploader.setFileFilters(["*.jpeg;*.jpg;*.png;*.gif;*.bmp"], "Picture Files");
	},
	//minimum version is 9.0.45
	hasSuitableFlashVersion: function() {
		if (YAHOO.util.FlashDetect.major > 9) {
			return true;
		} else if (YAHOO.util.FlashDetect.major == 9) {
			if (YAHOO.util.FlashDetect.minor > 0 || YAHOO.util.FlashDetect.revision >= 45) {
				return true;
			}
		}
		return false;
	},
	activateClassicUploader: function() {
		var overlay = document.getElementById('uploader_overlay');
		YAHOO.util.Dom.setStyle(overlay, 'display', "none");
		YAHOO.util.Dom.setStyle('choose_picture', 'display', "none");
		YAHOO.util.Dom.setStyle('file_upload', 'display', "inline");
		YAHOO.util.Dom.setStyle('classic_file_upload', 'display', "block");
		if(GallerySelect) {
			GallerySelect.classicUploader = true;
		}
	},
	startUpload: function(event) {
		this.showSpinner();
		this.flashUploader.uploadAll(document.getElementById("swf_upload_path").value, 'POST', {
			type: "Userpics",
			description: "",
			session: YAHOO.util.Dom.get('session').value,
			selected_gallery: YAHOO.util.Dom.get('selected_gallery').value
		});
	},
	uploadComplete: function(event) {

		var success = false;
		r = new ResponseHandler({});
		var serverData = event.data;
		if (serverData.indexOf("Success") == 0) {
			serverData = serverData.slice(0,8);
			success = true;
		}

		r.handleResponse({responseText: serverData});
		NexopiaPanel.current.close();
		
		if (success) {
			EditPicturePanel.init('first', 'profile_picture');
			YAHOO.util.Connect.asyncRequest('POST', Nexopia.areaBaseURI() + '/pictures/refresh', new ResponseHandler({}));
		}
		
	},
	showSpinner: function() {
		YAHOO.util.Dom.setStyle('uploader_overlay', 'width', "1px"); 
		YAHOO.util.Dom.setStyle('uploader_overlay', 'height', "1px"); 
		
		YAHOO.util.Dom.setStyle('add_profile_pic_form', 'visibility', 'hidden');
		YAHOO.util.Dom.setStyle('add_profile_pic', 'background-image', 'url('+Site.staticFilesURL + '/nexoskel/images/spinner.gif)');
		YAHOO.util.Dom.setStyle('add_profile_pic', 'background-position', 'center center');
		YAHOO.util.Dom.setStyle('add_profile_pic', 'background-repeat', 'no-repeat');
	},
	disabled: function(e) {
		// Block the event, e.g. because we're in the middle of creating an album.  Display a message?
		YAHOO.util.Event.preventDefault(e);
	}
};

Overlord.assign({
	minion: "add_profile_pic", 
	load: ProfilePictureUploader.init,
	scope: ProfilePictureUploader
});

// Toggle the "advanced" section of the profile pic uploader to be open or closed.
Overlord.assign({
	minion: "add_profile_pic:advanced_link",
	click: function() {
		var advanced_content = YAHOO.util.Dom.get("advanced_content");
		var display_state = YAHOO.util.Dom.getStyle(advanced_content, "display");
		var up_arrow = YAHOO.util.Dom.get("up_arrow");
		var down_arrow = YAHOO.util.Dom.get("down_arrow");
		
		// If we're not currently showing the advanced box show it.
		if (display_state.toLowerCase() == "none") {
			YAHOO.util.Dom.setStyle(advanced_content, "display", "block");
			YAHOO.util.Dom.setStyle(up_arrow, "display", "inline");
			YAHOO.util.Dom.setStyle(down_arrow, "display", "none");
		} else {
			YAHOO.util.Dom.setStyle(advanced_content, "display", "none");			
			YAHOO.util.Dom.setStyle(up_arrow, "display", "none");
			YAHOO.util.Dom.setStyle(down_arrow, "display", "inline");
		}
	}
	
});

Overlord.assign({
	minion: "add_profile_pic:cancel_link",
	click: function(){
		NexopiaPanel.current.close();
	}
});

Overlord.assign({
	minion: "add_profile_pic:classic_interface",
	click: function(event){
		YAHOO.util.Event.preventDefault(event);
		ProfilePictureUploader.activateClassicUploader();
	}
});