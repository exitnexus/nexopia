var Uploader = {
	flashUploader: null,
	flashDoneLoading: false,
	init: function() {
		this.uploadQueue = [];
		this.flashUploader = new SWFUpload({
			upload_url: document.getElementById("gallery_upload_link").href,
			flash_url: Site.staticFilesURL + "/Gallery/flash/SWFUpload.swf",
			file_size_limit: "10 MB",
			file_types: "*.jpeg;*.jpg;*.png;*.gif;*.bmp",
			file_types_description: "Picture Files",
			post_params: {
				type: "Gallery",
				session: YAHOO.util.Dom.get("session").value
			},
			debug: true,
			
			swfupload_loaded_handler: function() {return Uploader.flashLoaded();},
			file_queued_handler: function(file) {return Uploader.fileQueued(file);},
			upload_start_handler: function(file) {return Uploader.uploadFileStart(file);},
			upload_progress_handler: function(file, bytesLoaded, bytesTotal) {return Uploader.uploadProgress(file, bytesLoaded, bytesTotal);},
			upload_success_handler: function(file, data) {return Uploader.uploadFileComplete(file);},
			upload_complete_handler: function(file) {},
			upload_error_handler: function(file, errorCode, message) {Uploader.uploadError(file, errorCode, message);}
		});
	},
	flashLoaded: function() {
		var button = YAHOO.util.Dom.get("Filedata");
		button.type = "button";
		button.value = "Browse";
		YAHOO.util.Event.on(button, 'click', this.flashUploader.selectFiles, this.flashUploader, true);
		YAHOO.util.Event.on("upload_queue", 'click', this.startUpload, this, true);
	},
	files: {},
	fileQueued: function(file, queueLength) {
		var queue = document.getElementById("gallery_queue");
		var template = new Template("file");
		template.cancel.onclick = function() {Uploader.cancel(this);};
		template.description.value = file.name;
		template.cancel.id = file.id;
		this.files[file.id] = template;
		this.uploadQueue.push(file.id);
		queue.appendChild(template.rootElement);
		if (this.uploadComplete) {
			document.getElementById("upload_queue").value = "Upload Queue";
			document.getElementById("view_gallery").style.display = "none";
			this.uploadComplete = false;
		}
	},
	uploadFileStart: function(file) {
		this.flashUploader.addFileParam(file.id, "selected_gallery", YAHOO.util.Dom.get("selected_gallery").value);
		this.uploadProgress(file, 0, file.size);
		document.getElementById("upload_queue").disabled = "disabled";
		document.getElementById("upload_queue").value = "Uploading...";
		return true;
	},
	uploadProgress: function(file, bytesLoaded, bytesTotal) {
		var fileTemplate = this.files[file.id];
		fileTemplate.complete.style.width = (bytesLoaded/bytesTotal)*100 + "%";
	},
	uploadFileComplete: function(file) {
		this.uploadProgress(file, file.size, file.size);
		this.files[file.id].description.disabled = "disabled";
		this.files[file.id].cancel.src = Site.staticFilesURL + "/Gallery/images/check_on.gif";
		if (this.flashUploader.getStats().files_queued > 0) {
			this.flashUploader.startUpload();
		} else { //otherwise mark the queue complete
			this.uploadQueueComplete();
		}
	},
	uploadQueueComplete: function() {
		document.getElementById("upload_queue").disabled = false;
		document.getElementById("upload_queue").value = "Upload Complete";
		this.uploadComplete = true;
		this.inProgress = false;
		var selectedGallery = document.getElementById("selected_gallery");
		var viewGalleryLink = document.getElementById("view_gallery");
		viewGalleryLink.href = "/my/gallery/" + selectedGallery.value;
		viewGalleryLink.innerHTML = 'Manage, "' + selectedGallery.options[selectedGallery.selectedIndex].innerHTML + '"';
		viewGalleryLink.style.display = "inline";
	},
	uploadError: function(file, errorCode, message) {
	},
	uploadCancel: function() {},
/*	uploadParams: function(file) {
		var description = escape(this.files[file.id].description.value);
		var galleryid = document.getElementById("selected_gallery").value;
		var session = document.getElementById("session").value;
		var iv = document.getElementById("iv").value;
		var form_key = SecureForm.getFormKey();
		return "type=Gallery&selected_gallery="+galleryid+"&session="+session+"&iv="+iv+"&description="+description+"&form_key="+form_key;
	},*/
	cancel: function(cancelElement) {
		var fileID = cancelElement.id;
		var fileTemplate = this.files[fileID];
		fileTemplate.rootElement.parentNode.removeChild(fileTemplate.rootElement);
		if (this.inProgress) {
			if (!this.flashUploader.getStats().in_progress > 0) { //we were uploading and cancelled the active upload
				if (this.flashUploader.getStats().files_queued > 0) { //start uploading the next file
					this.flashUploader.startUpload();
				} else { //otherwise mark the queue complete
					this.uploadQueueComplete();
				}
			}
		}
	},
	startUpload: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		if (this.verifyUploadReady()) {
			if (this.flashUploader.getStats().files_queued > 0) {
				this.inProgress = true;
				this.flashUploader.startUpload();
			} else {
				alert("You must select a file to upload.");
			}
		}
	},
	verifyUploadReady: function() {
		if (document.getElementById("certify").checked) {
			if (parseInt(document.getElementById("selected_gallery").value) > 0) {
				return true;
			} else {
				alert("You must select a valid gallery to upload to.");
			}
		} else {
			alert("You must certify you have the right to distribute these pictures.");
			return false;
		}
	},
	inProgress: false
};
GlobalRegistry.register_handler("gallery_upload", Uploader.init, Uploader, true);