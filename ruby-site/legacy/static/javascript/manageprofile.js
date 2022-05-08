var lastNameOptions = null;

function constrainRealNameVisibility()
{
	var firstNameSelector = document.getElementById('data[firstnamevisibility]');
	var lastNameSelector = document.getElementById('data[lastnamevisibility]');
	
	if (lastNameOptions == null)
	{
		lastNameOptions = new Array();
		for (var i = 0; i < lastNameSelector.options.length; i++)
		{
			lastNameOptions[i] = lastNameSelector.options[i];
		}
	}
	
	var firstIndex = firstNameSelector.selectedIndex;
	var lastIndex = lastNameSelector.selectedIndex;

	for (var i = 0; i < lastNameSelector.options.length; i++) { lastNameSelector.options[i] = null; };
	
	for (var i = 0; i <= firstIndex; i++)
	{
		lastNameSelector.options[i] = lastNameOptions[i];
	}
	
	if (lastIndex < lastNameSelector.selectedIndex)
		lastNameSelector.selectedIndex = lastIndex;
};