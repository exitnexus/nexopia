/*
	headerElement: the element to click on to reveal bodyElement
	bodyElement: the element to reveal
	options: hash of options that override defaults in the prototype, see the prototype for what is available
*/
function Revealer(headerElement, bodyElement, options) {
	for (var key in options) {
		this[key] = options[key];
	}
	this.headerElement = YAHOO.util.Dom.get(headerElement);
	if (bodyElement) {
		this.bodyElement = YAHOO.util.Dom.get(bodyElement);
	} else {
		this.bodyElement = this.headerElement.id + "_body";
	}
	YAHOO.util.Event.on(this.headerElement, 'click', this.toggle, this, true);
};

Revealer.prototype = {
	toggleInnerHTML: null, //innerHTML content for what the innerHTML should change to when it is toggled, null for no change
	display: 'block', //What display is set to to show the element, block by default but sometimes inline may be required
	toggle: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		if (this.toggleInnerHTML !== null) {
			var oldInnerHTML = this.headerElement.innerHTML;
			this.headerElement.innerHTML = this.toggleInnerHTML;
			this.toggleInnerHTML = oldInnerHTML;
		}
		if (YAHOO.util.Dom.getStyle(this.bodyElement, 'display') == 'none') {
			YAHOO.util.Dom.setStyle(this.bodyElement, 'display', this.display);
		} else {
			YAHOO.util.Dom.setStyle(this.bodyElement, 'display', 'none');
		}
	}
};