Overlord.assign({
	minion: "blogs:battle:changetab:ignore",
	click: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
	}
});

Overlord.assign({
	minion: "blogs:battle:changetab:photo",
	click: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		
		YAHOO.util.Dom.setStyle('blog_battle_video', 'display', 'none');
		YAHOO.util.Dom.setStyle('blog_battle_photo', 'display', 'inline');
		document.getElementById('battletype').value = 'photo';
		
		document.getElementById('video_title').name = 'video_title';
		document.getElementById('video_caption_1').name = 'video_caption_1';
		document.getElementById('video_caption_2').name = 'video_caption_2';
		document.getElementById('video_link_1').name = 'video_link_1';
		document.getElementById('video_link_2').name = 'video_link_2';
		
		document.getElementById('photo_title').name = 'blog_post_title';
		document.getElementById('photo_caption_1').name = 'caption_1';
		document.getElementById('photo_caption_2').name = 'caption_2';
		document.getElementById('blog_post_photo_link_photo_1').name = 'link_1';
		document.getElementById('blog_post_photo_link_photo_2').name = 'link_2';
	}
});

Overlord.assign({
	minion: "blogs:battle:changetab:video",
	click: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}

		YAHOO.util.Dom.setStyle('blog_battle_video', 'display', 'inline');
		YAHOO.util.Dom.setStyle('blog_battle_photo', 'display', 'none');
		document.getElementById('battletype').value = 'video';
		
		document.getElementById('photo_title').name = 'photo_title';
		document.getElementById('photo_caption_1').name = 'photo_caption_1';
		document.getElementById('photo_caption_2').name = 'photo_caption_2';
		document.getElementById('blog_post_photo_link_photo_1').name = 'photo_link_1';
		document.getElementById('blog_post_photo_link_photo_2').name = 'photo_link_2';
		
		document.getElementById('video_title').name = 'blog_post_title';
		document.getElementById('video_caption_1').name = 'caption_1';
		document.getElementById('video_caption_2').name = 'caption_2';
		document.getElementById('video_link_1').name = 'link_1';
		document.getElementById('video_link_2').name = 'link_2';
	}
});

function blogs_battle_validate(type) {
	var questionField = document.getElementsByName("blog_post_title")[0];
	if (questionField.value == "") {
		alert('You need to enter a title before you can ' + type);
		return false;
	}
	
	return true;
}

function blogs_battle_photo_validate(type) {
	var elem1 =
	 	document.getElementById('blog_post_photo_link_photo_1_valid');
	var elem2 =
	 	document.getElementById('blog_post_photo_link_photo_2_valid');
	if ((elem1.value == '-1') || (elem2.value == '-1')) {
		alert('Image not yet validated, please wait before clicking ' + type);
		return false;
	} else if ((elem1.value == '0') || (elem2.value == '0')) {
		alert('Invalid image specified');
		return false;
	}
	
	return true;
}

function blogs_battle_video_validate_preview() {
	return blogs_battle_validate('preview');
}

function blogs_battle_photo_validate_preview() {
	return blogs_battle_validate('preview') &&
	 	blogs_battle_photo_validate('preview');
}

Overlord.assign({
	minion: "blogs:battle_create:video:post",
	click: function(event) {
		var valid = blogs_battle_validate('save');
		if (!valid && event) {
			YAHOO.util.Event.preventDefault(event);
		}
		return valid;
	}
});

Overlord.assign({
	minion: "blogs:battle_create:photo:post",
	click: function(event) {
		var valid = blogs_battle_validate('save');
		if (valid) valid = blogs_battle_photo_validate('save');
		if (!valid && event) {
			YAHOO.util.Event.preventDefault(event);
		}
		return valid;
	}
});

Overlord.assign({
	minion: "blogs:battle:video:preview",
	load: function(event) {
		// We want to prevalidate before allowing preview.
		NexopiaPanel.linkBeforeOpenMap['blogs_battle_video_preview'] =
			blogs_battle_video_validate_preview;
	}
});

Overlord.assign({
	minion: "blogs:battle:photo:preview",
	load: function(event) {
		// We want to prevalidate before allowing preview.
		NexopiaPanel.linkBeforeOpenMap['blogs_battle_photo_preview'] =
			blogs_battle_photo_validate_preview;
	}
});

Overlord.assign({
	minion: "blogs:battle_vote:skip",
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
	minion: "blogs:battle_vote:vote",
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
			form.action + this.value + "/battle_vote",
			new ResponseHandler({}), 'ajax=true');
	}
});

Overlord.assign({
	minion: "blogs:battle:photo:caption_1",
	blur: function(event) {
		if (this.value.trim() == '') this.value = 'Photo 1';
	}
});

Overlord.assign({
	minion: "blogs:battle:photo:caption_2",
	blur: function(event) {
		if (this.value.trim() == '') this.value = 'Photo 2';
	}
});

Overlord.assign({
	minion: "blogs:battle:video:caption_1",
	blur: function(event) {
		if (this.value.trim() == '') this.value = 'Video 1';
	}
});

Overlord.assign({
	minion: "blogs:battle:video:caption_2",
	blur: function(event) {
		if (this.value.trim() == '') this.value = 'Video 2';
	}
});
