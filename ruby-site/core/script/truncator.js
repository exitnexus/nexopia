/* 
 * Takes an element that should contain only text (will still try to do the right thing otherwise but no promises)
 * Shortens the text to fit in the element (cuts off at a word boundary) and adds ... 1820 1737 1966 1651 
 * Creates a tooltip that appears with the full text content when you hover over the text
 */

function Truncator(element, options) {
	Truncator.queue(this, function() {
		if (!element.truncator) {
			this.element = element;
			this.element.truncator = true;
		
			for (var key in options) {
				this[key] = options[key];
			}

			this.textContent = this.element.innerHTML;
			this.wrapperElement = document.createElement(this.element.tagName);
			if (!this.originalDimensions) {
				this.originalDimensions = this.getDimensions(this.element);
			}
			//alert(this.originalDimensions.height+":"+this.originalDimensions.width);
			for (style in this.element.style) if (this.element.style[style] && style != "length") {
				this.wrapperElement.style[style] = this.element.style[style];
			}
			this.wrapperElement.className = this.element.className;
			this.wrapperElement.style.height = this.originalDimensions.height;
			this.wrapperElement.style.position = "absolute";
			this.wrapperElement.innerHTML = "<span>" + this.textContent + "</span>";
			this.contentElement = this.wrapperElement.firstChild;
			document.getElementsByTagName("body")[0].appendChild(this.wrapperElement);
			this.wrapperElement.style.width = 1000000;
			this.originalDimensions.height = this.wrapperElement.offsetHeight;
			this.wrapperElement.style.width = this.originalDimensions.width;
			
			this.words = this.contentElement.innerHTML.split(/\s/);
			
			this.ellipsify();
			if (this.shortened) {
				this.element.innerHTML = this.wrapperElement.innerHTML;
				this.toolTip = new YAHOO.widget.Tooltip(document.createElement("div"), {
					text: this.textContent,
					context: this.element
				});
			}
		
			this.wrapperElement.parentNode.removeChild(this.wrapperElement);
		}
	});
};

Truncator.count = 0;

Truncator.queue = function(truncator, func) {
	GlobalTimer.setTimeout(function() {func.call(truncator);Truncator.count--;}, 50*Truncator.count);
	Truncator.count++;
};


Truncator.prototype = {
	getDimensions: function(element) {
		if (element.offsetHeight != 0 || element.offsetWidth != 0) {
			return {width: element.offsetWidth, height: element.offsetHeight};
		}
		
		// All *Width and *Height properties give 0 on elements with display none,
		// so enable the element temporarily
		var hiddenElement = element;
		while (hiddenElement.style.display != "none") {
			hiddenElement = hiddenElement.parentNode;
		}
		var originalPosition = hiddenElement.style.position;
		var originalzIndex = hiddenElement.style.zIndex;
		var originalDisplay = hiddenElement.style.display;
		var originalVisibility = hiddenElement.style.visibility;

		hiddenElement.style.position = "absolute";
		hiddenElement.style.zIndex = -1;
		hiddenElement.style.display = "block";
		hiddenElement.style.visibility = "hidden";

		var originalWidth = element.offsetWidth;
		var originalHeight = element.offsetHeight;
		
		hiddenElement.style.display = originalDisplay;
		hiddenElement.style.position = originalPosition;
		hiddenElement.style.visibility = originalVisibility;
		hiddenElement.style.zIndex = originalzIndex;

		return {width: originalWidth, height: originalHeight};
	},
	ellipsify: function() {
		//alert("ellipsify");
		while (this.contentElement.innerHTML.length > this.suffix.length &&
				(this.originalDimensions.height+this.fudgeFactor < this.contentElement.offsetHeight ||
				 this.originalDimensions.width-this.fudgeFactor < this.contentElement.offsetWidth)) {
			this.shorten();
		}
	},
	shorten: function() {
		this.shortened = true;
		var oldHTML = this.contentElement.innerHTML;
		if (this.words.length > 1) {
			var newHTML = oldHTML.substr(0, oldHTML.lastIndexOf(this.words.pop().replace(/[\s\,\.\?\:]+$/,"")));
			newHTML = newHTML.replace(/[\s\,\.\?\:]+$/,"");
			newHTML += this.suffix;
			this.contentElement.innerHTML = newHTML;
		} else {
			if (oldHTML.lastIndexOf(this.suffix) > 0) {
				this.contentElement.innerHTML = oldHTML.substr(0,oldHTML.lastIndexOf(this.suffix)-1) + this.suffix;
			} else {
				this.contentElement.innerHTML = oldHTML.substr(0,(oldHTML.length-(this.suffix.length+1))) + this.suffix;
			}
		}
	},
	updateText: function(newText) {
		if (this.shortened) {
			this.toolTip.destroy();
			this.shortened = false;
		}
		this.textContent = newText;
		this.contentElement.innerHTML = this.textContent;
		this.words = this.contentElement.innerHTML.split(/\s/);
		this.originalWidth = this.contentElement.offsetWidth;
		this.ellipsify();
		if (this.shortened) {
			this.toolTip = new ToolTip(this.element, {text: this.textContent, timeout: 5000, style:{width: this.originalWidth}});
		}
	},
	shortened: false,
	suffix: "...",
	fudgeFactor: 0
};