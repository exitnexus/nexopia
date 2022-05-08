if(YAHOO.profile == undefined){
	YAHOO.namespace ("profile");
}

YAHOO.profile.BlogBlock =
{
	init: function()
	{
		this.resize_embeds();
	},
	
	resize_embeds: function()
	{
		var blog_block = document.getElementById("blog_profile_block");
		var obj_list = YAHOO.util.Dom.getElementsBy(function(el){return true;}, "object", blog_block, null);
		
		var i;
		for(i=0; i < obj_list.length; i++)
		{
			var parent_width = parseInt(YAHOO.util.Dom.getStyle(obj_list[i].parentNode, "width"));
			var obj_width = parseInt(YAHOO.util.Dom.getStyle(obj_list[i], "width"));
			var obj_height = parseInt(YAHOO.util.Dom.getStyle(obj_list[i], "height"));
			
			if(obj_width > parent_width)
			{
				var ratio = obj_width / parseFloat(obj_height);
				var new_width = parent_width;
				var new_height = new_width / ratio;
				
				YAHOO.util.Dom.setStyle(obj_list[i], "width", new_width + "px");
				YAHOO.util.Dom.setStyle(obj_list[i], "height", new_height + "px");
				
				var embed_list = YAHOO.util.Dom.getElementsBy(function(el){return true;}, "embed", obj_list[i], null);
				
				var j;
				for(j=0; j < embed_list.length; j++)
				{
					YAHOO.util.Dom.setStyle(embed_list[j], "width", new_width + "px");
					YAHOO.util.Dom.setStyle(embed_list[j], "height", new_height + "px");
				}
			}
		}
	}
};

Overlord.assign({
	minion: "blog_profile_block",
	load: function(element) {
		YAHOO.profile.BlogBlock.init();
	},
	order: 2
});