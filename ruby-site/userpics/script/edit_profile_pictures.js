EditProfilePictures = {};

Overlord.assign({
	minion: "epp:add_picture",
	click: function() {
		var spinnerBody = "<div id='edit_picture_panel' class='spinner'><img src='"+Site.staticFilesURL+"/nexoskel/images/large_spinner.gif'/></div>";
		EditProfilePictures.addPictureOverlay = new YAHOO.widget.Panel("picture_edit_EditProfilePictures.addPictureOverlay", {
			fixedcenter: false,
			visible: true,
			modal:true,
			close:false,
			draggable:false,
			underlay: "none"
		});
		EditProfilePictures.addPictureOverlay.setBody(spinnerBody);
		EditProfilePictures.addPictureOverlay.render(document.body);
		EditProfilePictures.addPictureOverlay.center();
		YAHOO.util.Connect.asyncRequest('GET', '#{PageRequest.current.area_base_uri}/gallery/add_profile_pic/', new ResponseHandler({
			success: function(o) {
				YAHOO.util.Dom.get('edit_picture_panel').innerHTML = o.responseText;
				Overlord.summonMinions(document.getElementById('edit_picture_panel'));
				EditProfilePictures.addPictureOverlay.center();
			},
			failure: function(o) {
				EditProfilePictures.addPictureOverlay.destroy();
			},
			scope: this
		}));
	}
});