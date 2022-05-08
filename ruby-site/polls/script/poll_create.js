function initialize_userpoll_create(dialog) {
	function checkTextEntered()
	{
		var questionField = document.getElementById("question");
		questionField.value = questionField.value.trim();
		if (questionField.value == "") {
			alert("You need to enter a question before you can save.");
			return false;
		}
		
		var found = 0;
		for (i = 1; i <= 10; ++i) {
			var elem = document.getElementById("ans_" + i);
			elem.value = elem.value.trim();
			if (elem.value != "") found++;
		}
		if (found < 2) {
			alert("You need to enter at least two answers before you can save.");
			return false;
		}

		return true;
	}

	dialog.beforeSave = checkTextEntered;
}

Overlord.assign({
	minion: "polls:poll_create:add_row",
	click: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		
		// Find first hidden answer
		for (i = 1; i <= 10; ++i) {
			var elem = document.getElementById("ans_" + i);
			if (YAHOO.util.Dom.getStyle(elem, 'display') == 'none'){
				YAHOO.util.Dom.setStyle(elem, 'display',
				 	'block');
				elem.focus();
				foundElem = true;
				break;
			}
		}
		// Showing all ten answers?  If so, hide add link
		if (YAHOO.util.Dom.getStyle(document.getElementById("ans_10"),
			'display') == 'block') {
			var elem = document.getElementById("add_row");
			YAHOO.util.Dom.setStyle(elem, 'display', 'none');
		}
	}
});

