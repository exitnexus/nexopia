/*
	Description:

	This takes a regular select element and turns it into a YUI menu button, with Nexopia's custom
	button style.


	Usage:
	
		<select minion_name="themed_select">
			<option value="-1">{DisplayName}</option>
			<option value="{Value1}">{Option1}</option>
			<option value="{Value2}">{Option2}</option>
			<option value="{Value3}">{Option3}</option>
				.
				.
				.
			<option value="{ValueN}">{OptionN}</option>
		</select>
	
	for a basic themed select box, where the width of the select box will be based on the width
	of the widest option in the original list. Note that the first <option> tag will be used to
	set a starting label for the button and removed from the list of selectable options.
	
	
	Optional:
	
		style="max-width: {MaximumWidth}"
		
	can be set on the <select> tag for a themed select box that is {MaximumWidth} pixels wide.
	
	
	Functions:
	
		getSelected():
			returns an object with "label" and "value" properties, corresponding to the "innerHTML"
			and "value" of the original <option> tags. So, in the above usage example, if {Option1}
			is selected, getSelected().label would return {Option1} and getSelected().value would
			return {Value1}. If nothing is selected, this function returns null.
*/

if(YAHOO.nexopia == undefined){
	YAHOO.namespace("nexopia");
}

if(YAHOO.nexopia.Select == undefined){
	YAHOO.namespace("nexopia.Select");
}

YAHOO.nexopia.Select.generatedIDSequence = 0;

Overlord.assign({
	minion: "themed_select",
	load: function(element)
	{
		if (YAHOO.nexopia.Select.list == undefined)
		{
			YAHOO.nexopia.Select.list = [];
		}
		
		// In case there's no id on the select element, just generate one.
		if (element.id == "")
		{
			element.id = "themed_select_" + YAHOO.nexopia.Select.generatedIDSequence;

			YAHOO.nexopia.Select.generatedIDSequence = YAHOO.nexopia.Select.generatedIDSequence + 1;
		}
		
		YAHOO.nexopia.Select.list[element.id] = new ThemedSelect(element);
	},
	order: -1
});


function ThemedSelect(element)
{
	// If the select element has a CSS "max-width" style setting, use that. Otherwise, we 
	// set the size to be the original size of the select tag plus a little extra to make 
	// sure all the options will fit in the display.
	var originalWidth = parseInt(element.offsetWidth, 10);
	var maxWidth = parseInt(element.style.maxWidth, 10);
	var newWidth = (originalWidth + 14);
	if (maxWidth != undefined && newWidth > maxWidth)
	{
		newWidth = maxWidth;
	}

	// Need to generate an INPUT component for YUI to base the button part of the menu
	// button off of.
	this.inputElement = document.createElement("input");
	this.inputElement.id = element.id + "_button";
	this.inputElement.name = element.name;
	
	// Use the first element of the select list to get the display name of the button
	// and then remove it from the actual list so that it doesn't display as an option.
	this.nullLabel = element.options[0].innerHTML;
	this.inputElement.value = this.nullLabel;
	element.remove(0);
		
	// Put the generated button into the document for YUI's Menu Button component to use
	element.parentNode.insertBefore(this.inputElement, element);
	
	// Create the menu button to replace both the generated button and the selector	
	this.menuButton = new YAHOO.widget.Button(this.inputElement.id,
		{ 
			type: "menu", 
			menu: element.id,
			lazyloadmenu: false
		});
	this.menuButton.getMenu().cfg.setProperty("iframe", true);
	this.menuButton.getMenu().cfg.setProperty("zindex", "500");
	this.menuButton.addClass("themed_select_button");
	
	var buttonElement = this.menuButton.getElementsByTagName("button")[0];
	buttonElement.style.width = newWidth + "px";
	buttonElement.style.maxHeight = buttonElement.offsetHeight + "px";
	
	var containerElement = YAHOO.util.Dom.getAncestorByClassName(buttonElement, "yui-menu-button");
	containerElement.style.width = newWidth + "px";
	
	// Set up the menu button so that it changes its label to reflect the selected element
	function onMenuClick(eventType, args, that) 
	{  	
		var menuItem = args[1];
	
		if (menuItem) 
		{
			if (menuItem.cfg.getProperty("disabled"))
			{
				that.setSelected(null);
			}
			else
			{
				var newLabel = menuItem.cfg.getProperty("text");
				that.menuButton.set("label", newLabel);
			}
		}
	}
	this.menuButton.getMenu().subscribe("click", onMenuClick, this);
	
	YAHOO.util.Dom.addClass(this.menuButton.getMenu().element, "themed_select_menu");

	// Set the menu popup to be the same width as the button
	this.menuButton.getMenu().cfg.setProperty("width", newWidth + "px");
	// IE needs some help doing this because it likes to make web development as painful as possible
	var menuBody = YAHOO.util.Dom.getElementsByClassName("bd", "div", this.menuButton.getMenu().element)[0];
	// This won't exist if there are no options to select.
	if (menuBody)
	{
		menuBody.style.width = newWidth + "px";
	}
};


ThemedSelect.prototype = 
{
	getSelected: function()
	{
		var item = this.menuButton.getMenu().activeItem;
		if (item == null || item == undefined)
		{
			return null;
		}
		else
		{
			return { label: item.cfg.getProperty("text"), value: item.value };
		}
	},
	
	
	setSelected: function(value)
	{
		if (value == null)
		{
			this.menuButton.getMenu().activeItem = null;
			this.menuButton.set("label", this.nullLabel);
			
			return;
		}
		
		var items = this.menuButton.getMenu().getItems();
		for (var i=0; i < items.length; i++)
		{
			if (items[i].value == value)
			{
				this.menuButton.getMenu().activeItem = items[i];
				this.menuButton.set("label", items[i].cfg.getProperty("text"));
				break;
			}
		}
	},
	
	
	setEnabled: function(value, enabled)
	{
		var items = this.menuButton.getMenu().getItems();
		for (var i=0; i < items.length; i++)
		{
			if (items[i].value == value)
			{
				items[i].cfg.setProperty("disabled", ! enabled);
				break;
			}
		}
	},
	
	
	enable: function(value)
	{
		this.setEnabled(value, true);
	},
	
	
	disable: function(value)
	{
		this.setEnabled(value, false);
	},
	
	
	subscribe: function(eventName, handlerFunction, object)
	{
		this.menuButton.getMenu().subscribe(eventName, handlerFunction, object);
	}
};