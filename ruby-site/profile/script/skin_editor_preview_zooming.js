YAHOO.util.Event.on(window, 'load', function()
{
	YAHOO.util.Dom.setStyle("preview_handle", "opacity", "0");
	drawPreviewZoom();
});

YAHOO.util.Event.on(['preview_handle', 'preview_zoom'], 'mouseover', function()
{
	showZoomIcon();
});
YAHOO.util.Event.on(['preview_handle', 'preview_zoom'], 'mouseout', function()
{
	hideZoomIcon();
});

var previewIsZoomed = false;
var previewOriginalRegion;
var previewOriginalScoll;
YAHOO.util.Event.on('preview_zoom', 'click', function()
{
	if(previewIsZoomed)
	{
		var rightSideRegion = YAHOO.util.Dom.getRegion("right");
		
		var attributes = {
			points: { to: [rightSideRegion.left + 6, previewOriginalRegion.top] },
			height: { to: previewOriginalRegion.bottom - previewOriginalRegion.top - 4 },
			width: { to: previewOriginalRegion.right - previewOriginalRegion.left - 4 }
		};
		var anim = new YAHOO.util.Motion('preview_wrapper', attributes, 0.25);
		anim.animate();
		
		var shadowAttributes = { opacity: { to: 0 } };
		var shadowAnimation = new YAHOO.util.Motion('preview_shadow', shadowAttributes, 0.25);
		shadowAnimation.animate();
		shadowAnimation.onComplete.subscribe(function() { YAHOO.util.Dom.setStyle("preview_shadow", "display", "none"); } );
		
		previewIsZoomed = false;	
	}
	else
	{
		previewOriginalRegion = YAHOO.util.Dom.getRegion("preview_wrapper");
		jumpPreviewTo([0,0]);
		
		var viewHeight = YAHOO.util.Dom.getViewportHeight();
		var viewWidth = YAHOO.util.Dom.getViewportWidth();
		
		var goalWidth = Math.min(780, viewWidth - 50);
		var goalHeight = Math.min(600, viewHeight - 50);
		var goalLeft = Math.round((viewWidth - goalWidth) / 2) + YAHOO.util.Dom.getDocumentScrollLeft();
		var goalTop = Math.round((viewHeight - goalHeight) / 2) + YAHOO.util.Dom.getDocumentScrollTop();
		
		var attributes = {
			points: { to: [goalLeft, goalTop] },
			height: { to: goalHeight },
			width: { to: goalWidth }
		};
		var anim = new YAHOO.util.Motion('preview_wrapper', attributes, 0.25);
		anim.animate();
		
		YAHOO.util.Dom.setStyle("preview_shadow", "display", "block");
		var shadowFixAttributes = {
			points: { to: [0, 0] },
			height: { to: YAHOO.util.Dom.getDocumentHeight() },
			width: { to: YAHOO.util.Dom.getDocumentWidth() }
		};
		var shadowFixAnimation = new YAHOO.util.Motion('preview_shadow', shadowFixAttributes, 0);
		shadowFixAnimation.animate();
		
		var shadowAttributes = {
			opacity: { to: 0.7 }
		};
		var shadowAnimation = new YAHOO.util.Motion('preview_shadow', shadowAttributes, 0.25);
		shadowAnimation.animate();
		
		previewIsZoomed = true;
	}
});
