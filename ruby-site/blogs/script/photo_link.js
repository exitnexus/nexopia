Overlord.assign({
	minion: "blogs:photo_link",
	change: function(event) {
		var value = this.value;
		this.value = value.replace(/http:\/\/(.*):\/\//, '$1:\/\/');
		
		// Validate by loading the image
		document.getElementById(this.id + '_valid').value = '-1';
		var objImage = new Image();
		objImage.src = this.value;
		objImage.onLoad = photo_link_obj_load(objImage, this.id);
	}
});

function photo_link_obj_load(objImage, id) {
	if ((objImage.width > 0) && (objImage.height > 0)) {
		document.getElementById(id + '_valid').value = '1';
	} else {
		document.getElementById(id + '_valid').value = '0';
	}
}

