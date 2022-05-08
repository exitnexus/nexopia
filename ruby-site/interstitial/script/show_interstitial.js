/*
	The fun with the interstitial_head is for the head frame when skin_frames is being used.
	It communicates across the frame to blank out the header.
*/
if(YAHOO.interstitial == undefined){
	YAHOO.namespace ("interstitial");
}

YAHOO.interstitial.Interstitial = {
	init: function()
	{
		if (!this.interstitial_display) 
		{
			var width = YAHOO.util.Dom.getViewportWidth();
			var padding = width - 700;
			var offset = padding/2;
		
			if(offset <= 0)
			{
				offset = 10;
			}
		
			this.interstitial_display = new YAHOO.widget.Panel("interstitial_display",  
				{ xy: [offset, 40],
					width: "700px", 
					fixedcenter: false, 
					close: false, 
					draggable: false, 
					zindex:300,
					modal: true,
					visible: false
				} );
			var el = document.getElementById("interstitial");
			el.parentNode.removeChild(el);
			
			var top_button_bar = document.createElement("div");
			top_button_bar.className = "button_bar";
			top_button_bar.id = "button_bar_top";
			YAHOO.util.Dom.setStyle(top_button_bar, "position", "absolute");
			YAHOO.util.Dom.setStyle(top_button_bar, "top", "6px");
			YAHOO.util.Dom.setStyle(top_button_bar, "right", "6px");
			el.appendChild(top_button_bar);
			
			var bottom_button_bar = document.createElement("div");
			bottom_button_bar.className = "button_bar";
			bottom_button_bar.id = "button_bar_bottom";
			YAHOO.util.Dom.setStyle(bottom_button_bar, "position", "absolute");
			YAHOO.util.Dom.setStyle(bottom_button_bar, "bottom", "6px");
			YAHOO.util.Dom.setStyle(bottom_button_bar, "right", "6px");
			el.appendChild(bottom_button_bar);
			
			// Done Buttons
			this.done_button_top = new YAHOO.widget.Button({
				id: "done_buton_top",
				type: "button",
				label: "Close",
				container: "button_bar_top"
			});
			
			this.done_button_top.on("click", this.hide_interstitial);
			
			YAHOO.util.Dom.setStyle(el, "display", "block");
			this.interstitial_display.setBody(el.innerHTML);
			var links = el.getElementsByTagName('a');
			for (var i=0;i<links.length;i++) {
				YAHOO.util.Event.on(links[i], 'click', this.hide_interstitial, this, true);
			}
			
			this.interstitial_display.render(document.body);
			
			YAHOO.util.Dom.setStyle("interstitial_display", "border", "none");
		}
		// Show the Panel
		this.interstitial_display.show();
		if(parent && parent.head && parent.head.YAHOO && parent.head.YAHOO.interstitial && parent.head.YAHOO.interstitial.InterstitialHead.interstitial_head_display)
		{
			parent.head.YAHOO.interstitial.InterstitialHead.interstitial_head_display.show();
		}
  },

	hide_interstitial: function()
	{
		if(YAHOO.interstitial.Interstitial.interstitial_display)
		{
			YAHOO.interstitial.Interstitial.interstitial_display.hide();
			if(parent && parent.head && parent.head.YAHOO && parent.head.YAHOO.interstitial)
			{
				parent.head.YAHOO.interstitial.InterstitialHead.interstitial_head_display.hide();
			}
		}
	}
};

YAHOO.interstitial.InterstitialHead = {
	init: function(el)
	{
		var width = YAHOO.util.Dom.getViewportWidth();
		var padding = width - 700;
		var offset = padding/2;
		
		if(offset <= 0)
		{
			offset = 10;
		}
		
		this.interstitial_head_display = new YAHOO.widget.Panel("interstitial_display_head",  
				{ xy: [offset, 40],
					width: "700px", 
					fixedcenter: false, 
					close: false, 
					draggable: false, 
					zindex: 300,
					modal: true,
					visible: false
				} );
				
		this.interstitial_head_display.setBody("");
		this.interstitial_head_display.render(parent.head.document.body);
	}
};

Overlord.assign({
	minion: "interstitial",
	load: function(element) {
		YAHOO.interstitial.Interstitial.init();
	},
	order: 10	
});

Overlord.assign({
	minion: "interstitial_head",
	load: function(element) {
		YAHOO.interstitial.Interstitial.init();
	},
	order: 10
});