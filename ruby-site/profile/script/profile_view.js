if(YAHOO.profile == undefined){
	YAHOO.namespace ("profile");
}

/*
	This is needed for the profile views count. It get inclued on the profile page when the
	 profile user account has been view rate limited. It's included from view_profile.
*/
YAHOO.profile.Profile =
{
	init: function(request_form)
	{
		if(request_form.tagName.toLowerCase() == "form" && request_form.id == "request_profile_view")
		{
			YAHOO.util.Connect.setForm(request_form);
			YAHOO.util.Connect.asyncRequest(request_form.method, request_form.action, {
				success: function(o) {
				},
				failure: function(o) {
				},
				scope: this
			}, "");
		}
	}
};

Overlord.assign({
	minion: "request_profile_view",
	load: function(element) {
		YAHOO.profile.Profile.init(element);
	}
});