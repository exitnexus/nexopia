//require gallery_management.js
GalleryManagement.AlbumInfo = {
	summaryElement: null, //.details .summary
	editElement: null, //.details .edit
	spinnerElement: null, //.details .edit .spinner
	edit: function() {
		this.editClone = this.editElement.cloneNode(true);
		this.editElement.style.display = "block";
		this.summaryElement.style.display = "none";
	},
	cancel: function() {
		this.summaryElement.style.display = "block";
		this.editElement.parentNode.replaceChild(this.editClone, this.editElement);
		this.editElement = this.editClone;
		Overlord.summonMinions(this.editElement);
	},
	//finish saving, show spinner, etc...
	save: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		this.spinnerElement.style.display = "inline";
		var form = this.editElement.getElementsByTagName("form")[0];
		var new_title = form.elements[0].value.replace(/^(\s|&nbsp;|&#160;)*|(\s|&nbsp;|&#160;)*$/,"");
		if(new_title == "") {
			alert("Galleries cannot have empty titles.");
			this.spinnerElement.style.display = "none";
		} else {
			YAHOO.util.Connect.setForm(form);
			YAHOO.util.Connect.asyncRequest("POST", Nexopia.areaBaseURI() +"/gallery/update/"+Nexopia.json(this.editElement).id, new ResponseHandler({}), "refresh=album_info");
		}
	}
};

Overlord.assign({
	minion: "album_info:show_edit_album",
	click: GalleryManagement.AlbumInfo.edit,
	scope: GalleryManagement.AlbumInfo
});

Overlord.assign({
	minion: "album_info:cancel_edit",
	click: GalleryManagement.AlbumInfo.cancel,
	scope: GalleryManagement.AlbumInfo
});

Overlord.assign({
	minion: "album_info:edit",
	load: function(element) {
		GalleryManagement.AlbumInfo.editElement = element;
	}
});

Overlord.assign({
	minion: "album_info:spinner",
	load: function(element) {
		GalleryManagement.AlbumInfo.spinnerElement = element;
	}
});

Overlord.assign({
	minion: "album_info:summary",
	load: function(element) {
		GalleryManagement.AlbumInfo.summaryElement = element;
	}
});

Overlord.assign({
	minion: "album_info:save",
	click: GalleryManagement.AlbumInfo.save,
	scope: GalleryManagement.AlbumInfo
});

Overlord.assign({
	minion: "album_info:description_edit",
	load: function(element) {
		if (element.innerHTML == "") {
			element.innerHTML = "Enter description here...";
		}
	},
	click: function(event, element) {
		if (element.innerHTML == "Enter description here...") {
			element.innerHTML = "";
		}
	}
});

Overlord.assign({
	minion: "album_info:description",
	load: function(element) {
		new Truncator(element);
	}
});
