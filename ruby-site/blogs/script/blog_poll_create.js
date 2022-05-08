function blogs_poll_validate(type) {
	var photo_valid =
	 	document.getElementById('blog_post_photo_link_valid');
	// Allow photo_valid.value == -1 through; user's browser has not
	// yet finished loading, so we'll just assume the image
	// is valid.
	if (photo_valid.value == '0') {
		document.getElementById('blog_post_photo_link').value = '';
	}

	var questionField = document.getElementById("blog_post_title");
	questionField.value = questionField.value.trim();
	if (questionField.value == "") {
		alert('You need to enter a question before you can ' + type);
		return false;
	}
	
	var found = 0;
	for (i = 1; i <= 10; ++i) {
		var elem = document.getElementById("ans_" + i);
		elem.value = elem.value.trim();
		if (elem.value != "") {
			found++;
		}
	}
	if (found < 2) {
		alert('You need to enter at least two answers before you can ' + type);
		return false;
	}

	return true;
}

Overlord.assign({
	minion: "blogs:poll_create:post",
	click: function(event) {
		var valid = blogs_poll_validate('save');
		if (!valid && event) {
			YAHOO.util.Event.preventDefault(event);
		}
		return valid;
	}
});

function blogs_poll_validate_preview() {
	return blogs_poll_validate('preview');
}

Overlord.assign({
	minion: "blogs:poll:preview",
	load: function(event) {
		// We want to prevalidate before allowing preview.
		NexopiaPanel.linkBeforeOpenMap['blogs_poll_preview'] =
		 	blogs_poll_validate_preview;
	}
});

Overlord.assign({
	minion: "blogs:poll_create:add_row",
	click: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		
		// Find first hidden answer
		for (i = 1; i <= 10; ++i) {
			var elem = document.getElementById('ans_' + i);
			if (elem.className == 'hide') {
				elem.className = 'answer';
				elem.focus();
				foundElem = true;
				break;
			}
		}
		// Showing all ten answers?  If so, hide add link
		elem = document.getElementById('ans_10');
		if (elem.className == 'answer') {
			var elem = document.getElementById("add_row");
			YAHOO.util.Dom.setStyle(elem, 'display', 'none');
		}
	}
});

function blog_poll_create_set_photo_options(value) {
	document.getElementById('size_original').disabled = value;
	document.getElementById('size_100').disabled = value;
	document.getElementById('size_75').disabled = value;
	document.getElementById('size_50').disabled = value;
	document.getElementById('size_25').disabled = value;

	document.getElementById('align_center').disabled = value;
	document.getElementById('align_left').disabled = value;
	document.getElementById('align_right').disabled = value;
}

Overlord.assign({
	minion: "blogs:poll_create:add_photo",
	click: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		
		document.getElementById('add_photo_button').className =
		 	'hidden';
		document.getElementById('remove_photo_button').className =
			'shown';
		document.getElementById('add_photo_container').className =
			'shown';
		Overlord.summonMinions(document.getElementById('add_photo_container'));
		blog_poll_create_set_photo_options(false);
	}
});

Overlord.assign({
	minion: "blogs:poll_create:remove_photo",
	click: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		
		document.getElementById('add_photo_button').className =
		 	'shown';
		document.getElementById('remove_photo_button').className =
			'hidden';
		document.getElementById('add_photo_container').className =
			'hidden';
		blog_poll_create_set_photo_options(true);
			
		// Reset the photo field
		document.getElementById('blog_post_photo_link').value = '';
	}
});

Overlord.assign({
	minion: "blogs:poll:photo_options",
	load: function(event) {
		blog_poll_create_set_photo_options(true);
	}
});
