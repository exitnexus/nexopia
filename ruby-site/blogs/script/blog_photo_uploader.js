BlogPhotoUploader = function(element) {
	var container = document.getElementById('add_photo_container');
	var display = this.hasSuitableFlashVersion &&
		(!container || (container.className != 'hidden'));
	if (display) {
		this.photo_id = element.parentNode.parentNode.id;
		YAHOO.widget.Uploader.SWFURL = Site.staticFilesURL + "/gallery/flash/uploader.swf";
		this.flashUploader = new YAHOO.widget.Uploader("uploader_overlay"+this.photo_id);
		var overlay = document.getElementById('uploader_overlay'+this.photo_id);
		var button = overlay.nextSibling;
		var uiLayer = YAHOO.util.Dom.getRegion(button); 

		YAHOO.util.Dom.setStyle(overlay, 'width', (uiLayer.right-uiLayer.left+2) + "px"); 
		YAHOO.util.Dom.setStyle(overlay, 'height', (uiLayer.bottom-uiLayer.top+2) + "px"); 

		this.flashUploader.addListener('contentReady', this.configUploader, this, true);
		this.flashUploader.addListener('fileSelect', this.startUpload, this, true);
		this.flashUploader.addListener('uploadCompleteData', this.uploadComplete, this, true);
	}

};

BlogPhotoUploader.prototype = {
	flashUploader: null,
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
	startUpload: function(event) {
		this.showSpinner();
		this.flashUploader.uploadAll(document.getElementById("swf_upload_path"+this.photo_id).value, 'POST', {
			type: "Blogs",
			description: "",
			photo_id: this.photo_id,
			session: YAHOO.util.Dom.get('session'+this.photo_id).value
		});
	},
	uploadComplete: function(event) {
		var success = false;
		r = new ResponseHandler({});

		var serverData = event.data;
		if (serverData.indexOf("Success") == 0) {
			serverData = serverData.slice(8,serverData.length);
			success = true;
		}

		r.handleResponse({responseText: serverData});

		this.panel.close();
	},

	showSpinner: function() {
		this.panel = new DivPanel({div_id:'blog_uploading_spinner' + this.photo_id});
		this.panel.open();
	}
};

Overlord.assign({
	minion: "blogs:photo_uploader",
	load: function(element) {
		new BlogPhotoUploader(element);
	}
});