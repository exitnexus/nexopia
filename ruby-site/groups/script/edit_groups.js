EditGroups =
{
	fromMonthField: null,
	fromYearField: null,
	toMonthField: null,
	toYearField: null,
	presentField: null,
	programmaticUpdate: false,
	groupDataSource: null,
	groupAutoLookup: null,
	locationField: null,
	typeField: null,
	lostMatchesAt: null,
	nameField: null,
	autoCompleteChanging: null,
	
	init: function()
	{
		this.fromMonthField = document.getElementById("from_month");
		this.fromYearField = document.getElementById("from_year");
		this.toMonthField = document.getElementById("to_month");
		this.toYearField = document.getElementById("to_year");
		this.presentField = document.getElementById("present");
		this.locationField = document.getElementById("location");
		this.typeField = document.getElementById("type");
		this.nameField = document.getElementById("name");

		if (this.presentField != null)
		{
			this.toMonthField.disabled = this.presentField.checked;
			this.toYearField.disabled = this.presentField.checked;			
		}
		
		this.programmaticUpdate = false;
		this.lostMatchesAt = "";
		this.autoCompleteChanging = false;
		
		EditGroups.initAutoLookup();
	},
	
	
	onFromDateChange: function()
	{
		if (this.programmaticUpdate) { return; }
		this.programmaticUpdate = true;
		
		if (this.toMonthField.value != -1 && this.toYearField.value != -1 && this.fromYearField.value != -1 &&
			this.fromYearField.value > this.toYearField.value)
		{
			this.toYearField.value = this.fromYearField.value;
		}
		
		if (this.toMonthField.value != -1 && this.toYearField.value != -1 &&
			this.fromYearField.value == this.toYearField.value && 
			this.fromMonthField.value > this.toMonthField.value)
		{
			this.toMonthField.value = this.fromMonthField.value;
		}
		
		this.programmaticUpdate = false;
		
		EditGroups.validateFromDate();
	},
	
	
	onToDateChange: function()
	{
		if (this.programmaticUpdate) { return; }
		this.programmaticUpdate = true;
		
		if (this.fromMonthField.value != -1 && this.fromYearField.value != -1 && this.toYearField.value != -1 &&
			this.toYearField.value < this.fromYearField.value)
		{
			this.fromYearField.value = this.toYearField.value;
		}
		
		if (this.fromMonthField.value != -1 && this.fromYearField.value != -1 && 
			this.toYearField.value == this.fromYearField.value && 
			this.toMonthField.value < this.fromMonthField.value)
		{
			this.fromMonthField.value = this.toMonthField.value;
		}
		
		this.programmaticUpdate = false;
		
		EditGroups.validateToDate();
	},
	
	
	onPresentChange: function()
	{
		if (this.programmaticUpdate) { return; }
		this.programmaticUpdate = true;
		
		if (this.presentField.checked)
		{
			this.toYearField.selectedIndex = 0;
			this.toMonthField.selectedIndex = 0;
			
			this.toYearField.disabled = true;
			this.toMonthField.disabled = true;
		}
		else
		{
			this.toYearField.disabled = false;
			this.toMonthField.disabled = false;
		}
		
		this.programmaticUpdate = false;
		
		EditGroups.validateToDate();
	},
	
	
	validateToDate: function()
	{
		var results = to_chain.validate();
		Validation.displayValidation("to", results.state, results.message);				
	},
	
	
	validateFromDate: function()
	{
		var results = from_chain.validate();
		Validation.displayValidation("from", results.state, results.message);
	},
	
	
	validateName: function()
	{
		var results = name_chain.validate();
		Validation.displayValidation("name", results.state, results.message);
	},
	
	
	validateLocation: function()
	{
		var results = location_chain.validate();
		Validation.displayValidation("location", results.state, results.message);
	},
	
	
	remove: function(id, username) 
	{
		if (confirm("Delete group?")) 
		{
			var url = '/my/groups/remove/' + id;
			if (username)
			{
				url = '/admin/self/' + encodeURIComponent(username) + '/groups/remove/' + id; 
			}
			
			//YAHOO.util.Connect.setForm("multi_group_edit_form");
			YAHOO.util.Connect.asyncRequest('GET', url , new ResponseHandler({
				success: function(o, argument) {
					
				},
				scope: this
			}), "&_=");
		}
	},
	
	
	removeGroup: function(id, username) 
	{
		if (confirm("Delete entire group?")) 
		{
			var url = '/admin/groups/remove_group/' + id + '/' + encodeURIComponent(username); 
			
			//YAHOO.util.Connect.setForm("multi_group_edit_form");
			YAHOO.util.Connect.asyncRequest('GET', url , new ResponseHandler({
				success: function(o, argument) {
					
				},
				scope: this
			}), "&_=");
		}
	},
	
	
	onTypeChange: function()
	{
		EditGroups.groupDataSource.flushCache();
		EditGroups.updateAutoLookupQueryParams();
	},
	
	
	onLocationChange: function()
	{
		EditGroups.groupDataSource.flushCache();
		EditGroups.updateAutoLookupQueryParams();
		EditGroups.validateLocation();
	},
	
	
	updateAutoLookupQueryParams: function()
	{
		this.groupDataSource.scriptQueryAppend = "location=" + this.locationField.value + "&type=" + this.typeField.value;		
	},
	
	
	initAutoLookup: function()
	{
	    // DataSource setup
	    this.groupDataSource = new YAHOO.widget.DS_XHR("/my/groups/query",
	        ["group", "name", "location", "type", "location-id", "type-id"]);
	    this.groupDataSource.scriptQueryParam = "name";
	    this.groupDataSource.responseType = YAHOO.widget.DS_XHR.TYPE_XML;
	    this.groupDataSource.maxCacheEntries = 60;
		this.groupDataSource.queryMatchSubset = true;
		//this.groupDataSource.queryMatchContains = false;
		if (this.locationField != null && this.typeField != null)
		{
	    	this.groupDataSource.scriptQueryAppend = "location=" + this.locationField.value + "&type=" + this.typeField.value;
		}
		this.groupDataSource.connXhrMode = "ignoreStaleResponses";

	    // Instantiate AutoComplete
	    this.groupAutoLookup = new YAHOO.widget.AutoComplete('name','name_container', this.groupDataSource);
	    
		// AutoComplete configuration
		this.groupAutoLookup.autoHighlight = true;
		this.groupAutoLookup.typeAhead = true; 
		this.groupAutoLookup.minQueryLength = 1;
		this.groupAutoLookup.queryDelay = 0.5;
	
		// Fix silly IE6 bug
		if (YAHOO.env.ua.ie > 5 && YAHOO.env.ua.ie <= 7)
		{
			this.groupAutoLookup.useIFrame = true;
		}
	
		// HTML display of results
	    this.groupAutoLookup.formatResult = function(result, sQuery) {
	        // This was defined by the schema array of the data source
	        var name = result[0];
	        var location = result[1];
	        var type = result[2];
			var locationID = result[3];
			var typeID = result[4];

			var modifier = "<span class='secondary_info'>"+type;
			if (locationID != EditGroups.locationField.value)
			{
				modifier = modifier + " (" + location + ")";
			}
			modifier = modifier +"</span><br/>";
	        var markup = "<span>" + name + "</span>";
			
			if (locationID != EditGroups.locationField.value || typeID != EditGroups.typeField.value)
			{
				markup = modifier + markup;
			}

			return (markup);
		};

		// Set up a selection handler to change the location/type if a group is selected outside of the
		// currently selected location and type.
		var itemSelectHandler = function(eventType, args) {
			var selectedElement = args[2];
			
			var name = selectedElement[0];
			var location = selectedElement[1];
			var type = selectedElement[2];
			var locationID = selectedElement[3];
			var typeID = selectedElement[4];
			
			EditGroups.locationField.value = locationID;
			EditGroups.typeField.value = typeID;
		};
		
		if (this.groupAutoLookup.itemSelectEvent != null)
		{
			this.groupAutoLookup.itemSelectEvent.subscribe(itemSelectHandler);
		}
		
		// If the autocomplete query returns an empty result set, we want to stop sending AJAX queries
		// until the string in the text box either becomes shorter than the first time 0 results were
		// returned, or it completely changes. The following two event handlers effectively turn the
		// auto-complete control "on" or "off" at such events in order to create this effect.
		var turnOffAutoCompleteHandler = function(eventType, args) 
		{
			if (EditGroups.autoCompleteChanging || EditGroups.groupAutoLookup.minQueryLength == -1) return;
			EditGroups.autoCompleteChanging = true;
			
			var numberOfResults = args[2].length;
			
			if (numberOfResults == 0)
			{	
				EditGroups.groupAutoLookup.minQueryLength = -1;
				EditGroups.lostMatchesAt = EditGroups.nameField.value;
			}
			
			EditGroups.autoCompleteChanging = false;
			
			return true;
		}
		
		var turnOnAutoCompleteHandler = function() 
		{
			if (EditGroups.autoCompleteChanging || EditGroups.groupAutoLookup.minQueryLength == 1) return;
			EditGroups.autoCompleteChanging = true;
			
			if (EditGroups.lostMatchesAt != "" &&
				(EditGroups.nameField.value.length < EditGroups.lostMatchesAt.length || 
				EditGroups.nameField.value.substr(0, EditGroups.lostMatchesAt.length) != EditGroups.lostMatchesAt))
			{	
				EditGroups.groupAutoLookup.minQueryLength = 1;
				EditGroups.groupAutoLookup.lostMatchesAt = "";
			}
			
			EditGroups.autoCompleteChanging = false;
		}

		if (this.groupAutoLookup.dataReturnEvent != null)
		{
			this.groupAutoLookup.dataReturnEvent.subscribe(turnOffAutoCompleteHandler);
			this.groupAutoLookup.textboxKeyEvent.subscribe(turnOnAutoCompleteHandler);
		}
	}
};

GlobalRegistry.register_handler("edit_groups", EditGroups.init, EditGroups, true);