/*
	Note: This borrows heavily from the YUI drag and drop list element example. It was modified to work with
	DIV elements instead and to set some of the default behavior that we want for the profile blocks.
*/

var Dom = YAHOO.util.Dom;
var Event = YAHOO.util.Event;
var DDM = YAHOO.util.DragDropMgr;

if(YAHOO.profile == undefined){
	YAHOO.namespace ("profile");
}

//DDM.stopPropagation = true;

YAHOO.profile.DraggableBlock = function(id, handleID, noDrag, profileDisplayBlock, sGroup, config) {
    YAHOO.profile.DraggableBlock.superclass.constructor.call(this, id, sGroup, config);
	
	this.profileDisplayBlock = profileDisplayBlock;
	
	// Make the DraggableBlock functionality accessible via the element
	var el = Dom.get(id);
	//el.draggableBlock = this;
	
	this.dragListeners = new Array();

	if (handleID != null) 
	{
		this.setHandleElId(handleID);
		Dom.setStyle(handleID, "cursor", "move");
	
		if (noDrag == null) { noDrag = false; } 

		if (!noDrag)
		{
			// Build overlay to show an indication that the object in question can be dragged and dropped.
			var overlay = new YAHOO.widget.Overlay("overlay", { height: 22, width: 22, visible: false });
	
			overlay.setBody("<div style='background-color: white; border-width: 1px; border-style: solid; width: 20px; height: 20px; overflow: hidden'><img src='" +
				Site.staticFilesURL + 
				"/profile/images/icon_movable.gif' width='20px' height='20px'/></div>");
	
			overlay.render(document.body);
	
			function overlayOn(e, obj)
			{
				obj.cfg.setProperty("context", [id, "tl", "tl"]);
				obj.cfg.setProperty("visible", true);
			}
	
			function overlayOff(e, obj)
			{
				obj.cfg.setProperty("visible", false);
			}
	
			YAHOO.util.Event.addListener(handleID, "mouseover", overlayOn, overlay);
			YAHOO.util.Event.addListener(handleID, "mouseout", overlayOff, overlay);
		}
	}
	else
	{
		Dom.setStyle(id, "cursor", "move");
	}
	
    this.logger = this.logger || YAHOO;
    var el = this.getDragEl();

    this.goingUp = false;
    this.lastY = 0;
	
	// Set up an overlay to use to indicate valid drop spots for a profile block that is
	// being dragged.
	this.dropSpotBorder = new YAHOO.widget.Overlay("drop_spot_border", { visible: false });
	this.dropSpotBorder.render(document.body);
	
	Dom.setStyle("drop_spot_border", "border-style", "dotted");
	Dom.setStyle("drop_spot_border", "border-width", "2px");
};


YAHOO.extend(YAHOO.profile.DraggableBlock, YAHOO.util.DDProxy, {

	startDrag: function(x, y) 
	{
		// make the proxy look like the source element
		var dragEl = this.getDragEl();
		var clickEl = this.getEl();

		Dom.setStyle(clickEl, "visibility", "hidden");

		dragEl.innerHTML = clickEl.innerHTML;
		dragEl.className = clickEl.className;
		clickEl.parentNode.appendChild(dragEl);

		Dom.addClass(dragEl, "drag_block");
				
		this.fireDragListeners(clickEl);
		
		YAHOO.profile.DraggableBlockMgr.resizeColumnBottomMarkers(dragEl.offsetHeight);
	},


    endDrag: function(e)
	{
		var srcEl = this.getEl();
		var proxy = this.getDragEl();

		// Show the proxy element and animate it to the src element's location
		Dom.setStyle(proxy, "visibility", "");
		var a = new YAHOO.util.Motion( 
			proxy, 
			{ 
				points: 
				{ 
					to: Dom.getXY(srcEl)
				}
			},
			0.2, 
			YAHOO.util.Easing.easeOut 
		);
		
		var proxyid = proxy.id;
		var thisid = this.id;

		// Hide the proxy and show the source element when finished with the animation
		a.onComplete.subscribe(
			function() 
			{
				Dom.setStyle(proxyid, "visibility", "hidden");
				Dom.setStyle(thisid, "visibility", "");
			});
		a.animate();

		var glassDiv = Dom.getElementsByClassName("glass_div", "div", srcEl)[0];
		if (glassDiv)
		{
			Dom.setStyle(glassDiv, "width", srcEl.offsetWidth + "px");
			Dom.setStyle(glassDiv, "height", srcEl.offsetHeight + "px");
		}

		var glassIframe = Dom.getElementsByClassName("glass_iframe", "iframe", srcEl)[0];
		if (glassIframe)
		{
			Dom.setStyle(glassIframe, "width", srcEl.offsetWidth + "px");
			Dom.setStyle(glassIframe, "height", srcEl.offsetHeight + "px");
		}

		// At the end of the drag, we no longer need a visual cue as to where the
		// block can be dropped, so we hide the drop spot border.
		this.dropSpotBorder.cfg.setProperty("visible", false);
		
		this.fireDragListeners(this.getEl());
		this.saveDrop(srcEl);
		YAHOO.profile.DraggableBlockMgr.resizeColumnBottomMarkers();	
	},


    onDrag: function(e) 
	{
		// Keep track of the direction of the drag for use during onDragOver
		var y = Event.getPageY(e);

		if (y < this.lastY)
		{
			this.goingUp = true;
		}
		else if (y > this.lastY)
		{
			this.goingUp = false;
		}

		this.lastY = y;

		// Size the overlay for the drop spot to be the width and height of the invisible
		// source block (for which, we're dragging the proxy). The overlay will follow the
		// source element so that a dotted border will be shown in each valid drop spot.
		var srcEl = this.getEl();
		this.dropSpotBorder.cfg.setProperty("width", (srcEl.offsetWidth - 4) + "px");
		this.dropSpotBorder.cfg.setProperty("height", (srcEl.offsetHeight - 4) + "px");
		this.dropSpotBorder.cfg.setProperty("context", [srcEl.id,"tl","tl"]);
		this.dropSpotBorder.cfg.setProperty("visible", true);
		
		this.fireDragListeners(this.getDragEl());
	},


	onDragOver: function(e, id) 
	{
		var srcEl = this.getEl();
		var destEl = Dom.get(id);

		// We are only concerned with div items
		if (destEl.nodeName.toLowerCase() == "div")
		{
			var orig_p = srcEl.parentNode;
			var p = destEl.parentNode;

			// Particularly, we are only concerned with "block_container" items
			// Note: This will need to be changed to whatever the actual class
			// of the div blocks is.
			if (YAHOO.util.Dom.hasClass(destEl, "block_container"))
			{
				if (this.profileDisplayBlock.form_factor() == "both" ||
					this.profileDisplayBlock.form_factor() == this.columnFormFactor(destEl))
				{
					if (ProfileDisplayBlock.getBlockById(id).moveable())
					{
						if (this.goingUp)
						{
							p.insertBefore(srcEl, destEl); // insert above
						} 
						else 
						{
							p.insertBefore(srcEl, destEl.nextSibling); // insert below
						}
			
						this.fireDragListeners(destEl);
						this.fireDragListeners(this.getDragEl());
					}
					else if (YAHOO.util.Dom.hasClass(destEl.nextSibling, "column_bottom_marker"))
					{
						p.insertBefore(srcEl, destEl.nextSibling); // insert below
					}
				}
			}
			// Handle the case where there are no draggable blocks in a column, but we want
			// to drag a block from another column into it. For this, we're putting invisible
			// placeholder marker divs at the bottoms of the columns.
			else if (YAHOO.util.Dom.hasClass(destEl, "column_bottom_marker"))
			{
				if (this.profileDisplayBlock.form_factor() == "both" ||
					this.profileDisplayBlock.form_factor() == this.columnFormFactor(destEl))
				{
					p.insertBefore(srcEl, destEl);					
				}
			}

			DDM.refreshCache();
		}
	},
	
	
	saveDrop: function(srcEl)
	{	
		var srcBlockID = this.profileDisplayBlock.blockid;
		var destColumn = this.getColumn(srcEl);
		var destPosition = this.getPosition(srcEl);

		//var formKey1 = document.getElementById("profile_block_form_key").value;
		var formKey2 = document.getElementById("profile_form_key").value;
		
		YAHOO.util.Connect.asyncRequest('POST', "/my/profile/edit/" + srcBlockID + "/position", {
			success: function(o) {
			},
			failure: function(o) {
			},
			scope: this
		}, "ajax=true&column=" + destColumn + "&position=" + destPosition + /*"&form_key[]=" + formKey1 +*/ "&form_key[]=" + formKey2);
	},
	
	
	columnFormFactor: function(blockContainer)
	{
		if (Dom.getAncestorByClassName(blockContainer, "profile_left_column") != null)
		{
			return "narrow";
		}
		else
		{
			return "wide";
		}
	},
	
	
	getColumn: function(blockContainer)
	{
		if (Dom.getAncestorByClassName(blockContainer, "profile_left_column") != null)
		{
			return 0;
		}
		else
		{
			return 1;
		}
	},
	
	
	getPosition: function(blockContainer)
	{
		var position = 0;
		var currentEl = blockContainer.previousSibling;
		
		while(currentEl != null)
		{
			position = position + 1;

			if (YAHOO.util.Dom.hasClass(currentEl, "block_container"))
			{
				currentEl = currentEl.previousSibling;
			}
			else
			{
				currentEl = null;
			}
		}
		
		return position;
	},
	
	
	fireDragListeners: function(ref)
	{
		for (var i = 0; i < this.dragListeners.length; i++)
		{
			this.dragListeners[i][0](ref, this.dragListeners[i][1]);
		}
	},
	
	
	registerDragListener: function(listener, object)
	{
		this.dragListeners.push([listener, object]);
	}
});


YAHOO.profile.DraggableBlockMgr = 
{
	resizeColumnBottomMarkers: function(offset)
	{
		if (!offset) { offset = 10; }
	
		var leftBottomMarker = document.getElementById("column_bottom_marker_0");
		var rightBottomMarker = document.getElementById("column_bottom_marker_1");
	
		var leftRegion = YAHOO.util.Region.getRegion(leftBottomMarker);
		var rightRegion = YAHOO.util.Region.getRegion(rightBottomMarker);
	
		if (leftRegion.top > rightRegion.top)
		{
			YAHOO.util.Dom.setStyle(leftBottomMarker, "height", offset + "px");
			var newHeight = leftRegion.top - rightRegion.top + offset;
			if (newHeight < offset) { newHeight = offset };
			YAHOO.util.Dom.setStyle(rightBottomMarker, "height", newHeight + "px");
		
		}
		else if (rightRegion.top > leftRegion.top)
		{
			YAHOO.util.Dom.setStyle(rightBottomMarker, "height", offset + "px");
			var newHeight = rightRegion.top - leftRegion.top + offset;
			if (newHeight < 10) { newHeight = 10 };
			YAHOO.util.Dom.setStyle(leftBottomMarker, "height", newHeight + "px");
		
		}
	}
};