Nexopia = {
	JSONData: {},
	jsonTagData: {},
	json: function(id_or_el) {
		var json = null;
		var el = YAHOO.util.Dom.get(id_or_el);
		if (el) {
			json = el.attributes.json_id.value;
		}
		if (json) {
			return this.jsonTagData[json];
		} else {
			return null;
		}
	},
	areaBaseURI: function() {
		var match;
		if (match = document.location.href.match(new RegExp("(" + Site.adminSelfURL + "/.*?)(/|$)"))) {
			return match[1];
		} else if (document.location.href.match(new RegExp(Site.adminURL))) {
			return Site.adminURL;
		} else if (document.location.href.match(new RegExp(Site.selfURL))) {
			return Site.selfURL;
		} else if (match = document.location.href.match(new RegExp("(" + Site.userURL + "/.*?)(/|$)"))) {
			return match[1];
		} else {
			return Site.wwwURL;
		}
	}
};