YAHOO.util.Event.on(window, 'load', init_tabs());

function init_tabs()
{
	var tab_views = YAHOO.util.Dom.getElementsByClassName("tab_view");
	for(j = 0; j < tab_views.length; j++)
	{
		tab_views[j].meta = {};
		
		var tabs = YAHOO.util.Dom.getChildrenBy(tab_views[j], function(el){return YAHOO.util.Dom.hasClass(el, 'tab_handle')});
		var bodies = YAHOO.util.Dom.getChildrenBy(tab_views[j], function(el){return YAHOO.util.Dom.hasClass(el, 'tab_body')});
		if(tabs.length != bodies.length) return;
		
		for(i = 0; i < tabs.length; i++)
		{
			bodies[i].initialDisplay = YAHOO.util.Dom.getStyle(bodies[i], "display");
		
			YAHOO.util.Dom.setStyle(bodies[i], "display", "none");
			
			YAHOO.util.Event.on(tabs[i], 'click', function(e, source)
			{
				tab = source[0];
				body = source[1];
				tab_view = source[2];
				
				if(tab != tab_view.meta.active[0])
				{
					YAHOO.util.Dom.setStyle(tab_view.meta.active[1], "display", "none");
					YAHOO.util.Dom.setStyle(body, "display", body.initialDisplay);

					YAHOO.util.Dom.removeClass(tab_view.meta.active[0], "tab_handle_active");
					YAHOO.util.Dom.addClass(tab, "tab_handle_active");
					YAHOO.util.Dom.removeClass(tab_view.meta.active[1], "tab_body_active");
					YAHOO.util.Dom.addClass(body, "tab_body_active");
					
					tab_view.meta.active = [tab, body];
				}
				
			}, [tabs[i], bodies[i], tab_views[j]]);
		}
		
		YAHOO.util.Dom.setStyle(bodies[0], "display", bodies[0].initialDisplay);
		
		YAHOO.util.Dom.addClass(tabs[0], "tab_handle_active");
		YAHOO.util.Dom.addClass(bodies[0], "tab_body_active");
		tab_views[j].meta.active = [tabs[0], bodies[0]];
	}
}