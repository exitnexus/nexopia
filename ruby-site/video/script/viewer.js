Viewer = {
	init: function()
	{
	},
	
	
	show_embed: function() 
	{
		embed_div = document.getElementById("show_embed");
		if (embed_div.style.display == 'none')
			embed_div.style.display = 'block';
		else
			embed_div.style.display = 'none';
	}
}

Viewer.init();