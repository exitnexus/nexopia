Nexopia.tutorial = {
	resort: function(sorttype) {
		var uri = 'worldspost';
		if (sorttype == "m") {
			if(document.getElementById("massdirect").innerHTML) {
				sortdirect = (document.getElementById("massdirect").innerHTML == "v" ? "desc" : "asc");
			} else {
				sortdirect = "desc";
			}
		} else {
			if(document.getElementById("radiusdirect").innerHTML) {
				sortdirect = (document.getElementById("radiusdirect").innerHTML == "v" ? "desc" : "asc");
			} else {
				sortdirect = "asc";
			}
		}
		var parms = "sort=" + sorttype + "&sortdirect=" + sortdirect + "&form_key[]=" + document.getElementById("form_key").value;
		YAHOO.util.Connect.asyncRequest('POST', uri, new ResponseHandler({}), parms);
	}
}

Overlord.assign({
	minion: "tutorial:worlds:resort:r",
	click: function(event) {
		YAHOO.util.Event.preventDefault(event);
		Nexopia.tutorial.resort("r");
	}
});

Overlord.assign({
	minion: "tutorial:worlds:resort:m",
	click: function(event) {
		YAHOO.util.Event.preventDefault(event);
		Nexopia.tutorial.resort("m");
	}
});
