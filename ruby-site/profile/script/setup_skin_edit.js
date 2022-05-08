if(YAHOO.profile == undefined){
	YAHOO.namespace ("profile");
}

/*
	Setup aspects of the skin editor
*/
YAHOO.profile.UserSkin = {
	init: function() {
		PROFILE.init_user_skin_model();
		PROFILE.init_user_skin_selectors();
		PROFILE.init_user_skin_display_group_selectors();
    }
};

GlobalRegistry.register_handler("user_skin_edit", YAHOO.profile.UserSkin.init, YAHOO.profile.UserSkin, true);