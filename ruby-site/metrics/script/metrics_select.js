function handleMetricsCalendarFrom(type,args,obj) { 
    	var dates = args[0]; 
    	var date = dates[0]; 
    	var year = date[0], month = date[1], day = date[2];

	document.getElementById('date_from').value = year + "/" + month + "/" + day;
} 
	
function handleMetricsCalendarTo(type,args,obj) { 
    	var dates = args[0]; 
    	var date = dates[0]; 
    	var year = date[0], month = date[1], day = date[2];

	document.getElementById('date_to').value = year + "/" + month + "/" + day;
} 
	
Overlord.assign({
	minion: "metrics:metrics_select:categories",
	change: function() {
		var all_cats = document.getElementById('metric_category');
		var selected_cat = all_cats.options[all_cats.selectedIndex].value;
		var metrics = Nexopia.JSONData.metricsByCategory[selected_cat];
		var options = document.getElementById('metric_options').options;
		options.length = 0;
		for (var i in metrics) {
			var key = metrics[i][0];
			var value = metrics[i][1];
			var desc = value['description'];
			options[options.length] = new Option(desc, key, false, false);
		}
	}
});

Overlord.assign({
	minion: "metrics:metrics_select:from",
	load: function() {
		var d=new Date();
		d.setDate(d.getDate() - 1);
		var day=d.getDate();
		var month=d.getMonth() + 1;
		var year=d.getFullYear();
		var date = year + "/" + month + "/" + day;
		document.getElementById('date_from').value = date;
		
		var cal = new YAHOO.widget.Calendar("calendar_from");
		cal.cfg.setProperty("title", "From:");
		cal.cfg.setProperty("navigator", true);
		cal.cfg.setProperty("mindate", "02/04/2003");
		cal.cfg.setProperty("maxdate", d);
		cal.cfg.setProperty("selected", month + "/" + day + "/" + year);
		cal.selectEvent.subscribe(handleMetricsCalendarFrom, cal, true);
		cal.render();
	}
});

Overlord.assign({
	minion: "metrics:metrics_select:to",
	load: function() {
		var d=new Date();
		d.setDate(d.getDate() - 1);
		var day=d.getDate();
		var month=d.getMonth() + 1;
		var year=d.getFullYear();
		var date = year + "/" + month + "/" + day;
		document.getElementById('date_to').value = date;
		
		var cal = new YAHOO.widget.Calendar("calendar_to"); 
		cal.cfg.setProperty("title", "To:");
		cal.cfg.setProperty("navigator", true);
		cal.cfg.setProperty("mindate", "02/04/2003");
		cal.cfg.setProperty("maxdate", d);
		cal.cfg.setProperty("selected", month + "/" + day + "/" + year);
		cal.selectEvent.subscribe(handleMetricsCalendarTo, cal, true);
		cal.render();
	}
});

Overlord.assign({
	minion: "metrics:metrics_select:submit",
	click: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		var spinner = document.getElementById('metrics_output');
		spinner.innerHTML = "<img src=\"" + Site.staticFilesURL +
		 	"/Legacy/images/spinner.gif\"/>";
		
		// Let's check that the user selected one or more metrics
		var form = document.getElementById("metrics_select_form");
		var anySelected = false;
		var options = document.getElementById('metric_options').options;
		for (var i = 0; i < options.length; i++) {
			if (options[i].selected) {
				anySelected = true;
				break;
			}
		}
		var view_general = document.getElementById('view_general') &&
		 	document.getElementById('view_general').checked;
		var view_populate = document.getElementById('view_populate') &&
			document.getElementById('view_populate').checked;
		var view_ml = document.getElementById('view_ml') &&
			document.getElementById('view_ml').checked;
		if (!view_general && !view_populate && !view_ml && !anySelected) {
			alert("You need to select one or more metrics.");
			spinner.innerHTML = "";
			return false;
		}
		
		// Good to go, let's grab the results!
		YAHOO.util.Connect.setForm(form);
		if (view_general) {
			var msg = "Are you sure you want to do this?";
			if (!confirm(msg)) {
				spinner.innerHTML = "";
				return;
			}
			// Want to populate DB
			YAHOO.util.Connect.asyncRequest("POST",
				form.action + "/general",
				new ResponseHandler({}), 'ajax=true');
		} else if (view_populate) {
			var msg = "Are you sure you want to do this?";
			if (!confirm(msg)) {
				spinner.innerHTML = "";
				return;
			}
			// Want to populate DB
			YAHOO.util.Connect.asyncRequest("POST",
				form.action + "/populate",
				new ResponseHandler({}), 'ajax=true');
		} else if (view_ml) {
			var msg = "Are you sure you want to do this?";
			if (!confirm(msg)) {
				spinner.innerHTML = "";
				return;
			}
			// Want to repopulate metriclookup table
			YAHOO.util.Connect.asyncRequest("POST",
				form.action + "/metricslookup",
				new ResponseHandler({}), 'ajax=true');
		} else if (document.getElementById('view_html').checked) {
			// Want HTML output
			YAHOO.util.Connect.asyncRequest("POST",
				form.action + "/results_html",
				new ResponseHandler({}), 'ajax=true');
		} else {
			// Want CSV output
			var old_action = form.action;
			form.action = form.action + "/results_csv";
			form.submit();
			form.action = old_action;
			spinner.innerHTML = "";
		}
	}
});

