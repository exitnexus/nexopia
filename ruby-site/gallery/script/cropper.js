Cropper = {
	init: function() {
		var images = YAHOO.util.Dom.getElementsByClassName("cropper");
		for (var i=0; i<images.length;i++) {
			var cropperLink = YAHOO.util.Dom.getNextSibling(images[i]);
			cropperLink.cropper = new YAHOO.widget.ImageCropper(images[i], {xyratio:1,minW:200});
		}
		var cropperLinks = YAHOO.util.Dom.getElementsByClassName("cropper_link");
		for (var i=0; i<cropperLinks.length; i++) {
			var cropperLink = cropperLinks[i];
			YAHOO.util.Event.on(cropperLinks[i], 'click', function(event) {
				YAHOO.util.Event.preventDefault(event);
				var region = cropperLink.cropper.getCropRegion();
				var display = "";
				for (key in region) {
					display += (key+":"+region[key]+"\n");
				}
				alert(display);
			});
		}
	}
};

GlobalRegistry.register_handler("cropper", Cropper.init, Cropper, true);