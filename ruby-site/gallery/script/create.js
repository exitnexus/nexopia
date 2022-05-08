CreateAlbum = {
	
	validateGalleryName: function() {
		valid = false;
		var gallery_name = YAHOO.util.Dom.get("gallery_name");
		if (gallery_name.value == "")
		{
			alert("Please enter a gallery name.");
		} else {
			valid = true;
		}
		return valid;
	}
	
};

Overlord.assign({
	minion: "album_create:continue",
	click: function(event, element) {
		YAHOO.util.Event.preventDefault(event);

		if ( CreateAlbum.validateGalleryName() ) {
		
			// Set a spinner while we're creating the gallery
			var spinner = YAHOO.util.Dom.get("spinner");
			YAHOO.util.Dom.setStyle(element, "display", "none");
			YAHOO.util.Dom.setStyle(spinner, "display", "inline");
			
			// Ajax submit the form
			var create_gallery_form = YAHOO.util.Dom.get("create_album_form");
			create_gallery_form.submit();
		}
		
	}
	
});