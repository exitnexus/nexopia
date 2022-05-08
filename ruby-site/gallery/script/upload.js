var Uploader = {
	flashUploader: null,
	flashDoneLoading: false,
	filesQueue: [],
	init: function() {
		this.uploadQueue = [];
		if (this.hasSuitableFlashVersion()) {
			YAHOO.widget.Uploader.SWFURL = Site.staticFilesURL + "/gallery/flash/uploader.swf";
			this.flashUploader = new YAHOO.widget.Uploader("uploader_overlay");

			var overlay = document.getElementById('uploader_overlay');
			var button = overlay.nextSibling.firstChild.firstChild;
			var uiLayer = YAHOO.util.Dom.getRegion(button); 
			YAHOO.util.Dom.setStyle(button.firstChild, 'visibility', 'visible');
			YAHOO.util.Dom.setStyle(overlay, 'width', (uiLayer.right-uiLayer.left+2) + "px"); 
			YAHOO.util.Dom.setStyle(overlay, 'height', (uiLayer.bottom-uiLayer.top+2) + "px"); 
			
			this.flashUploader.addListener('contentReady', this.configUploader, this, true);
			this.flashUploader.addListener('fileSelect', this.filesQueued, this, true);
			this.flashUploader.addListener('uploadCompleteData', this.uploadFileSuccess, this, true);
			this.flashUploader.addListener('uploadProgress', this.uploadProgress, this, true);
			this.flashUploader.addListener('uploadStart', function(event) {
				YAHOO.util.Dom.setStyle(overlay, 'width', "1px"); 
				YAHOO.util.Dom.setStyle(overlay, 'height', "1px");
				YAHOO.util.Dom.setStyle(button.parentNode, 'display', "none");
			});
		} else {
			this.activateClassicUploader();
		}
	},
	configUploader: function() {
		this.flashUploader.setSimUploadLimit(1);
		this.flashUploader.setAllowMultipleFiles(true);
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
		var noscript = YAHOO.util.Dom.getElementsByClassName("noscript");
		var script = YAHOO.util.Dom.getElementsByClassName("script");

		for (var i = 0; i < noscript.length; i++) {
			YAHOO.util.Dom.addClass(noscript[i], 'script');
			YAHOO.util.Dom.removeClass(noscript[i], 'noscript');
		}

		for (i = 0; i < script.length; i++) {
			YAHOO.util.Dom.addClass(script[i], 'noscript');
			YAHOO.util.Dom.removeClass(script[i], 'script');
		}
		YAHOO.util.Dom.get('upload_queue').style.display = "block";
		var overlay = document.getElementById('uploader_overlay');
		YAHOO.util.Dom.setStyle(overlay, 'display', "none");
		YAHOO.util.Dom.setStyle(overlay.nextSibling.firstChild, 'display', "none");
	},
	files: {},
	totalFiles: 0,
	uploadedFiles: 0,
	filesQueued: function(event) {
		//We need to do this extra processing of keys in order to preserve upload order
		keys = []
		for (var k in event.fileList) {
			keys.push(k);
		}
		keys.sort();
		for (var i=0; i<keys.length; i++) {
			this.fileQueued(event.fileList[keys[i]]);
		}
		
		var overlay = document.getElementById('uploader_overlay');
		YAHOO.util.Dom.setStyle(overlay, 'width', "1px"); 
		YAHOO.util.Dom.setStyle(overlay, 'height', "1px");
		YAHOO.util.Dom.setStyle(overlay.nextSibling, 'display', "none");
		YAHOO.util.Dom.setStyle('terms_warning', 'display', "none");
		
		this.uploadNext();
	},
	fileQueued: function(file) {
		var queue = document.getElementById("gallery_queue");
		var template = new Template("file");
		template.cancel.onclick = function() {Uploader.cancel(this);};
		template.description.innerHTML = file.name;
		template.cancel.id = file.id;
		this.files[file.id] = template;
		this.filesQueue.push(file.id);
		this.totalFiles++;
		queue.appendChild(template.rootElement);
	},
	uploadProgress: function(event) {
		var fileTemplate = this.files[event.id];
		fileTemplate.complete.style.width = (event.bytesLoaded/event.bytesTotal)*100 + "%";
	},
	uploadFileSuccess: function(event) {
		this.uploadedFiles++;
		var success = false;
		r = new ResponseHandler({});
		var serverData = event.data;
		if (serverData.indexOf("Success") == 0) {
			serverData = serverData.slice(0,8);
			success = true;
		}

		r.handleResponse({responseText: serverData});

		if (success) {
			var fileTemplate = this.files[event.id];
			fileTemplate.complete.style.width = "100%";
			fileTemplate.cancel.src = Site.staticFilesURL + "/Gallery/images/check_on.gif";
		} else {
			this.uploadError(event);
		}
		if (this.uploadedFiles == this.totalFiles) {
			this.allUploadsComplete();
		} else {
			this.uploadNext();
		}
	},
	uploadNext: function() {
		var file = this.filesQueue.shift();
		this.flashUploader.upload(file, document.getElementById("gallery_upload_link").href, 'POST', {
			type: "Gallery",
			description: "",
			session: YAHOO.util.Dom.get('session').value,
			selected_gallery: YAHOO.util.Dom.get('selected_gallery').value
		});
	},
	allUploadsComplete: function() {
		YAHOO.util.Dom.setStyle('view_album_links', 'display', 'block');
	},
	uploadError: function(event) {
		YAHOO.util.Dom.setStyle(this.files[event.id].rootElement, 'background-color', '#880000');
	},
	cancel: function(cancelElement) {
		var fileID = cancelElement.id;
		var fileTemplate = this.files[fileID];
		fileTemplate.rootElement.parentNode.removeChild(fileTemplate.rootElement);
		this.flashUploader.removeFile(fileID);
	},
	verifyUploadReady: function() {
		return true;
	},
	verifyFilesSelected: function(event) {
		var fileInput = document.getElementById("Filedata_noflash");
		if(!fileInput.value) {
			YAHOO.util.Event.preventDefault(event);
			alert("No files selected.");
		}
	},
	inProgress: false
};

Overlord.assign({
	minion: "gallery_upload",
	load: Uploader.init,
	scope: Uploader	
});

Overlord.assign({
	minion: "gallery_upload:classic_link",
	click: Uploader.activateClassicUploader,
	scope: Uploader
});

Overlord.assign({
	minion: "gallery_upload:classic_upload",
	click: Uploader.verifyFilesSelected,
	scope: Uploader
});