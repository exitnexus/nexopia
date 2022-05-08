//require nexopia.js
//require script_manager.js
Nexopia.DelayedImage = {
	//load the images for any img tags that have their src's on url attributes and are contained by element
	loadImages: function(element) {
		YAHOO.util.Dom.getElementsBy(function(img) {
			return img.attributes.url; //match any img tag inside of element that has a url attribute
		}, "img", element, this.loadImage);
	},
	loadImage: function(img) {
		if (img.attributes.url) {
			img.src = img.attributes.url.value; //set the src attribute to be the value of the url attribute
			img.removeAttribute('url');
		}
	},
	setDelayedSrc: function(element, src) {
		var attr = document.createAttribute('url');
		attr.value = src;
		element.setAttributeNode(attr);
	}
};

Overlord.assign({
	minion: "user_content_image",
	load: Nexopia.DelayedImage.loadImage
});
