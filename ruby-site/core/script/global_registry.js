/*
	Javascript usage: In your javascript file call GlobalRegistry.register_handler to notify the system of your interest
	in a docid.  The params you pass are:
		docid: The a string with the id of the document you care about.
		func: The callback function you want to run when the document is loaded.
		obj: An arbitrary object that will be passed to the function.
		override_this: Boolean value, if true then func will execute in the scope of obj; that is in func (this == obj).
	
	Template usage:
		Simply include the attribute t:docid="some identifier string" on your root node.
*/
GlobalRegistry = {
	init: function() {
		YAHOO.util.Event.onDOMReady(this.initialize_docids, null, this, true);
	},
	register_docid: function(docid) {
		this.docids = this.docids.concat(docid);
	},
	register_handler: function(docid, func, obj, override_this, fire_multiple) {
		docid = [].concat(docid);
		var handler = {
			docid: docid,
			func: func,
			obj: obj,
			override_this: override_this,
			fire_multiple: fire_multiple,
			unfired: true
		};
		for (var i=0; i<handler.docid.length; i++) {
			if (this.handlers[handler.docid[i]]) {
				this.handlers[handler.docid[i]] = this.handlers[handler.docid[i]].concat(handler);
			} else {
				this.handlers[handler.docid[i]] = [handler];
			}
		}
	},
	initialize_docids: function() {
		for (var doc_index=0; doc_index < this.docids.length; doc_index++) {
			if (this.handlers[this.docids[doc_index]]) {
				var current_handlers = this.handlers[this.docids[doc_index]];
				for (var handler_index=0; handler_index < current_handlers.length; handler_index++) {
					var current_handler = current_handlers[handler_index];
					if (current_handler.unfired || current_handler.fire_multiple) {
						if (current_handler.override_this) {
						current_handler.obj.global_registry_function = current_handler.func;
						current_handler.obj.global_registry_function(current_handler.obj);
						current_handler.obj.global_registry_function = null;	
						} else {
							current_handler.func(obj);
						}
						current_handler.unfired = false;
					}
				}
			}
		}
	},
	docids: new Array(),
	handlers: {}
};

GlobalRegistry.init();
