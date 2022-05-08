GalleryProfileBlockManager = {
	init: function() {
	}
};

Overlord.assign({
	minion: "profile",
	load: function(element) {
		GalleryProfileBlockManager.init();
	},
	order: -1	
});

Overlord.assign({
	minion: "gallery_pics_frame",
	load: function(element) {
		new Paginator(element, {height:105, width: 492});
	},
	order: -1	
});

Overlord.assign({
	minion: "gallery_pics_frame",
	mouseover: function(event, element) {
		Nexopia.DelayedImage.loadImages(element);
	},
	order: 1	
});
