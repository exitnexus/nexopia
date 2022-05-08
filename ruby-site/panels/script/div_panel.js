//require nexopia_panel.js
// TODO: This panel will load from a hidden Div on the page
DivPanel = function(cfg) {
	this.constructor.superclass.constructor.call(this, cfg);
};

YAHOO.extend(DivPanel, NexopiaPanel, {
	drawPanel: function()
	{
		this.initOverlay();
		if (!this.div) { this.div = document.getElementById(this.cfg.div_id).cloneNode(true); }
		this.overlay.setBody(this.div);
		
		YAHOO.util.Dom.setStyle(this.div, "display", "block");
		
		this.render();
		this.overlay.center();
	}
});

DivPanel.createConfig = function(element)
{
	return YAHOO.lang.merge(NexopiaPanel.createConfig(element), { 
		div_id: element.getAttribute('div_id')
	});
}

Overlord.assign({
	minion: "div_panel",
	load: function(element) {
		var panel = new DivPanel(DivPanel.createConfig(element));
		NexopiaPanel.setup(element, panel);
	}
});