function GalleryFolder(gallery_div) {
	this.dragdrop = new YAHOO.util.DDTarget(gallery_div);
	this.id = GalleryManagement.parseID(gallery_div.id);
	//Truncate the title text if it's too long
	new Truncator(document.getElementById("gallery_title["+this.id+"]"), {originalDimensions: {width:89,height:15}});
	//Create an EditBar object for the edit bar
	YAHOO.util.Dom.getElementsByClassName("edit_bar", "div", gallery_div, function(element) {
		new GalleryManagement.EditBar(element);
	});
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
		if (this.id != GalleryManagement.galleryid) {
			GalleryManagement.startSpinner();
			YAHOO.util.Connect.setForm("gallery_form");
			YAHOO.util.Connect.asyncRequest('POST', '/my/gallery/change_gallery', new ResponseHandler({
				success: function(o) {
					GalleryManagement.images[this.argument].remove();
					GalleryManagement.rebalanceTable();
					GalleryManagement.stopSpinner();
				},
				failure: function(o) {
				},
				argument: picID
			}), "ids=" + picID + "&targetgallery=" + this.id);
		} else {
			YAHOO.util.Dom.setStyle(picID, "visibility", "");
		}
	}
};