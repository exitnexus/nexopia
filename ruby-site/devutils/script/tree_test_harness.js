var TestHarness = {
	runTest : function(node) {
		if (node.children.length > 0) {
			for (var index=0; index < node.children.length; index++) {
				TestHarness.runTest(node.children[index]);
			}
			node.expand();
			return false;
		}
		var link = '/tests/' + encodeURIComponent(node.parent.label) + '/' + encodeURIComponent(node.label);
		
		node.labelStyle = "spinner";
		tree.draw();
		//TestHarness.dataModel.setValueAt("<img src=\"/static/images/spinner.gif\" alt=\"Running...\">", row.rowIndex, 2)
		var results = document.getElementById('test-results');
		YAHOO.util.Connect.asyncRequest('GET', link, {
			success: function(response) {
				if (response.responseText.indexOf("PASSED") != -1) {
					node.labelStyle = "pass";
				} else {
					node.labelStyle = "fail";
				}
				tree.draw();
				results.innerHTML = response.responseText + results.innerHTML;
			},
			failure: function(response) {
				node.labelStyle = "not_run";
				tree.draw();
				results.innerHTML = "<span style='color:red'>Failed AJAX request while running test " + node.label + ".</span><br/>" + results.innerHTML;
			},
			argument: [node]
		}, null);

	}
}
YAHOO.util.Event.on(window, 'load', TestHarness.init, TestHarness, true);
