ProjectManager = {

	lastSelectedID: null,
	lastSelectedClass: null,
	buttonOriginalColor: null,
	doingOperation: false,
	myDialog: null,
	recordType: null,
	
	init: function()
	{
		var buttons = YAHOO.util.Dom.getElementsByClassName("button", "div");
		for (var i = 0; i < 1; i++)
		{
			buttonOriginalColor = buttons[i].style.backgroundColor;
		}
		
		var lastSelectedElements = YAHOO.util.Dom.getElementsByClassName("row_selected", "div", null);
		if (lastSelectedElements != null && lastSelectedElements.length != 0)
		{
			lastSelectedID = lastSelectedElements[0].id.replace(/record_/,"");
		}
		else
		{
			lastSelectedID = null;
		}
		
		mainRecordClass = document.getElementById("main_record_class");
		if (mainRecordClass != null)
		{
			recordType = mainRecordClass.innerHTML;
		}		
		
		lastSelectedClass = null;
		buttonOriginalColor = null;
		doingOperation = false;
	},


	toggleDisplay: function(element) 
	{
		if (element.style.display == "none") {
			element.style.display = "";
		} else {
			element.style.display = "none";
		}
	},


	toggleProjectDetails: function(project_id)
	{
		element_id = "projectDetails[" + project_id + "]";
		element = document.getElementById(element_id);
		ProjectManager.toggleDisplay(element);
	},
	
	
	select: function(recordID, recordType)
	{
		if (lastSelectedID != null)
		{
			var lastSelectedElement = document.getElementById("record_" + lastSelectedID);
			lastSelectedElement.className = lastSelectedClass;
		}
		
		element = document.getElementById("record_" + recordID);

		lastSelectedID = recordID;
		
		if (element != null)
		{
			lastSelectedClass = element.className;		

			element.className = "row_selected";
		
			element.style.display = "none";
			element.style.display = "block";
		}
		
		ProjectManager.updateDetails(recordID, recordType);
	},
	
	
	updateDetails: function(recordID, recordType, divID)
	{
		var specificDivRefresh = "";
		if (divID != null)
		{
			specificDivRefresh = "&div_id=" + divID;
		}
		
		ProjectManager.disableButtons();
		formKey = SecureForm.getFormKey();
		YAHOO.util.Connect.asyncRequest('POST', '/my/projectmanager/record_details/' + recordID + '/' + escape(recordType), new ResponseHandler({
			success: function(o) {
				ProjectManager.enableButtons();
			},
			failure: function(o) {
				
			},
			scope: this
		}), "ajax=true&form_key=" + formKey + specificDivRefresh);
	},
	
	
	save: function(recordID, recordType)
	{	
		if (doingOperation)
		{
			return;
		}
		
		var editForm = document.getElementById("dialog_edit_form");
		if (editForm == null)
		{
			editForm = document.getElementById(ProjectManager.shortType(recordType) + "_edit_form");
		}
		YAHOO.util.Connect.setForm(editForm);

		ProjectManager.disableButtons();
		
		YAHOO.util.Connect.asyncRequest('POST', '/my/projectmanager/record_save/' + recordID + '/' + escape(recordType), new ResponseHandler({
			success: function(o) {
				ProjectManager.enableButtons();
			},
			failure: function(o) {

			},
			scope: this
		}), "ajax=true");
	},


	revert: function(recordID, recordType)
	{
		if (doingOperation)
		{
			return;
		}
		
		ProjectManager.updateDetails(recordID, recordType);
	},
	
	
	disableButtons: function()
	{
		var buttons = YAHOO.util.Dom.getElementsByClassName("button", "div");
		for (var i = 0; i < buttons.length; i++)
		{
			buttons[i].style.backgroundColor = "grey";
		}
		
		doingOperation = true;
	},
	
	
	enableButtons: function()
	{
		var buttons = YAHOO.util.Dom.getElementsByClassName("button", "div");
		for (var i = 0; i < buttons.length; i++)
		{
			buttons[i].style.backgroundColor = buttonOriginalColor;
		}
		
		doingOperation = false;
	},
	
	
	getRecord: function(recordID, recordType)
	{
		callback = {
			success: function(o) {
				xmlRoot = o.responseXML.documentElement;
				fieldTags = xmlRoot.getElementsByTagName("field");
				for (var i = 0; i < fieldTags.length; i++)
				{
					nameTag = fieldTags[i].getElementsByTagName("name")[0];
					valueTag = fieldTags[i].getElementsByTagName("value")[0];
					name = nameTag.firstChild == null ? "" : nameTag.firstChild.nodeValue;
					value = valueTag.firstChild == null ? "" : valueTag.firstChild.nodeValue;
					
					document.getElementsByName(name)[0].value = value;
				}
			}
		};
		
		YAHOO.util.Connect.asyncRequest('POST', '/my/projectmanager/record_details/' + recordID + '/' + escape(recordType), callback, "&_=");
	},
	
	
	refreshSummaryPanel: function(recordType)
	{
		var refreshURIDiv = YAHOO.util.Dom.getElementsByClassName("refresh_uri", "div", null)[0];
		var uri = refreshURIDiv.innerHTML;
		var uriParams = uri.replace(/.*\?(.*)/, "$1");
		formKey = SecureForm.getFormKey();
		YAHOO.util.Connect.asyncRequest('POST', '/my/projectmanager/summary/refresh', new ResponseHandler({
			success: function(o) {
				// Reselect to make sure details are refreshed
				ProjectManager.updateDetails(lastSelectedID, recordType);
			},
			failure: function(o) {},
			scope: this
		}), "ajax=true&form_key=" + formKey + "&uri=" + escape(uri) + "&" + uriParams);
	},
	
	
	add: function(recordParent, recordParentType, recordType, summary)
	{
		if (doingOperation)
		{
			return;
		}
		
		ProjectManager.disableButtons();
		
		summary == null ? summary = false : summary = summary;
		
		var dialog_container_div = document.createElement("div");
		dialog_container_div.id = "dialog_container_div";
		var dialog_div = document.createElement("div");
		dialog_div.id = "dialog_div";
		var dialog_hd_div = document.createElement("div");
		dialog_hd_div.className = "hd";
		dialog_div.appendChild(dialog_hd_div);
		var dialog_bd_div = document.createElement("div");
		dialog_bd_div.className = "bd";
		dialog_div.appendChild(dialog_bd_div);
		dialog_container_div.appendChild(dialog_div);
		
		var main_container = YAHOO.util.Dom.getElementsByClassName("devtaskman_content_container", "div", null)[0];	
		main_container.appendChild(dialog_container_div);
		
		callback = {
			success: function(o) {
				text = o.responseText;
				dialog_hd_div.innerHTML = recordType;
				dialog_bd_div.innerHTML = text;
				
				form_element = YAHOO.util.Dom.getElementsByClassName("edit_form", "form", dialog_bd_div)[0];
				form_element.id = "dialog_edit_form";
				
				var related_editors = YAHOO.util.Dom.getElementsByClassName("related_objects_div", "div", dialog_bd_div);
				for (var i = 0; i < related_editors.length; i++)
				{
					related_editors[i].style.display = "none";
				}
				
				var buttons = YAHOO.util.Dom.getElementsByClassName("button", "div", dialog_bd_div);
				for (var i = 0; i < buttons.length; i++)
				{
					buttons[i].style.display = "none";
				}
				
				if (recordParent != 0)
				{
					parent_field = document.createElement("input");
					parent_field.setAttribute('name', 'parent_id');
					parent_field.setAttribute('type', 'hidden');
					parent_field.setAttribute('value', recordParent);
					form_element.appendChild(parent_field);
				}
				
				//alert(form_element);
				
				//alert(text);
				var dialog = new YAHOO.widget.Dialog("dialog_div");
				var handleCancel = function() {
					main_container.removeChild(dialog_container_div);
					ProjectManager.enableButtons();
				}
				var handleSubmit = function() {
					var recordToSave = recordParent;
					if (recordToSave != "0")
					{
						recordToSave = recordToSave + "-0";
					}
					
					// Need to enable before saving so that save does not immediately return.
					doingOperation = false;
					
					// Save the record
					ProjectManager.save(recordToSave, recordType);
					
					main_container.removeChild(dialog_container_div);
					
					if (summary)
					{	
						ProjectManager.refreshSummaryPanel(recordType);				
					}
					else
					{
						if (recordParentType != 'NilClass')
						{
							ProjectManager.updateDetails(recordParent, recordParentType, 'related_objects_div');
						}
					}
					
				}
				var myButtons = [ { text:"Submit", handler:handleSubmit, isDefault:true },
								  { text:"Cancel", handler:handleCancel } ];
				dialog.cfg.queueProperty("buttons", myButtons);

				dialog.render();
				dialog.show();
			}
		};
		formKey = SecureForm.getFormKey();
		YAHOO.util.Connect.asyncRequest('POST', '/my/projectmanager/record_details/'+recordParent+'-0/' + escape(recordType), callback, "form_key=" + formKey); 
	},
	
	
	shortType: function(type)
	{
		var withoutPrefix = type.replace(/.*::(.*)/,"$1");
		
		return withoutPrefix.toLowerCase();
	},
	
	
	filter: function(programmerID)
	{
		callback = {
			success: function(o) {
				ProjectManager.refreshSummaryPanel(recordType);
				//ProjectManager.select(lastSelectedID, recordType);
			}
		};
		formKey = SecureForm.getFormKey();
		YAHOO.util.Connect.asyncRequest('POST', '/my/projectmanager/programmer_filter/' + programmerID, callback, "form_key=" + formKey); 
	}
	
};

GlobalRegistry.register_handler("list_product_tasks", ProjectManager.init, ProjectManager, true);
GlobalRegistry.register_handler("list_products", ProjectManager.init, ProjectManager, true);
GlobalRegistry.register_handler("list_projects", ProjectManager.init, ProjectManager, true);
GlobalRegistry.register_handler("list_sprint_tasks", ProjectManager.init, ProjectManager, true);
GlobalRegistry.register_handler("list_sprints", ProjectManager.init, ProjectManager, true);
GlobalRegistry.register_handler("list_todos", ProjectManager.init, ProjectManager, true);