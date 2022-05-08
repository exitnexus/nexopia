Views = {
	viewed: null,
	
	
	init: function()
	{
		viewed = new Array();
	},
	
	
	update: function(videoid) 
	{
		is_viewed = viewed["" + videoid];
		if (is_viewed == null || is_viewed == undefined || is_viewed == false)
		{
			YAHOO.util.Connect.asyncRequest('POST', '/videos/view/' + videoid, null, "&_=");
			viewed["" + videoid] = true;
		}
	}
}

Views.init();