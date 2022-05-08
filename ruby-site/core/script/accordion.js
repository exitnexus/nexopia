YAHOO.util.Event.on(window, 'load', init_accordions);

function init_accordions()
{
	var speed = 0.15;

	var accordions = YAHOO.util.Dom.getElementsByClassName("accordion_view");
	for(j = 0; j < accordions.length; j++)
	{
		accordions[j].meta = {};
		
		var handles = YAHOO.util.Dom.getChildrenBy(accordions[j], function(el){return YAHOO.util.Dom.hasClass(el, 'accordion_handle')});
		var bodies = YAHOO.util.Dom.getChildrenBy(accordions[j], function(el){return YAHOO.util.Dom.hasClass(el, 'accordion_body')});
		if(handles.length != bodies.length) return;
		
		for(i = 0; i < handles.length; i++)
		{
			bodies[i].meta = {};    handles[i].meta = {};
			
			bodies[i].meta.originalHeight = YAHOO.util.Dom.getRegion(bodies[i]).bottom - YAHOO.util.Dom.getRegion(bodies[i]).top;
			bodies[i].meta.originalPaddingTop = parseInt(YAHOO.util.Dom.getStyle(bodies[i], "padding-top"));
			
			// TODO: Fix this IE bug which only seems to come up in our framework
			if(YAHOO.env.ua.ie)
				collapsedHeight = 0;
			else
				collapsedHeight = 0;
			
			YAHOO.util.Dom.setStyle(bodies[i], "height", collapsedHeight);
			YAHOO.util.Dom.setStyle(bodies[i], "padding-top", 0);
			YAHOO.util.Dom.setStyle(bodies[i], "padding-bottom", 0);
			
			YAHOO.util.Event.on(handles[i], 'click', function(e, source)
			{
				handle = source[0];
				body = source[1];
				accordion = source[2];
				
				if(handle != accordion.meta.active[0])
				{
					var oldAttributes = {
						height: { to: collapsedHeight },
						paddingTop: { to: 0 }
					};
					var oldAnimation = new YAHOO.util.Motion(accordion.meta.active[1], oldAttributes, speed);
					var newAttributes = {
						height: { to: body.meta.originalHeight - body.meta.originalPaddingTop },
						paddingTop: { to: body.meta.originalPaddingTop }
					};
					var newAnimation = new YAHOO.util.Motion(body, newAttributes, speed);
					
					oldAnimation.animate();
					newAnimation.animate();

					YAHOO.util.Dom.removeClass(accordion.meta.active[0], "accordion_handle_active");
					YAHOO.util.Dom.addClass(handle, "accordion_handle_active");
					YAHOO.util.Dom.removeClass(accordion.meta.active[1], "accordion_body_active");
					YAHOO.util.Dom.addClass(body, "accordion_body_active");
					
					accordion.meta.active = [handle, body];
				}
				else
				{
/*
					var oldAttributes = {
						height: { to: collapsedHeight },
						paddingTop: { to: 0 }
					};
					var oldAnimation = new YAHOO.util.Motion(accordion.meta.active[1], oldAttributes, speed);
					
					oldAnimation.animate();
					
					YAHOO.util.Dom.removeClass(accordion.meta.active[0], "accordion_handle_active");
					YAHOO.util.Dom.removeClass(accordion.meta.active[1], "accordion_body_active");
					
					accordion.meta.active = ["null", "null"];
*/
				}
				
			}, [handles[i], bodies[i], accordions[j]]);
		}
		var firstAttributes = {
			height: { to: bodies[0].meta.originalHeight - bodies[0].meta.originalPaddingTop },
			paddingTop: { to: bodies[0].meta.originalPaddingTop }
		};
		var firstAnimation = new YAHOO.util.Motion(bodies[0], firstAttributes, speed);
		firstAnimation.animate();
		YAHOO.util.Dom.addClass(handles[0], "accordion_handle_active");
		YAHOO.util.Dom.addClass(bodies[0], "accordion_body_active");
		accordions[j].meta.active = [handles[0], bodies[0]];
		
/* 		accordions[j].meta.active = ["null", "null"]; */
	}
}