function GalleryFolder(gallery_div) {
	this.dragdrop = new YAHOO.util.DDTarget(gallery_div);
	this.id = GalleryManagement.parseID(gallery_div.id);
	//Truncate the title text if it's too long
	new Truncator(document.getElementById("gallery_title["+this.id+"]"), {width:89,height:20});
	
	//Register the delete link for this gallery to do an ajax delete call through GalleryManagement
	YAHOO.util.Dom.getElementsByClassName("gallery_delete", "a", gallery_div, function(element) {
		YAHOO.util.Event.on(element, "click", function(e) {
			YAHOO.util.Event.preventDefault(e);
			GalleryManagement.deleteGallery(element);
		});
	});
}

GalleryFolder.prototype = {
	takePicture: function(picID) {
		YAHOO.util.Dom.setStyle(picID, "visibility", "");
		if (this.id != GalleryManagement.galleryid) {
			GalleryManagement.startSpinner();
			YAHOO.util.Connect.setForm("gallery_form");
			YAHOO.util.Connect.asyncRequest('POST', Nexopia.JSONData['areaBaseUri'] + '/gallery/change_gallery', new ResponseHandler({
				success: function(o) {
					GalleryManagement.images[this.argument].remove();
					GalleryManagement.stopSpinner();
				},
				failure: function(o) {
					GalleryManagement.stopSpinner();
				},
				argument: picID
			}), "ids=" + picID + "&targetgallery=" + this.id);
		}
	}
};

Overlord.assign({
	minion: "folder_photoframe",
	load: function(element) {
		var folder = new GalleryFolder(element);
		element.galleryFolderID = folder.id;
		GalleryManagement.galleries[folder.id] = folder;
	}, 
	unload: function(element) {
		delete GalleryManagement.galleries[element.galleryFolderID];
	}, 
	order: -1	
});