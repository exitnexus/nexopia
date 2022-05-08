/*
	locationNameFieldID:	The field that the user types the location into.
	locationMatchDivID: 	The DIV that will display the possible matching locations for what was typed into the lookup field.
	locationIDFieldID: 		The field (should be hidden) that stores the id value for the location the user has selected.
*/
function LocationAutocomplete(locationNameFieldID, locationMatchDivID, locationIDFieldID, locationQuerySubLocationsFieldID)
{
	this.locationNameFieldID = locationNameFieldID;
	this.locationMatchDivID = locationMatchDivID;
	this.locationIDFieldID = locationIDFieldID;
	this.locationQuerySubLocationsFieldID = locationQuerySubLocationsFieldID;
	this.initialize();
}

LocationAutocomplete.prototype = {
	initialize: function()
	{
	    // DataSource setup
	    this.locationDataSource = new YAHOO.widget.DS_XHR("/core/query/location",
	        ["location", "name", "id", "extra", "query_sublocations"]);
	    this.locationDataSource.scriptQueryParam = "name";
	    this.locationDataSource.responseType = YAHOO.widget.DS_XHR.TYPE_XML;
	    this.locationDataSource.maxCacheEntries = 60;
		// this.locationDataSource.queryMatchSubset = true;

		this.locationDataSource.connXhrMode = "ignoreStaleResponses";

	    // Instantiate AutoComplete
	    this.locationAutoLookup = new YAHOO.widget.AutoComplete(this.locationNameFieldID, this.locationMatchDivID, this.locationDataSource);
	    
		// AutoComplete configuration
		this.locationAutoLookup.autoHighlight = true;
		// this.locationAutoLookup.typeAhead = true;
		this.locationAutoLookup.minQueryLength = 1;
		this.locationAutoLookup.queryDelay = 0;
		this.locationAutoLookup.forceSelection = true;
		this.locationAutoLookup.maxResultsDisplayed = 10;
	
		// Fix silly IE6 bug
		if (YAHOO.env.ua.ie > 5 && YAHOO.env.ua.ie <= 7)
		{
			this.locationAutoLookup.useIFrame = true;
		}

		this.locationIDField = document.getElementById(this.locationIDFieldID);
		this.locationQuerySubLocationsField = document.getElementById(this.locationQuerySubLocationsFieldID);
		var itemSelectHandler = function(eventType, args, obj) {
			var selectedElement = args[2];
			
			var name = selectedElement[0];
			var id = selectedElement[1];
			var extra = selectedElement[2];
			var subLocations = selectedElement[3];
			
			obj.locationIDField.value = id;
			obj.locationQuerySubLocationsField.value = subLocations;
		};
		this.locationAutoLookup.itemSelectEvent.subscribe(itemSelectHandler, this);
	
		var forceSelectionClearHandler = function(eventType, args, obj)
		{
			obj.locationIDField.value = 0;
			obj.locationQuerySubLocationsField.value = "true";
		};
		this.locationAutoLookup.selectionEnforceEvent.subscribe(forceSelectionClearHandler, this);
	
		// HTML display of results
		this.locationAutoLookup.formatResult = function(result, sQuery) {
			// This was defined by the schema array of the data source
			var name = result[0];
			var id = result[1];
			var extra = result[2];
			
			var extraInfo = "";
			if (extra != undefined && extra != "")
				extraInfo = "<br/>" + "<span style='font-size: 10px; color: grey'>" + " ("+ extra + ")" + "</span>";
			
			return name + extraInfo;
		};
	}	
};

Overlord.assign({
	minion: "location_autocomplete",
	load: function(element) {
		locationNameField = element;
		locationMatchDiv = YAHOO.util.Dom.getNextSiblingBy(element, function(el){return el.className == 'matches';});
		locationIDField = YAHOO.util.Dom.getNextSiblingBy(element, function(el){return el.className == 'location_id';});
		locationQuerySubLocationsField = YAHOO.util.Dom.getNextSiblingBy(element, function(el){return el.className == 'query_sublocations';});
		
		new LocationAutocomplete(locationNameField.id, locationMatchDiv.id, locationIDField.id, locationQuerySubLocationsField.id);
	}
});