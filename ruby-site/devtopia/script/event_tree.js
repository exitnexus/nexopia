EventTree = {
	tree: null,
	
	init: function()
	{
		tree = new YAHOO.widget.TreeView("event_tree_div");
		
	//	alert(tree);
		var root = tree.getRoot();
		var tmpNode1 = new YAHOO.widget.TextNode("Task 1", root, false);
		var tmpNode2 = new YAHOO.widget.HTMLNode("<input name='blah' value='blah' style='width: 100px'/>", tmpNode1, true);
		var tmpNode3 = new YAHOO.widget.HTMLNode("<input name='blah2' value='yadayada' style='width: 100px'/>", tmpNode2, true);
		
		//root.refresh();
		tree.draw();
	}
}
