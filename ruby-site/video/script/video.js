Video = {
	init: function()
	{
		var elements = YAHOO.util.Dom.getElementsBy(function (e) { return e.id.match(/thumbnail_\d+/) }, "div");
		for (var i = 0; i < elements.length; i++)
		{
			new Truncator(elements[i], {fudgeFactor:4});
		}
	}
}

GlobalRegistry.register_handler("video_main", Video.init, Video, true);
