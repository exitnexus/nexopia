function initialize_userpoll_vote(dialog) {
	function checkVoteEntered()
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

PollManagement = {
	voteElement: null,
	vote: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		
		// Let's check that the user actually selected an option
		var form = document.getElementById("userpoll_vote_form");
		var anyChecked = false;
		for (var i = 0; i < form.vote.length; i++) {
			if (form.vote[i].checked) {
				anyChecked = true;
				break;
			}
		}
		if (!anyChecked) {
			alert("You need to select an option before you can vote.");
			return false;
		}

		// Good to go, let's vote!		
		YAHOO.util.Connect.setForm(form);
		YAHOO.util.Connect.asyncRequest("POST",
			form.action + "/vote",
			new ResponseHandler({}), 'ajax=true');
	},

	skip: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		var form = document.getElementById("userpoll_vote_form");
		YAHOO.util.Connect.setForm(form);
		YAHOO.util.Connect.asyncRequest("POST",
			form.action + "/skip",
			new ResponseHandler({}), 'ajax=true');
	}
};

Overlord.assign({
	minion: "polls:poll_vote",
	load: function(element) {
		PollManagement.voteElement = element;
	}
});

Overlord.assign({
	minion: "polls:poll_vote:vote",
	click: PollManagement.vote,
	scope: PollManagement
});

Overlord.assign({
	minion: "polls:poll_vote:skip",
	click: PollManagement.skip,
	scope: PollManagement
});
