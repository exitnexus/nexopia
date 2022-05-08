function DateSelector(callingObject)
{
	this.removedDayOptions = new Array();
	this.callingObject = callingObject;
	
	this.daySelector = YAHOO.util.Dom.getElementsByClassName("day", "select", this.callingObject)[0];
	this.monthSelector = YAHOO.util.Dom.getElementsByClassName("month", "select", this.callingObject)[0];
	this.yearSelector = YAHOO.util.Dom.getElementsByClassName("year", "select", this.callingObject)[0];
	
	YAHOO.util.Event.on(this.monthSelector, "change", this.updateDays, this, true);
	YAHOO.util.Event.on(this.yearSelector, "change", this.updateDays, this, true);
}


DateSelector.prototype =
{
	updateDays: function()
	{
		if (this.daySelector != null)
		{
			var dayIndex = this.daySelector.selectedIndex;
		}
		var monthIndex = this.monthSelector.selectedIndex;
		var yearIndex = this.yearSelector.selectedIndex;
	
		if (this.daySelector != null)
		{
			var dayOptions = this.daySelector.options;
		}
		var monthOptions = this.monthSelector.options;
		var yearOptions = this.yearSelector.options;
	
		if (this.daySelector != null)
		{
			var day = dayOptions[dayIndex].value;
		}
		var month = monthOptions[monthIndex].value;
		var year = yearOptions[yearIndex].value;
	
		if (this.daySelector != null)
		{	
			var maxDays = 31;
			//	January, March, May, July, August, October, and December have 31 days
			if (this.arrayHas([1,3,5,7,8,10,12], month))
			{
				maxDays = 31;
			}
			// April, June, September, and November have 30 days
			else if (this.arrayHas([4,6,9,11], month))
			{
				maxDays = 30;
			}
			// February has 29 days on a leap year and 28 otherwise
			else if (this.arrayHas([2], month))
			{
				if (year % 4 == 0 || year == -1)
				{
					maxDays = 29;
				}
				else
				{
					maxDays = 28;
				}
			}

			// Restore any removed days
			while(this.removedDayOptions.length > 0) 
			{
				dayOptions[dayOptions.length] = this.removedDayOptions.pop(); 
			}
	
			// Remove days that fall after the maximum number of days for the month.
			// The reason for the -1 in the while clause is that one of the dayOptions
			// option items is used to contain the "Day" label.
			while(dayOptions.length - 1 > maxDays) 
			{
				this.removedDayOptions.push(dayOptions[dayOptions.length - 1]);
				dayOptions[dayOptions.length - 1] = null;
			}
	
			if (day > maxDays)
			{
				this.daySelector.selectedIndex = maxDays;
			}
		}
	},


	arrayHas: function(arrayObject, object)
	{
		for(var i=0; i < arrayObject.length; i++)
		{
			if (arrayObject[i] == object)
			{
				return true;
			}
		}
	
		return false;
	}
}


DateSelectorInitializer = {
	init: function()
	{
		elements = YAHOO.util.Dom.getElementsByClassName("date_selector", "div");
		for (var i = 0; i < elements.length; i++)
		{
			new DateSelector(elements[i]);		
		}
	}
}


GlobalRegistry.register_handler("date_selector", DateSelectorInitializer.init, DateSelectorInitializer, true);