ProductBacklog = {
		
	init: function()
	{
	},
	
	
	updateHierarchy: function(element)
	{
		var checkboxes = YAHOO.util.Dom.getElementsBy(function (e) { return e.attributes['type'].value == "checkbox" }, "input");
		
		for (var i = 0; i < checkboxes.length; i++)
		{
			if (ProductBacklog.above(checkboxes[i],element))
			{
				if (! element.checked)
				{
					checkboxes[i].checked = false;
				}
			}
			else if (ProductBacklog.below(checkboxes[i],element))
			{
				checkboxes[i].checked = element.checked;
			}
		}
	},
		
	
	primaryKey: function(element)
	{
		return element.id.replace(/.*\[(.*?)\]/, "$1").split("_");
	},
	
	
	above: function(element1, element2)
	{	
		var key1 = ProductBacklog.primaryKey(element1);
		var key2 = ProductBacklog.primaryKey(element2);
		
		if (key1.length < key2.length)
		{
			for (var i = 0; i < key1.length; i++)
			{
				if (key1[i] != key2[i])
				{
					return false;
				}
			}
			
			return true;
		}
		else
		{
			return false;
		}
	},
	
	
	below: function(element1, element2)
	{
		var key1 = ProductBacklog.primaryKey(element1);
		var key2 = ProductBacklog.primaryKey(element2);
		
		if (key2.length < key1.length)
		{
			for (var i = 0; i < key2.length; i++)
			{
				if (key1[i] != key2[i])
				{
					return false;
				}
			}
			
			return true;
		}
		else
		{
			return false;
		}
	}
};

GlobalRegistry.register_handler("product_backlog", ProductBacklog.init, ProductBacklog, true);