function ResponseHandler(options) {
	for (option in options) {
		if (option == "success") {
			var __success = options[option];
		} else {
			this[option] = options[option];
		}
	}
	if (__success) {
		var __originalSuccess = this.success;
		this.success = function(o) {
			__originalSuccess(o);
			this.__success = __success;
			this.__success(o);
		};
	}
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
		var xml = document.createElement("temp");
		xml.innerHTML = o.responseText;
		for (var i=0; i<xml.childNodes.length; i++) {
			var node = xml.childNodes[i];
			if (!node.id) {
				continue;
			}
			var original = document.getElementById(node.attributes.id.value);
			if (original) {
				original.parentNode.replaceChild(node, original);
			}
			if (ResponseHandler.registeredHandlers[node.id]) {
				ResponseHandler.registeredHandlers[node.id].execute();
			}
		}
	},
	failure: function(o) {
	}
};
