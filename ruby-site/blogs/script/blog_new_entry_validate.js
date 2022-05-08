function blogs_photo_validate(type) {
	var elem = document.getElementById('blog_post_photo_link_valid');
	if (elem) {
		// Allow elem.value == -1 through; user's browser has not
		// yet finished loading, so we'll just assume the image
		// is valid.
		if (elem.value == '0') {
			alert('No valid image specified');
			return false;
		}
	}
	
	return true;
}

function blogs_validate_preview() {
	return blogs_photo_validate('preview') && blogs_title_validate();
}

function blogs_title_validate() {
	var title = document.getElementsByName('blog_post_title')[0];
	if (title) {
		title.value = title.value.trim();
		if (title.value == '') {
			alert('No title specified');
			return false;
		}
	}
	return true;
}

Overlord.assign({
	minion: "blogs:preview",
	load: function(event) {
		NexopiaPanel.linkBeforeOpenMap['blogs_preview'] =
			blogs_validate_preview;
	}
});

Overlord.assign({
	minion: "blogs:post",
	click: function(event) {
		var valid = true;
		if (document.getElementById('blog_post_photo_link_valid')) {
			valid = blogs_photo_validate('save');
		}
		if (valid) valid = blogs_title_validate();

		if (!valid && event) {
			YAHOO.util.Event.preventDefault(event);
		}
		
		return valid;
	}
});
