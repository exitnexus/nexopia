HelpPanel = function(element) {
	this.element = element;
	this.content = this.element.getAttribute('content');
	this.title = this.element.getAttribute('title');
	YAHOO.util.Event.on(this.element, 'click', this.open, this, true);
};

HelpPanel.prototype = {
	element: null, //The element the help panel is attached to, normally a [?] link
	overlay: null, //The YUI Panel widget
	content: "", //The text of the help panel
	title: "Information",
	open: function(event) {
		YAHOO.util.Event.preventDefault(event);
		var xy = YAHOO.util.Event.getXY(event);
		xy[0] = xy[0]-200;
		this.overlay = new YAHOO.widget.Overlay("help_panel", {
			fixedcenter: false,
			visible: true,
			xy: xy
		});
		this.overlay.setBody("<a id='help_panel_close' href='#close'><img src='"+Site.staticFilesURL+"/panels/images/close_help_panel.gif'/></a><h1>"+this.title+"</h1><div>" + this.content + "</div>");
		this.overlay.render(document.body);
		YAHOO.util.Dom.setStyle('help_panel', 'zIndex', 1000);
		YAHOO.util.Event.on('help_panel_close', 'click', this.close, this, true);
	},
	close: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		this.overlay.destroy();
	}
};

Overlord.assign({
	minion: "help",
	load: function(element) {
		new HelpPanel(element);
	}
});