function ToolTip(element, options) {
	this.element = element;
	if (!this.element.tooltip) {
		this.element.tooltip = this;
		this.mouseover = YAHOO.util.Event.addListener(this.element, "mouseover", this.mouseover);
		this.mouseout = YAHOO.util.Event.addListener(this.element, "mouseout", this.mouseout);
		this.insertionPoint = document.getElementsByTagName("body")[0];
		for (var key in options) {
			if (key == "style" ) {
				var newStyle = {};
				for (var style in this.style) {
					newStyle[style] = this.style[style];
				}
				for (var style in options[key]) {
					newStyle[style] = options[key][style];
				}
				this.style = newStyle;
			} else {
				this[key] = options[key];
			}
		}
		this.node = document.createElement("div");
	}
};

ToolTip.prototype = {
	mouseover: function(e) {this.tooltip.show(e);},
	mouseout: function(e) {this.tooltip.hide(e);},
	destroy: function() {
		this.hide();
		YAHOO.util.Event.removeListener(this.element, "mouseover", this.mouseover);
		YAHOO.util.Event.removeListener(this.element, "mouseout",this.mouseout)
		delete this.toolTip;
	},
	show: function(e) {
		if (this.innerHTML) {
			this.node.innerHTML = this.innerHTML;
			this.node = this.node.firstChild;
		} else {
			this.node.innerHTML = "<div>"+this.text+"</div>";
			for (var key in this.style) if (this.style[key]){
			  this.node.style[key] = this.style[key];
			}
			this.node.style.position = 'absolute';
		}
		var xy = YAHOO.util.Event.getXY(e);
		this.node.style.left = xy[0]+2;
		this.node.style.top = xy[1]-15;
		this.insertionPoint.appendChild(this.node);
		GlobalTimer.setTimeout(function() {this.hide();}, this.timeout, this, true);
	},
	hide: function() {
		if (this.node.parentNode == this.insertionPoint) {
			this.insertionPoint.removeChild(this.node);
		}
	},
	timeout: 1000,
	text: null, //The text content of the tooltip
	innerHTML: null, //Overrides the basic rendering and ignores text if it is set
	style: { //style attribute for the tooltip div
		backgroundColor: 'CornSilk',
		color: 'Black',
		border: '1px solid black',
		textAlign: 'left',
		padding: '1px'
	}
};