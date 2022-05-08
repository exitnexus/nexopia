GroupsProfileBlock =
{
	init: function(dialog)
	{
		this.editDialog = dialog;
		GroupsProfileBlock.editDialog = dialog;
		var html = document.getElementById("edit_group_block").innerHTML;
		GroupsProfileBlock.loadJavaScript(html);
		EditGroups.init();
	},
	
	
	hoverOn: function(element, groupid)
	{	
		element.style.backgroundColor = '#CDCDCD';
		
		var overlay = document.getElementById("row_edit_overlay_" + groupid);
		if (overlay == null)
		{
			overlay = document.createElement("div");
			overlay.id = "row_edit_overlay_" + groupid;
			overlay.className = "row_edit_overlay"

			overlay.innerHTML = 
				"<div class='group_edit_row_operations'><a href='javascript:;' onclick='GroupsProfileBlock.editGroup("+groupid+");'>edit</a>&#160;&#160;&#160;<a href='javascript:;' onclick='GroupsProfileBlock.remove("+groupid+");'>X</a></div>";
			element.appendChild(overlay);
		}
		
		overlay.style.visibility = 'visible';
	},
	
	
	hoverOff: function(element, groupid)
	{
		var overlay = YAHOO.util.Dom.getElementsByClassName("row_edit_overlay", "div", element)[0];
		if (overlay != null)
		{
			overlay.style.visibility = 'hidden';
		}
		
	 	element.style.backgroundColor = '#FFFFFF';
	},
	
	
	remove: function(id)
	{	
		GroupsProfileBlock.operationStart();
		if (confirm("Delete group?")) 
		{
			var conn = YAHOO.util.Connect.asyncRequest("GET", "/my/groups/remove/" + id, 
				{
					success: function(o) {
						row = document.getElementById("list_row_" + id);
						row.parentNode.removeChild(row);
						GroupsProfileBlock.operationEnd();
					},
					failure: function(o) {
					}		
				}, "ajax=true");
		}
		else
		{
			GroupsProfileBlock.operationEnd();
		}
	},
	
	
	edit: function(id, onSuccessFunction)
	{
		// Connect to our data source and load the data
		var conn = YAHOO.util.Connect.asyncRequest("GET", "/my/profile_blocks/Groups/edit_group/" + id, new ResponseHandler({
			success: function(o) {
				GroupsProfileBlock.loadJavaScript(o.responseText);
				if (onSuccessFunction)
				{
					onSuccessFunction();
				}
				EditGroups.init();
			},
			failure: function(o) {
			},
			scope: this
		}), "ajax=true");	
	},
	
	
	loadJavaScript: function(responseText)
	{
		// Code taken from a sample given at: http://www.derosion.com/uploads/loadInnerHTML.txt
		// regarding the problem of interpreting javascript that comes back inside an ajax call
		var head = document.getElementsByTagName("head")[0];
		var re = /<script(\b[\s\S]*?)>([\s\S]*?)<\//ig;
		var match;
		// loop through script tags
		while (match = re.exec(responseText)) {
			// generate new script element
			var script = document.createElement('script');
			script.type = 'text/javascript';
			script.defer = 'true';
			// check for src property in this script tag
			var reSrc = /src="([\s\S]*?)"/ig;
			var strSrc = reSrc.exec(match[1]);
			if (strSrc != null) {
				// set js file url as src property
				script.src = strSrc[1];
			} else {
				// OR write js code between script tags
				script.text=match[2];
			}
			// add to DOM
			head.appendChild(script);
		}
	},
	
	
	create: function(onSuccessFunction)
	{
		// Connect to our data source and load the data
		var conn = YAHOO.util.Connect.asyncRequest("GET", "/my/profile_blocks/Groups/create_group", new ResponseHandler({
			success: function(o) {
				GroupsProfileBlock.loadJavaScript(o.responseText);
				if (onSuccessFunction)
				{
					onSuccessFunction();
				}
				setTimeout('EditGroups.init()',10);
			},
			failure: function(o) {
			},
			scope: this
		}), "ajax=true");
	},
	
	
	refreshList: function(onSuccessFunction)
	{
		var conn = YAHOO.util.Connect.asyncRequest("GET", "/my/profile_blocks/Groups/refresh_list", new ResponseHandler({
			success: function(o) {
				if (onSuccessFunction)
				{
					onSuccessFunction();
				}
			},
			failure: function(o) {
			},
			scope: this
		}), "ajax=true");
	},
	
	
	editGroup: function(id)
	{
		GroupsProfileBlock.operationStart();
		
		this.edit(id, GroupsProfileBlock.operationEnd);
	},
	
	
	createNew: function()
	{
		GroupsProfileBlock.operationStart();

		this.create(GroupsProfileBlock.operationEnd);
	},
	
	
	createSubmit: function()
	{
		GroupsProfileBlock.operationStart();

		var editForm = document.getElementById("block_edit_form");		
		var formKey1 = document.getElementById("profile_block_form_key").value;
		var formKey2 = document.getElementById("profile_form_key").value;
		
		YAHOO.util.Connect.setForm(editForm);

		YAHOO.util.Connect.asyncRequest('POST', '/my/profile_blocks/Groups/update', new ResponseHandler({
			success: function(o) {
				var response = GroupsProfileBlock.trim(o.responseText);
				if (response == null || response == "")
				{
					function handleSuccess()
					{
						GroupsProfileBlock.refreshList(GroupsProfileBlock.operationEnd);
					}
			
					GroupsProfileBlock.create(handleSuccess);
				}
				else
				{
					GroupsProfileBlock.operationEnd();
					setTimeout('EditGroups.init()',10);
				}
			},
			failure: function(o) {
			},
			scope: this
		}), "ajax=true&form_key=" + formKey1 + "&form_key=" + formKey2);
	},
	
	
	cancelSubmit: function(id)
	{
		GroupsProfileBlock.operationStart();
	
		if (id == null)
		{
			GroupsProfileBlock.create(GroupsProfileBlock.operationEnd);
		}
		else
		{
			GroupsProfileBlock.edit(id, GroupsProfileBlock.operationEnd);
		}
	},
	
	
	updateSubmit: function(id)
	{
		GroupsProfileBlock.operationStart();

		var dialog = this.editDialog;
		function enableButtons()
		{
			dialog.enableButtons();
			GroupsProfileBlock.enableButtons();
			GroupsProfileBlock.hideSpinner();
		}
		
		var editForm = document.getElementById("block_edit_form");
		var formKey = editForm.edit_form_key.value;

		YAHOO.util.Connect.setForm(editForm);

		YAHOO.util.Connect.asyncRequest('POST', '/my/profile_blocks/Groups/update', new ResponseHandler({
			success: function(o) {
				var response = GroupsProfileBlock.trim(o.responseText);
				if (response == null || response == "")
				{
					GroupsProfileBlock.edit(id, enableButtons);
				}
				else
				{
					enableButtons();
				}
			},
			failure: function(o) {
			},
			scope: this
		}), "ajax=true&form_key=" + formKey);
	},
	
	
	disableButtons: function()
	{
		GroupsProfileBlock.setButtonDisabled("group_create_new_button", true);
		GroupsProfileBlock.setButtonDisabled("group_create_button", true);
		GroupsProfileBlock.setButtonDisabled("group_cancel_button", true);
		GroupsProfileBlock.setButtonDisabled("group_update_button", true);
	},
	
	
	enableButtons: function()
	{
		GroupsProfileBlock.setButtonDisabled("group_create_new_button", false);
		GroupsProfileBlock.setButtonDisabled("group_create_button", false);
		GroupsProfileBlock.setButtonDisabled("group_cancel_button", false);
		GroupsProfileBlock.setButtonDisabled("group_update_button", false);
	},
		
	
	setButtonDisabled: function(buttonID, disabled)
	{
		var button = document.getElementById(buttonID);
		if (button)
		{
			button.disabled = disabled;
		}
	},
	
	
	trim: function(text)
	{
		return text.replace(/^\s+|\s+$/g,"");
	},
	
	
	showSpinner:function()
	{
		GroupsProfileBlock.getSpinner().style.display= "block";
	},
	
	
	hideSpinner: function()
	{
		GroupsProfileBlock.getSpinner().style.display = "none";
	},
	
	
	getSpinner: function()
	{
		var spinner = YAHOO.util.Dom.getElementsByClassName("edit_group_operation_spinner", "div", null)[0];
		
		return spinner;
	},
	
	
	operationStart: function()
	{
		GroupsProfileBlock.editDialog.disableButtons();
		GroupsProfileBlock.disableButtons();
		GroupsProfileBlock.showSpinner();		
	},
	
	
	operationEnd: function()
	{
		GroupsProfileBlock.editDialog.enableButtons();
		GroupsProfileBlock.enableButtons();
		GroupsProfileBlock.hideSpinner();
	}
};

GlobalRegistry.register_handler("edit_groups", GroupsProfileBlock.init, GroupsProfileBlock, true);