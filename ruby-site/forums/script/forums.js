FORUMS = {};

FORUMS.switchSignal = function(id, save_ajax) {
	var showPlus = false;		
	var elems    = document.getElementsByName('category_' + id);

	for (var i=0; i<elems.length; i++) {
		if (elems[i].style.display == 'none') {
			elems[i].style.display =  '';
		} else {
			showPlus = true;
			elems[i].style.display = 'none';
		}
	}

	if ( showPlus ) {
		document.getElementById('forum_plus_'    + id).style.display = 'inline';
		document.getElementById('forum_minus_'   + id).style.display = 'none';
		
		if (save_ajax) FORUMS.collapse(id, true);
	} else {
		document.getElementById('forum_plus_'    + id).style.display = 'none';
		document.getElementById('forum_minus_'   + id).style.display = 'inline';

		if (save_ajax) FORUMS.collapse(id, false);
	}
};


FORUMS.collapse = function(category, value) {
	var site      = 'http://' + FORUMS.base_domain + '/forums/collapse/' + category + '/' + value;
	var onSuccess = {
		success: function(o) { 
		}
	}
	YAHOO.util.Connect.asyncRequest('GET', site, onSuccess );	
	return false;
};

FORUMS.deleteCategory = function(site) {
	var answer = confirm("Confirm to delete this category? \n\n The forums will return to theirs original categories.");
	
	if (answer) location.href = site;	
};

FORUMS.edit = function(name) {
	var elem = document.getElementById('forum_edit_' + name);

	elem.value = document.getElementById('forum_original_' + name).value;

	document.getElementById('forum_save_'   + name).style.display = 'inline';
	document.getElementById('forum_cancel_' + name).style.display = 'inline';

	elem.className = elem.className.replace(/forum_category_show/, "forum_category_edit");
	elem.className = elem.className.replace(/forum_bg_dark/, "forum_bg_light");

	elem.disabled = false;
	elem.focus();
};

FORUMS.cancel = function(name) {
	var elem = document.getElementById('forum_edit_' + name);

	elem.value = document.getElementById('forum_original_' + name).value;

	FORUMS.hide_editing(name);
};

FORUMS.save = function(name) {
	var elem = document.getElementById('forum_edit_' + name);

	document.getElementById('forum_original_' + name).value = elem.value;
	
	/* If the name is greater than the input size, it will show the beginning of the text, and not the end */
	elem.value = elem.value;

	FORUMS.hide_editing(name);
	
	var site      = 'http://' + FORUMS.base_domain + '/forums/save/category/' + name + '/' + elem.value;
	var onSuccess = {
		success: function(o) { 
		}
	}
	YAHOO.util.Connect.asyncRequest('GET', site, onSuccess );	
};

FORUMS.hide_editing = function(name) {
	var elem = document.getElementById('forum_edit_' + name);

	document.getElementById('forum_save_'     + name).style.display = 'none';
	document.getElementById('forum_cancel_'   + name).style.display = 'none';

	elem.className = elem.className.replace(/forum_category_edit/, "forum_category_show");
	elem.className = elem.className.replace(/forum_bg_light/, "forum_bg_dark");

	elem.disabled = true;
}

FORUMS.filter_focus = function() {
	var filter          = document.getElementById("filter");
	var filter_original = document.getElementById("filter_original");

	if (filter.value == filter_original.value)
		filter.value = "";
};

FORUMS.filter_blur = function() {
	var filter          = document.getElementById("filter");
	var filter_original = document.getElementById("filter_original");

	if (filter.value == "")
		filter.value = filter_original.value;
};

FORUMS.initializeThreadEditor = function()
{	
	var poll_response_row = document.getElementById("poll_response_row");
	var poll_interactive_row = document.getElementById("poll_interactive_row");
	
	poll_response_row.style.display = "none";
	poll_interactive_row.style.display = "";
	
	var poll_div = document.getElementById("poll_creation_div");
	
	var input_array = poll_div.getElementsByTagName("input");
	var i;
	var temp;
	
	for(i=0; i<input_array.length; i++)
	{
		temp = input_array[i];
		temp.disabled = "disabled";
	}
	
	js_preview_0 = document.getElementById("js_preview_0");
	sub_preview_0 = document.getElementById("sub_preview_0");
	js_preview_1 = document.getElementById("js_preview_1");
	sub_preview_1 = document.getElementById("sub_preview_1");
	
	sub_preview_0.style.display = "none";
	js_preview_0.style.display = "";
	sub_preview_1.style.display = "none";
	js_preview_1.style.display = "";
};

FORUMS.showPollDiv = function()
{
	var poll_value = document.getElementById("thread_poll");
	var poll_options = document.getElementById("poll_creation_div");
	var poll_responses = document.getElementById("poll_response_rows");
	
	var i;
	var input_array;
	var temp;
	
	if(poll_value.checked)
	{	
		poll_options.style.display = "";
		poll_responses.disabled = "disabled";
		input_array = poll_options.getElementsByTagName("input");
		for(i=0; i<input_array.length; i++)
		{
			temp = input_array[i];
			temp.disabled = "";	
		}
	}
	else
	{
		poll_options.style.display = "none";
		poll_responses.disabled = "";
		input_array = poll_options.getElementsByTagName("input");
		for(i=0; i<input_array.length; i++)
		{
			temp = input_array[i];
			temp.disabled = "disabled";	
		}
	}
};

FORUMS.addResponseField = function()
{
	var poll_table_body = document.getElementById("poll_creation_body");
	
	var text_input_array = poll_table_body.getElementsByTagName("input");
	var i;
	var temp_array;
	var max_value = -1;
	var max_holder;
	
	for(i=0; i<text_input_array.length; i++)
	{
		temp_array = text_input_array[i].id.split("_");
		
		if(temp_array.length == 3 && temp_array[1] == "response")
		{
			if(temp_array[2] > max_value)
			{
				max_value = parseInt(temp_array[2]);
				
			}
		}
	}
	
	var table_row = document.createElement("tr");
	var table_cell1 = document.createElement("td");
	var table_cell2 = document.createElement("td");
	
	table_cell1.appendChild(document.createTextNode(" "));
	
	var response_input = document.createElement("input");
	response_input.type = "text";
	response_input.id = "poll_response_"+(max_value+1);
	
	table_cell2.appendChild(response_input);
	
	table_row.appendChild(table_cell1);
	table_row.appendChild(table_cell2);
	
	poll_table_body.appendChild(table_row);
};

FORUMS.previewPost = function(url, formName, destinationDivId)
{
	var onSuccess = {
		success: function(o) { 
			var dest = document.getElementById(destinationDivId);
			//alert(o.responseText);
			/*
			 * This is not recommended, but it doesn't render correctly without it.
			 * 
			 * Mike, Jan 24, 2007
			 */
			dest.innerHTML = o.responseText;
			//dest.appendChild(o.responseXML.documentElement);
	
		}
	}

	YAHOO.util.Connect.setForm(formName);
	this.removeAllChildNodes(destinationDivId);
	YAHOO.util.Connect.asyncRequest('POST', url, onSuccess );
	
	return false;
};

FORUMS.removeAllChildNodes = function(nodeId)
{
	if(nodeId == null)
	{
		return;
	}
	node = document.getElementById(nodeId);
	
	while(node.firstChild)
	{
		node.removeChild(node.firstChild);	
	}
};

FORUMS.initializePreviewButton = function()
{
	js_preview = document.getElementById("js_preview");
	sub_preview = document.getElementById("sub_preview");
	
	sub_preview.style.display = "none";
	js_preview.style.display = "";	
};
