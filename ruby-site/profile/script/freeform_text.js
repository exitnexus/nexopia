function initialize_enhanced_text_input_for_dialog(dialog)
{
	var textField = document.getElementById("freeform_content");
	var titleField = document.getElementById("freeform_content_title");

	function checkTextEntered()
	{
		if (textField.value == "")
		{
			alert("You need to enter some text before you can save.");

			return false;
		}

		return true;
	}

	dialog.beforeSave = checkTextEntered;
}