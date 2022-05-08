function ResponseHandler(options) {
	YAHOO.lang.augmentObject(this, options, true);
	var that = this;
	var success = this.success;
	this.success = function(o) {
		that.handleResponse(o);
		success.call(this, o); //should execute in the scope passed to it by the yui event object
	};
	var failure = this.failure;
	this.failure = function(o) {
		that.handleResponse(o);
		failure.call(this, o); //should execute in the scope passed to it by the yui event object
	};
}

ResponseHandler.registeredHandlers = {};

ResponseHandler.registerIDHandler = function(id, func, obj, overrideScope) {
	ResponseHandler.registeredHandlers[id] = {
		func: func,
		obj: obj,
		overrideScope: overrideScope,
		execute: function() {
			if (overrideScope) {
				this.obj["__" + id] = func;
				this.obj["__" + id](this.obj);
				delete this.obj["__" + id];
			} else {
				this.func(this.obj);
			}
		}
	};
};

ResponseHandler.prototype = {
	success: function(o) {
	},
	failure: function(o) {
	},
	handleResponse: function(o) {
		var xml = document.createElement("temp");
		//The prepended div is necessary to make IE6 parse script nodes that occur before content nodes.
		xml.innerHTML = "<div>THIS IS AN IE6 HACK</div>"+o.responseText;
		xml.removeChild(xml.firstChild);
		var setupNodes = [];
		//execute any script nodes we sent
		var scriptNodes = xml.getElementsByTagName('script');
		for (var i=0; i<scriptNodes.length;i++) {
			var script = scriptNodes[i].innerHTML;
			if (script.indexOf('<!--') == 0) {
				script = script.substring(4, script.lastIndexOf('//-->'));
			}
			eval(script);
		}

		var children = [];
		//we're going to remove nodes so we need to copy the array first
		for (i=0;i<xml.childNodes.length;i++) {
			children.push(xml.childNodes[i]);
		}
		for (i=0; i<children.length; i++) {
			var node = children[i];
			if (node.nodeType != 1) { //Node.ELEMENT_NODE (ie6 doesn't have this constant)
				continue;
			}
			var original = null;
			if (node.attributes && node.attributes.id) {
				original = document.getElementById(node.attributes.id.value);
			}
			if (original) {
				if (this.copyStyle) {
					for (var key in original.style) {
						try {
							node.style[key] = original.style[key];
						} catch (err) {
							//some properties can't be copied, if they can't that's fine just continue with the ones that can
						}
					}
				}
				original.parentNode.replaceChild(node, original);
				Overlord.summonMinions(node);
			} else if (this.newNode) {
				var inserted = this.newNode(node);
				if (inserted) {
					Overlord.summonMinions(node);
				}
			}
			if (!original && !inserted && YAHOO.util.Dom.hasClass(node, 'info_message')) {
				var infoMessages = YAHOO.util.Dom.get('info_messages');
				if (infoMessages) {
					infoMessages.appendChild(node);
				}
			}
			if (node.attributes && node.attributes.id && ResponseHandler.registeredHandlers[node.attributes.id.value]) {
				ResponseHandler.registeredHandlers[node.attributes.id.value].execute();
			}
		}
	},
	copyStyle: false //copy the style tag from the on screen element when substituting?
};
