GroupsProfileBlock =
{
	init: function(dialog)
	{
		this.editDialog = dialog;
		GroupsProfileBlock.editDialog = dialog;
		var editGroupList = document.getElementById("groups_block_group_list");

		var editGroupDiv = document.getElementById("edit_group_block");
		if (editGroupDiv)
		{
			var html = editGroupDiv.innerHTML;
			GroupsProfileBlock.showOperationsBar = true;
			GroupsProfileBlock.loadJavaScript(html);
		}
		else
		{
			GroupsProfileBlock.showOperationsBar = false;
		}
		
		EditGroups.init();
		GroupsProfileBlock.attachListeners();
	},
	
	
	attachListeners: function()
	{
		var editGroupList = document.getElementById("groups_block_group_list");
		
		var listRows = YAHOO.util.Dom.getElementsByClassName("list_row", "div", editGroupList);
		for (var i = 0; i < listRows.length; i++)
		{
			var listRow = listRows[i];
			 
			if (listRow.id != "")
			{
				var groupId = listRow.id.substring("list_row_".length);
			
				function onMouseOver(e,id)
				{
					var row = document.getElementById("list_row_" + id);
					GroupsProfileBlock.hoverOn(row, id);
				}
				YAHOO.util.Event.addListener(listRow, "mouseover", onMouseOver, groupId);
			
				function onMouseOut(e,id)
				{
					var row = document.getElementById("list_row_" + id);
					GroupsProfileBlock.hoverOff(row, id);
				}
				YAHOO.util.Event.addListener(listRow, "mouseout", onMouseOut, groupId);
			}
		}
		
		function selectChangeHandler(e)
		{
			var element = YAHOO.util.Dom.getAncestorByClassName(e.target, "list_row");
			
			YAHOO.util.Dom.removeClass(element, "group_marked_for_delete");
			YAHOO.util.Dom.removeClass(element, "member_marked_for_delete");
			
			if (e.target.value == "remove_group")
			{
				YAHOO.util.Dom.addClass(element, "group_marked_for_delete");
			}
			else if (e.target.value == "remove_member")
			{
				YAHOO.util.Dom.addClass(element, "member_marked_for_delete");
			}
		}
		
		var adminActions = YAHOO.util.Dom.getElementsByClassName("admin_action", "select", editGroupList);
		for(var i=0; i < adminActions.length; i++)
		{
			 YAHOO.util.Event.addListener(adminActions[i], "change", selectChangeHandler);
		}
	},
	
	
	hoverOn: function(element, groupid)
	{	
		if (GroupsProfileBlock.selectedElement != element)
		{
			YAHOO.util.Dom.addClass(element, "list_row_hover");
		}
		
		var overlay = document.getElementById("row_edit_overlay_" + groupid);

		if (GroupsProfileBlock.showOperationsBar)
		{
			overlay.style.visibility = 'visible';
		}
	},
	
	
	hoverOff: function(element, groupid)
	{
		var overlay = YAHOO.util.Dom.getElementsByClassName("row_edit_overlay", "div", element)[0];
		if (overlay != null)
		{
			overlay.style.visibility = 'hidden';
		}
		
		YAHOO.util.Dom.removeClass(element, "list_row_hover");
	},
	
	
	edit: function(id, onSuccessFunction)
	{
		var element = document.getElementById("list_row_" + id);
		
		if (GroupsProfileBlock.selectedElement)
		{
			YAHOO.util.Dom.removeClass(GroupsProfileBlock.selectedElement, "list_row_selected");
		}
		
		YAHOO.util.Dom.removeClass(element, "list_row_hover");
		YAHOO.util.Dom.addClass(element, "list_row_selected");
		
		// Connect to our data source and load the data
		var conn = YAHOO.util.Connect.asyncRequest("GET", "/my/profile_blocks/Groups/edit_group/" + id, new ResponseHandler({
			success: function(o) {
				GroupsProfileBlock.selectedElement = element;
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
				
				// Stupid hack to fix alignment problems in IE6 for the autocomplete field
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
				GroupsProfileBlock.attachListeners();
				
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
					
					// Stupid hack to fix alignment problems in IE6 for the autocomplete field
					setTimeout('EditGroups.init()',10);
				}
			},
			failure: function(o) {
			},
			scope: this
		}), "ajax=true&form_key[]=" + formKey1 + "&form_key[]=" + formKey2);
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
		}), "ajax=true&form_key[]=" + formKey);
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

GroupsProfileBlock.EditLink = function(element) {
	this.element = element;
	YAHOO.util.Event.on(this.element, 'click', this.submit, this, true);
};

GroupsProfileBlock.EditLink.prototype = {
	submit: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}

		GroupsProfileBlock.operationStart();

		var listRow = YAHOO.util.Dom.getAncestorByClassName(this.element, 'list_row');
		
		if (GroupsProfileBlock.selectedElement)
		{
			YAHOO.util.Dom.removeClass(GroupsProfileBlock.selectedElement, "list_row_selected");
		}
		
		YAHOO.util.Dom.removeClass(listRow, "list_row_hover");
		YAHOO.util.Dom.addClass(listRow, "list_row_selected");
		
		// Connect to our data source and load the data
		var conn = YAHOO.util.Connect.asyncRequest("GET", this.element.href, new ResponseHandler({
			success: function(o) {
				GroupsProfileBlock.selectedElement = listRow;
				GroupsProfileBlock.loadJavaScript(o.responseText);
				EditGroups.init();
				GroupsProfileBlock.operationEnd();
			},
			failure: function(o) {
			},
			scope: this
		}), "ajax=true");
	}
};

Overlord.assign({
	minion: "groups:edit_link",
	load: function(element) {
		new GroupsProfileBlock.EditLink(element);
	},
	order: -1
	
});

GroupsProfileBlock.RemoveLink = function(element) {
	this.element = element;
	YAHOO.util.Event.on(this.element, 'click', this.submit, this, true);
};

GroupsProfileBlock.RemoveLink.prototype = {
	submit: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		
		var listRow = YAHOO.util.Dom.getAncestorByClassName(this.element, 'list_row');
		
		GroupsProfileBlock.operationStart();
		if (confirm("Delete group?")) 
		{
			var conn = YAHOO.util.Connect.asyncRequest("GET", this.element.href, 
				{
					success: function(o) {
						listRow.parentNode.removeChild(listRow);
						if(GroupsProfileBlock.selectedElement.id == listRow.id)
						{
							GroupsProfileBlock.createNew();
						}
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
	}
};

Overlord.assign({
	minion: "groups:remove_link",
	load: function(element) {
		new GroupsProfileBlock.RemoveLink(element);
	},
	order: -1	
});

Overlord.assign({
	minion: "groups:group_name_link",
	load: function(element) {
		new Truncator(element, { width: 142, fudgeFactor: 4 });
	},
	order: -1	
	
});

GlobalRegistry.register_handler("edit_groups", GroupsProfileBlock.init, GroupsProfileBlock, true);