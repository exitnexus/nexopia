/*
	The ScriptManager allows you to attach scripts to elements specified by css3 selector rules.
	Load/Unload events get fired based on page load/unload as well as ajax load/unload through the ResponseHandler.
	Arbitrary events can also be attached (click, blur, focus, etc.) these events are also properly migrated
	when nodes are replaced through the ResponseHandler.
	
	Basic usage:
		ScriptManager.register("my css3 selector rule", {
			load: function(element) {
				//do some initialization
			},
			unload: function(element) {
				//do some uninitialization
			},
			click: function(event, element) {
				//onclick handler
			},
			arbitraryevent: function(event, element) {
				//onarbitraryevent handler
			},
			scope: someObject, //the object to execute all functions in the scope of
		});
	
	All options in the options object are optional.  As a general guideline it is best
	to avoid css selectors that aren't prefixed with some id, eg. ".myclass" is bad "#myid .myclass"
	is good.  This is necessary because doing full tree scans to find the classes on
	every page for every css selector for the entire site would be very expensive.  The id
	allows it to immediately eliminate pages without the id.
*/
ScriptManager = {
	toString: function() {
		return this.scripts.join("\n");
	},
	register: function(selector, options) {
		this.scripts.push(new ScriptManager.Script(selector, options));
	},
	setup: function(event, rootNodeOrNodes) {
		rootNodeOrNodes = [].concat(rootNodeOrNodes);
		this.scripts.sort(function(a,b) {
			return b.priority - a.priority;
		});
		for (var i=0; i<this.scripts.length; i++) {
			this.scripts[i].setup(rootNodeOrNodes);
		}
	},
	teardown: function(event, rootNode) {
		for (var i=0; i<this.scripts.length; i++) {
			this.scripts[i].teardown(rootNode);
		}
	},
	scripts: []
};

ScriptManager.Script = function(selector, options) {
	this.selector = selector;
	for (option in options) {
		//Assume any option that is a function and we don't have a default 
		//for is an event to register.  The option click would contain a function 
		//for onclick.
		if (this[option] === undefined && Function.prototype.isPrototypeOf(options[option])) {
			this.registerEventHandler(option, options[option]);
		}
		this[option] = options[option];
	}
};

ScriptManager.Script.prototype = {
	load: null, //function called on load at page load or via ajax
	unload: null, //function called when the element is removed or unloaded
	scope: null, //optional object to execute in the scope of, if it exists load and unload will be called on it
	limitToContext: false, //set this true if you don't want to check upwards from the inserted node when doing matches (generally should not be used for id based selectors)
	priority: 0, //set this value to adjust priority for a script, higher priorities load sooner
	toString: function() {
		return this.selector;
	},
	registerEventHandler: function(event, func) {
		if (!this.eventHandlers) {
			this.eventHandlers = {};
		}
		this.eventHandlers[event] = func;
	},
	setup: function(rootNodes) {
		var that = this;
		if (this.load || this.eventHandlers) {
			if (this.limitToContext) {
				for (var i=0; i<rootNodes.length; i++) {
					var rootNode = rootNodes[i];
					$(this.selector, rootNode).each(function(index, match){
						for (var event in that.eventHandlers) {
							YAHOO.util.Event.on(match, event, that.eventHandlers[event], match, that.scope);
						}
						if (that.load) {
							if (that.scope) {
								that.load.call(that.scope, match);
							} else {
								that.load.call(match, match);
							}
						}
					});
				}
			} else {
				$(this.selector).each(function(index, match){
					for (var i=0; i<rootNodes.length; i++) {
						var rootNode = rootNodes[i];
						if (!rootNode || rootNode == match || YAHOO.util.Dom.isAncestor(rootNode, match)) {
							for (var event in that.eventHandlers) {
								YAHOO.util.Event.on(match, event, that.eventHandlers[event], match, that.scope);
							}
							if (that.load) {
								if (that.scope) {
									that.load.call(that.scope, match);
								} else {
									that.load.call(match, match);
								}
							}
						}
					}
				});
			}
		}
	},
	teardown: function(rootNode) {
		if (this.unload) {
			var that = this;
			var context = null;
			if (this.limitToContext) {
				context = rootNode;
			}
			$(this.selector, context).each(function(index, match){
				if (!rootNode || context || rootNode == match || YAHOO.util.Dom.isAncestor(rootNode, match)) {
					if (that.scope) {
						that.unload.call(that.scope, match);
					} else {
						that.unload.call(match, match);
					}
				}
			});
		}
	}
};

YAHOO.util.Event.on(window, 'load', function() {
	if (!document.getElementById("disable_script_manager")) {
		ScriptManager.setup();
	}
});
YAHOO.util.Event.on(window, 'unload', ScriptManager.teardown, null, ScriptManager);