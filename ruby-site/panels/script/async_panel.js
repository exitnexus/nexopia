//require nexopia_panel.js
/* 
	This will automatically load a modal overlay panel when an element  with minion_name="async_panel"
   	is clicked.  The panel is loaded off of the path attribute on the element.  If the loading link has
	an id on it a reference to the panel can be retrieved with AsyncPanel.get(id).  The currently open
	panel will be available in NexopiaPanel.current.
	
	Panels can also be created purely in javascript using new AsyncPanel(path) and then calling open()
	on the resulting object.
	
	See nexopia_panel.js for more information.
*/
AsyncPanel = function(cfg) {
	this.constructor.superclass.constructor.call(this, cfg);
};

YAHOO.extend(AsyncPanel, NexopiaPanel, {
	drawPanel: function()
	{
		this.showSpinner();
		if (this.cfg.load_form) {
			YAHOO.util.Connect.setForm(this.cfg.load_form);
		}
		YAHOO.util.Connect.asyncRequest('POST', this.cfg.path, new ResponseHandler({
			success: function(o) {
				this.overlay.setBody(o.responseText);
				this.render();
			},
			failure: function(o) {
				this.close();
			},
			scope: this,
			cache: false
		}));		
	}
});

AsyncPanel.createConfig = function(element)
{
	return YAHOO.lang.merge(NexopiaPanel.createConfig(element), { 
		path: element.getAttribute('path'),
		//load_form can be specified to pass parameters through in the request to load the panel
		//the most obvious example of where we want to do this is an async panel for preview functionality
		//where we need to pass some user input through to be displayed as a preview.
		load_form: YAHOO.util.Dom.get(element.getAttribute('load_form'))
	});
};

Overlord.assign({
	minion: "async_panel",
	load: function(element) {
		var panel = new AsyncPanel(AsyncPanel.createConfig(element));
		NexopiaPanel.setup(element, panel);
	}
});