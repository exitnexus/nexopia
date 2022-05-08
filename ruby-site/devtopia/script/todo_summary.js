TodoSummary = {		
	counter: null,
	
	init: function()
	{
		counter = 0;
	},
	
	
	addChild: function(parentID)
	{
		parentIDArray = parentID.split("_");
		parentElement = document.getElementById("ul_" + parentID);
		
		li = document.createElement("li");
		
		nameInput = document.createElement("input");
		nameInput.type = "text";
		nameInput.id = "new_todo_name[" + parentID + "_" + counter + "]";
		nameInput.name = "new_todo_name[" + parentID + "_" + counter + "]";
		
		stateInput = document.createElement("select");
		stateInput.id = "new_todo_state[" + parentID + "_" + counter + "]";
		stateInput.name = "new_todo_state[" + parentID + "_" + counter + "]";
		stateInput.innerHTML = 
			"<option value='0'>New</option>" +			
			"<option value='1'>In Progress</option>" +
			"<option value='2'>Done</option>" +
			"<option value='3'>Cancelled</option>";
		
		ttcInput = document.createElement("input");
		ttcInput.type = "text";
		ttcInput.id = "new_todo_ttc[" + parentID + "_" + counter + "]";
		ttcInput.name = "new_todo_ttc[" + parentID + "_" + counter + "]";
		
		li.appendChild(document.createTextNode("Name: "));
		li.appendChild(nameInput);
		li.appendChild(document.createTextNode(" | State: "));
		li.appendChild(stateInput);
		li.appendChild(document.createTextNode(" | TTC: "));
		li.appendChild(ttcInput);
		
		parentElement.appendChild(li);
		
		counter = counter + 1;
	},
	
	
	markTodoDone: function(id)
	{
		if (!confirm("Mark Todo completed?")) return;
		
		var ids = id.split("_");
		
		formKey = SecureForm.getFormKey();
		YAHOO.util.Connect.asyncRequest('POST', '/my/projectmanager/todos/done/' + ids[0] + '/' + ids[1] + '/' + ids[2], new ResponseHandler({
			success: function(o) {
				
			},
			failure: function(o) {},
			scope: this
		}), "ajax=true&form_key=" + formKey);
	},
	
	
	markTaskDone: function(id)
	{
		if (!confirm("Mark Task completed?")) return;
	
		var ids = id.split("_");
		
		formKey = SecureForm.getFormKey();
		YAHOO.util.Connect.asyncRequest('POST', '/my/projectmanager/tasks/done/' + ids[0] + '/' + ids[1], new ResponseHandler({
			success: function(o) {
				
			},
			failure: function(o) {},
			scope: this
		}), "ajax=true&form_key=" + formKey);
	}
};

GlobalRegistry.register_handler("todo_summary", TodoSummary.init, TodoSummary, true);