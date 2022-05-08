function pyp_submit1()
{

	var form = (YAHOO.util.Dom.getElementsByClassName('vote_form', 'form')[0]);

	var plus_skin_vote = false;
	var non_plus_skin_vote = false;
	for( var i = 0; i < form.plus_skin.length; i++) {
		if ( form.plus_skin[i].checked ) { 
			plus_skin_vote = true; 
		}
	}
	
	if (!plus_skin_vote) {
		alert("You haven't voted for a skin.");
	} else {
		form.submit();	
	}

}

function pyp_submit2()
{

	var form = (YAHOO.util.Dom.getElementsByClassName('vote_form', 'form')[0]);

	var skin_vote = false;
	for( var i = 0; i < form.skin_entry.length; i++) {
		if ( form.skin_entry[i].checked ) { 
			skin_vote = true; 
		}
	}

	if (!skin_vote) {
		alert("You haven't voted for anything.");
	} else {
		form.submit();	
	}

}
