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
	minion: "blogs:poll_vote:skip",
	click: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		var form = document.getElementById("blog_form");
		YAHOO.util.Connect.setForm(form);
		YAHOO.util.Connect.asyncRequest("POST",
			this.href, new ResponseHandler({}), 'ajax=true');
	}
});

Overlord.assign({
	minion: "blogs:poll_vote:skip_login",
	click: function(event) {
		this.href='/account/login?referer=' +
		 	escape(document.location.href);
	}
});

Overlord.assign({
	minion: "blogs:poll_vote:vote",
	click: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		
		// Let's check that the user actually selected an option
		var radios = document.getElementsByName('vote_' + this.value);
		var anyChecked = false;
		for (var i = 0; i < radios.length; i++) {
			if (radios[i].checked) {
				anyChecked = true;
				break;
			}
		}
		if (!anyChecked) {
			alert("You need to select an option before you can vote.");
			return false;
		}

		// Good to go, let's vote!
		var form = document.getElementById('blog_form');
		YAHOO.util.Connect.setForm(form);
		YAHOO.util.Connect.asyncRequest("POST",
			form.action + this.value + "/poll_vote",
			new ResponseHandler({}), 'ajax=true');
	}
});

Overlord.assign({
	minion: "blogs:poll_vote:vote_login",
	click: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		
		top.location='/account/login?referer=' +
			escape(document.location.href);
	}
});

