//require edit_profile_pictures.js

//////////////////////////////////////////////////////////////////////////////
// This is based largely on the drag and drop list reordering example from the
// yui docs.
//////////////////////////////////////////////////////////////////////////////

EditProfilePictures.ProfilePicture = function(id, sGroup, config) {

    EditProfilePictures.ProfilePicture.superclass.constructor.call(this, id, sGroup, config);

    this.logger = this.logger || YAHOO;
    var el = this.getDragEl();
    YAHOO.util.Dom.setStyle(el, "opacity", 0.67); // The proxy is slightly transparent

    this.goingUp = false;
    this.lastY = 0;
};

YAHOO.extend(EditProfilePictures.ProfilePicture, YAHOO.util.DDProxy, {

    startDrag: function(x, y) {
        this.logger.log(this.id + " startDrag");

        // make the proxy look like the source element
        var dragEl = this.getDragEl();
        var clickEl = this.getEl();
        YAHOO.util.Dom.setStyle(clickEl, "visibility", "hidden");

        dragEl.innerHTML = clickEl.innerHTML;

        YAHOO.util.Dom.setStyle(dragEl, "color", YAHOO.util.Dom.getStyle(clickEl, "color"));
        YAHOO.util.Dom.setStyle(dragEl, "backgroundColor", YAHOO.util.Dom.getStyle(clickEl, "backgroundColor"));
        YAHOO.util.Dom.setStyle(dragEl, "border", "2px solid gray");
    },

    endDrag: function(e) {

        var srcEl = this.getEl();
        var proxy = this.getDragEl();

        // Show the proxy element and animate it to the src element's location
        YAHOO.util.Dom.setStyle(proxy, "visibility", "");
        var a = new YAHOO.util.Motion(proxy, { 
                points: { 
                    to: YAHOO.util.Dom.getXY(srcEl)
                }
            }, 
            0.2, 
            YAHOO.util.Easing.easeOut 
        );
        var proxyid = proxy.id;
        var thisid = this.id;

		var position = 1;
		for (var i=0; i<srcEl.parentNode.childNodes.length; i++) {
			var node = srcEl.parentNode.childNodes[i];
			if (node.nodeName.toLowerCase() != "li") {
				continue;
			}
			if (srcEl == node) {
				break;
			}
			position++;
		}
		
		var form_key = YAHOO.util.Dom.get('form_key').value;
		
		YAHOO.util.Connect.asyncRequest('POST', Nexopia.areaBaseURI() + '/pictures/move/'+Nexopia.json(srcEl).gallerypicid+"/to/"+position, 
			new ResponseHandler({}), "refresh=edit_profile_pictures&form_key[]=" + form_key);

        // Hide the proxy and show the source element when finished with the animation
        a.onComplete.subscribe(function() {
                YAHOO.util.Dom.setStyle(proxyid, "visibility", "hidden");
                YAHOO.util.Dom.setStyle(thisid, "visibility", "");
            });
        a.animate();
    },

    onDrag: function(e) {

        // Keep track of the direction of the drag for use during onDragOver
        var y = YAHOO.util.Event.getPageY(e);

        if (y < this.lastY) {
            this.goingUp = true;
        } else if (y > this.lastY) {
            this.goingUp = false;
        }

        this.lastY = y;
    },

    onDragOver: function(e, id) {
    
        var srcEl = this.getEl();
        var destEl = YAHOO.util.Dom.get(id);

        // We are only concerned with list items, we ignore the dragover
        // notifications for the list.
        if (destEl.nodeName.toLowerCase() == "li") {
            var orig_p = srcEl.parentNode;
            var p = destEl.parentNode;

            if (this.goingUp) {
                p.insertBefore(srcEl, destEl); // insert above
            } else {
                p.insertBefore(srcEl, destEl.nextSibling); // insert below
            }
            YAHOO.util.DragDropMgr.refreshCache();
        }
    }
});

Overlord.assign({
	minion: "epp:profile_picture",
	load: function(el) {
		new EditProfilePictures.ProfilePicture(el);
	}
});

Overlord.assign({
	minion: "epp:remove_link",
	click: function(event, element) {
		YAHOO.util.Event.preventDefault(event);
		YAHOO.util.Connect.asyncRequest('POST', element.href, new ResponseHandler({}), "refresh=edit_profile_pictures");
	}
});