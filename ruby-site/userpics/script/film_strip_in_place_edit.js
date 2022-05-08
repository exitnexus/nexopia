if(YAHOO.profile == undefined){
	YAHOO.namespace ("profile");
}

YAHOO.profile.FilmStripInPlaceEdit = {
	init: function()
	{
		var type_dropdown = document.getElementById("film_strip_choice");
		
		if(type_dropdown)
		{
			YAHOO.util.Event.addListener(type_dropdown, "change", YAHOO.profile.FilmStripInPlaceEdit.save_film_strip_choice, null, this);
		}
	},
	
	save_film_strip_choice: function(e)
	{
		var type_form = document.getElementById("film_strip_choice_form");
		var type_dropdown = document.getElementById("film_strip_choice");
		
		YAHOO.util.Connect.setForm(type_form);
		YAHOO.util.Connect.asyncRequest(type_form.method, type_form.action, {
			success: function(o) {
			},
			failure: function(o) {
				alert("Saving of Profile Pic type failed.")
			},
			scope: this
		}, "");
	}
};

Overlord.assign({
	minion: "userpics:film_strip_profile_edit_view",
	load: function(element) {
		YAHOO.profile.FilmStripInPlaceEdit.init();
	}
});