// require setup_skin_edit.js

SmoothScroll = function(el, attributes, duration,  method) {
	if (el) { // dont break existing subclasses not using YAHOO.extend
		SmoothScroll.superclass.constructor.call(this, el, attributes, duration, method);
	}
};

YAHOO.extend(SmoothScroll, YAHOO.util.Scroll, {
	getAttribute: function(attr) {
		var val = null;
		
		if (attr == 'scroll')
		{
			val = previewScrollPosition();
		}
		else
		{
			val = superclass.getAttribute.call(this, attr);
		}
		
		return val;
	},
	
	setAttribute: function(attr, val, unit) {
		if (attr == 'scroll')
		{
			jumpPreviewTo(val);
		}
		else
		{
			superclass.setAttribute.call(this, attr, val, unit);
		}
	}
});

/* ========== */

DDScroll = function(panelElId, handleElId, sGroup, config) {
	DDScroll.superclass.constructor.call(this, panelElId, sGroup, config);
	if (handleElId) {
		this.setHandleElId(handleElId);
	}
};

YAHOO.extend(DDScroll, YAHOO.util.DragDrop, {
	onMouseDown: function(e)
	{
		this.startPos = previewScrollPosition();
		this.mouseStart = YAHOO.util.Event.getXY(e);
	},
	
	onDrag: function(e) {
		var newPos = YAHOO.util.Event.getXY(e);
		
		var offsetX = this.mouseStart[0] - newPos[0];
		var offsetY = this.mouseStart[1] - newPos[1];
		
		jumpPreviewTo([this.startPos[0] + offsetX, this.startPos[1] + offsetY]);
	}
});

YAHOO.profile.UserSkin.init_fancy_scroll = function()
{
    var dd, dd2, dd3;
    dd = new DDScroll("preview_handle", "preview_handle");
};
