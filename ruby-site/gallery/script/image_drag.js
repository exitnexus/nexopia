ImageDrag = function(id, sGroup, config) {
	ImageDrag.superclass.constructor.call(this, id, sGroup, config);
	var el = this.getDragEl();
	YAHOO.util.Dom.setStyle(el, "opacity", 0.67);
	this.setHandleElId("draggable_image["+this.id+"]");
	new YAHOO.widget.Tooltip(document.createElement("div"), {
		context: document.getElementById("draggable_image["+this.id+"]"), 
		text:"Move", 
		showDelay: 1000
	});
	this.goingUp = false;
	this.lastX = 0;
};

YAHOO.extend(ImageDrag, YAHOO.util.DDProxy, {
	startDrag: function(x, y) {
		for (var key in Paginator.paginators) {
			Paginator.paginators[key].showStrikeZones();
		}
		// make the proxy look like the source element
		var dragEl = this.getDragEl();
		var clickEl = this.getEl();
		YAHOO.util.Dom.setStyle(clickEl, "visibility", "hidden");
		this.originalNextSibling = clickEl.nextSibling;
		this.originalParentNode = clickEl.parentNode;
		dragEl.innerHTML = clickEl.innerHTML;
		YAHOO.util.Dom.setStyle(dragEl, "color", YAHOO.util.Dom.getStyle(clickEl, "color"));
		YAHOO.util.Dom.setStyle(dragEl, "backgroundColor", YAHOO.util.Dom.getStyle(clickEl, "backgroundColor"));
		YAHOO.util.Dom.setStyle(dragEl, "border", "");
		GalleryManagement.dragInProgress = true;
	},
	endDrag: function(e) {
		for (var key in Paginator.paginators) {
			Paginator.paginators[key].hideStrikeZones();
		}
		if (this.undoDrag) {
			if (this.originalNextSibling) {
				this.originalParentNode.insertBefore(this.getEl(), this.originalNextSibling);
			} else {
				this.originalParentNode.appendChild(this.getEl());
			}
		}
		if (!this.skipReorder) {
			YAHOO.util.Dom.setStyle(this.id, "visibility", "");
			GalleryManagement.images[this.id].move();
		} else {
			this.skipReorder = false;
		}
		GalleryManagement.dragInProgress = false;
	},
	onDrag: function(e) {
		// Keep track of the direction of the drag for use during onDragOver
		var x = YAHOO.util.Event.getPageX(e);

		if (x < this.lastX) {
			this.goingUp = true;
		} else if (x > this.lastX) {
			this.goingUp = false;
		}

		this.lastX = x;
	},
	onDragDrop: function(e, id) {
		var el = YAHOO.util.Dom.get(id);
		if (id.indexOf("gallery") == 0) {
			this.skipReorder = true;
			GalleryManagement.galleries[GalleryManagement.parseID(id)].takePicture(this.id);
		} else if (el.className == "profile_pic_frame") {
			this.undoDrag = true;
			GalleryManagement.ProfilePicture.registeredElements[el.id].handleDrop(this.id);
		}
		document.getElementById(id).parentNode.parentNode.style.border = "";
	},
	onDragOver: function(e, id) {
		var destEl =YAHOO.util.Dom.get(id);

		// We are only concerned with list items, we ignore the dragover
		// notifications for the list.
		if (destEl.tagName == "LI") {
			var srcEl = this.getEl();
			var p = destEl.parentNode;
			if (this.goingUp) {
				p.insertBefore(srcEl, destEl); // insert above
			} else {
				p.insertBefore(srcEl, destEl.nextSibling); // insert below
			}
		}
	},
	onDragEnter: function(e, id) {
		var el = YAHOO.util.Dom.get(id);
		if (id.indexOf("gallery") == 0) {
			var frame = document.getElementById(id).parentNode.parentNode;
			frame.style.border = "2px solid gray";
		} else if (el.className == "profile_pic_frame"){
			el.style.border = "2px solid gray";
		} else {
			var paginatorEl = YAHOO.util.Dom.getNextSiblingBy(el.parentNode, function(checkEl) { return YAHOO.util.Dom.hasClass(checkEl, "paginator");});
			if (paginatorEl) {
				var paginator = Paginator.paginators[paginatorEl.id];
				if (YAHOO.util.Dom.hasClass(el, "left")) {
					paginator.pageLeft(null, function() {YAHOO.util.DragDropMgr.refreshCache();});
				} else {
					paginator.pageRight(null, function() {YAHOO.util.DragDropMgr.refreshCache();});
				}
			}
		}
	},
	onDragOut: function(e, id) {
		var el = YAHOO.util.Dom.get(id);
		if (id.indexOf("gallery") == 0) {
			document.getElementById(id).parentNode.parentNode.style.border = "";
		} else if (el.className == "profile_pic_frame"){
			el.style.border = "";
		}
	}
});