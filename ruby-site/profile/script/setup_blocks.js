if(YAHOO.profile == undefined){
	YAHOO.namespace ("profile");
}

/*
	Setup drag and drop for profile blocks.
*/
YAHOO.profile.Blocks = {
	init: function() {
		YAHOO.profile.draggableBlocks = [];
		YAHOO.profile.editableBlocks = [];
		
		YAHOO.profile.block_info_list = [];
		YAHOO.profile.display_block_list = [];

		PROFILE.init_display_blocks();
		PROFILE.init_block_query_info_list();

		for (var i = 0; i < YAHOO.profile.display_block_list.length; i++)
		{
			var displayBlock = YAHOO.profile.display_block_list[i];
			var block = document.getElementById(displayBlock.html_id);

			if (displayBlock.moveable())
			{
				YAHOO.profile.draggableBlocks.push(new YAHOO.profile.DraggableBlock(block, null, null, displayBlock));
			}
			
			if (displayBlock.editable() || displayBlock.removable())
			{
				YAHOO.profile.editableBlocks.push(new YAHOO.profile.EditableBlock(block, displayBlock));
			}
			else
			{
				YAHOO.profile.Blocks.coverWithGlassPane(block);
			}
		}

		// Set up column bottom markers. This ensures that if there are no draggable blocks in a column,
		// a draggable block can still be dragged into the column (only "draggable" blocks fire the needed
		// events indicating that there is a block being dragged over)
		blocks = Dom.getElementsByClassName("column_bottom_marker", "div", null);
		for (var i = 0; i < blocks.length; i++)
		{
			var block = blocks[i];
			
			// The id here doesn't exist, but it keeps the marker block itself from appearing draggable to the user. 
			YAHOO.profile.draggableBlocks[i] = new YAHOO.profile.DraggableBlock(block, "no_drag_column_bottom_handle_" + i, true);
		}
		
		YAHOO.profile.Blocks.loadModuleJS("groups");

		YAHOO.profile.Blocks.editOn();
		
		PROFILE.init_visibility();
		
    },


	loadModuleJS: function(moduleName)
	{
		var loader = new YAHOO.util.YUILoader();
		//Add the module to YUILoader
		loader.addModule({
			name: "nexopia_groups", //module name; must be unique
			type: "js", //can be "js" or "css"
		    fullpath: "http://www.david/static/4965/script/groups.js", //can use a path instead, extending base path
		    varName: "JSON" // a variable that will be available when the script is loaded. Needed
		                    // in order to act on the script immediately in Safari 2.x and below.
			//requires: ['yahoo', 'event'] //if this module had dependencies, we could define here
		});
		
		loader.require("nexopia_groups");
		loader.insert();
	},


	editOn: function()
	{
		this.editing = true;
		
		for (var i = 0; i < YAHOO.profile.editableBlocks.length; i++)
		{
			var block = YAHOO.profile.editableBlocks[i];
			block.setVisible(true);
		}
	},
	
	
	editOff: function()
	{
		this.editing = false;
		
		for (var i = 0; i < YAHOO.profile.editableBlocks.length; i++)
		{
			var block = YAHOO.profile.editableBlocks[i];
			block.setVisible(false);
		}
	},
	
	
	toggleEdit: function()
	{
		if (this.editing)
		{
			this.editOff();
		}
		else
		{
			this.editOn();
		}
	},
	
	
	coverWithGlassPane: function(element)
	{
		Dom.setStyle(element, "position", element.style.position || "relative");
		
		var glassDiv = document.createElement("div");
		element.appendChild(glassDiv);

		YAHOO.util.Dom.setStyle(glassDiv, "height", element.offsetHeight);
		YAHOO.util.Dom.setStyle(glassDiv, "width", element.offsetWidth);
		YAHOO.util.Dom.setStyle(glassDiv, "z-index", "21");
		YAHOO.util.Dom.setStyle(glassDiv, "position", "absolute");
		YAHOO.util.Dom.setStyle(glassDiv, "top", "0");
		YAHOO.util.Dom.setStyle(glassDiv, "left", "0");
	},
	
	
	canMakeMoreOf: function(displayBlock)
	{
		// Count the existing blocks
		var count = 0;
		for (var i=0; i < YAHOO.profile.display_block_list.length; i++)
		{
			var existingBlock = YAHOO.profile.display_block_list[i];
			if (existingBlock.moduleid === displayBlock.moduleid &&
				existingBlock.path === displayBlock.path)
			{
				count++;
			}

		}
		
		return count >= displayBlock.max_number();
	},
	
	
	createNewBlock: function()
	{
		var selector = document.getElementById("new_block_selector");
		var blockHashID = selector.options[selector.selectedIndex].value;

		var blockInfo = YAHOO.profile.block_info_list[blockHashID];
		var displayBlock = blockInfo.create_display_block();
		var editPath = displayBlock.edit_path();
		
		if (this.canMakeMoreOf(displayBlock))
		{
			alert("You cannot create any more than " + displayBlock.max_number() + " " + displayBlock.title() + " blocks.")
			return;
		}
		
		var callback = {
			success : function(o) {
				
				var editPanel = new YAHOO.profile.EditDialog("edit_panel", displayBlock);
				
				editPanel.afterSave = function (blockId) {

					blockId = parseInt(blockId);
					displayBlock.update(blockId);
					var refreshPath = displayBlock.refresh_path();
				
					var newBlockDiv = document.createElement("div");
					newBlockDiv.id = displayBlock.html_id;
					YAHOO.util.Dom.addClass(newBlockDiv, "block_container");
					var newInternalDiv = document.createElement("div");
					newInternalDiv.id = "data_" + blockId;
					newBlockDiv.appendChild(newInternalDiv);
					
					var defaultColumn = displayBlock.columnid;
					var bottomElement = document.getElementById("column_bottom_marker_" + defaultColumn);
					bottomElement.parentNode.insertBefore(newBlockDiv, bottomElement);

					YAHOO.util.Connect.asyncRequest('GET', refreshPath , new ResponseHandler({
						success: function(o) {
							var block = document.getElementById(displayBlock.html_id);

							YAHOO.profile.draggableBlocks.push(new YAHOO.profile.DraggableBlock(block, null, null, displayBlock));
							YAHOO.profile.editableBlocks.push(new YAHOO.profile.EditableBlock(block, displayBlock));
							YAHOO.profile.display_block_list.push(displayBlock);
						},
						failure: function(o) {
						},
						scope: this
					}), "ajax=true");
				};
				
				editPanel.afterDone = editPanel.afterSave;
				
				editPanel.setBody(o.responseText);
				editPanel.render(document.body);

				if (displayBlock.editable())
				{
					editPanel.show();
				
					// After the panel is shown and centered, keep it from repositioning as the user scrolls 
					// down the page.
					editPanel.cfg.setProperty("fixedcenter", false);
				}
				else
				{
					var formKey1 = document.getElementById("profile_block_form_key").value;
					var formKey2 = document.getElementById("profile_form_key").value;

					YAHOO.util.Connect.asyncRequest('POST', displayBlock.save_path(), {
						success: function(o) {
							var response = o.responseText;
							editPanel.afterSave(parseInt(response));
						},
						failure: function(o) {
						},
						scope: this
					}, "ajax=true&form_key=" + formKey1 + "&form_key=" + formKey2 + "&module_name=" + displayBlock.module_name() +
						"&path=" + displayBlock.path);					
				}
			},
			failure : function(o) {
				alert("Error accessing handler for: " + editPath);
			}
		}

		var conn = YAHOO.util.Connect.asyncRequest("GET", editPath, callback);
	}
};

GlobalRegistry.register_handler("profile_edit", YAHOO.profile.Blocks.init, YAHOO.profile.Blocks, true);