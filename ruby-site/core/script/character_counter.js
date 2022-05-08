//require script_manager.js

function CharacterCounter(textField, displayElement, maxLimit)
{
	this.textField = textField;
	this.displayElement = displayElement;
	this.maxLimit = maxLimit;
	
	YAHOO.util.Event.addListener(this.textField, "change", this.update, this);
	YAHOO.util.Event.addListener(this.textField, "keydown", this.update, this);
	YAHOO.util.Event.addListener(this.textField, "keyup", this.update, this);	

	this.update(null, this);
}


CharacterCounter.prototype = {
	update: function(e, that)
	{
		if(that.textField.value.length > that.maxLimit)
		{
			that.textField.value = that.textField.value.substring(0, that.maxLimit);
		}
		
		that.displayElement.innerHTML = that.textField.value.length + " / " + that.maxLimit;
	}
};


Overlord.assign({
	minion: "show_character_count",
	load: function(element)
	{
		var maxLength = parseInt(element.getAttribute("maxlength"), 10);
		
		var brElement = document.createElement("br");
		var lengthText = document.createElement("span");
		lengthText.innerHTML = "Length: ";
		var displayElement = document.createElement("span");
		displayElement.id = element.id + "_character_counter";

		element.parentNode.insertBefore(brElement, element.nextSibling);
		element.parentNode.insertBefore(lengthText, brElement.nextSibling);
		element.parentNode.insertBefore(displayElement, displayElement.nextSibling);

		var counter = new CharacterCounter(element, displayElement, maxLength);
	}
});