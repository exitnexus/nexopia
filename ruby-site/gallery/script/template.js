function Template(name) {
	this.templateID = Template.creationCount;
	Template.creationCount++;
	this.rootElement = document.getElementById("template_"+name).cloneNode(true);
	this.recurseElement(this.rootElement);
};

Template.creationCount = 0;

Template.prototype = {
	recurseElement: function(node) {
		if (node.id) {
			node.id = node.id + this.templateID;
		}
		var children = node.childNodes;
		for (var i=0; i<children.length; i++) {
			var child = children.item(i);
			var id = child.id;
			if (id) {
				var match = id.match(/^template_(.+)$/);
				if (match) {
					this[match[1]] = child;
				}
			}
			this.recurseElement(child);
		}
	}
};