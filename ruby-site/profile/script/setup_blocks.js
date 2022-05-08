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

		PROFILE.init_block_query_info_list();
		PROFILE.init_display_blocks();
		PROFILE.init_admin_values();

		for (var i = 0; i < YAHOO.profile.display_block_list.length; i++)
		{
			var displayBlock = YAHOO.profile.display_block_list[i];
			var block = document.getElementById(displayBlock.html_id);

			if (displayBlock.moveable())
			{
				YAHOO.profile.draggableBlocks.push(new YAHOO.profile.DraggableBlock(block, null, null, displayBlock));
			}
			
			if (displayBlock.editable() || displayBlock.removable() || (displayBlock.in_place_editable() && displayBlock.custom_edit_button() && displayBlock.custom_edit_button() != ""))
			{
				YAHOO.profile.editableBlocks.push(new YAHOO.profile.EditableBlock(block, displayBlock));
			}
			else
			{
				if(!displayBlock.in_place_editable())
				{
					YAHOO.profile.Blocks.coverWithGlassPane(block);
				}
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
		
		YAHOO.profile.Blocks.editOn();
		
		PROFILE.init_visibility();
		
		if (YAHOO.nexopia.Select.list && YAHOO.nexopia.Select.list["new_content_menu"])
		{
			this.initAddNewContentMenu();
		}
		
		YAHOO.profile.DraggableBlockMgr.resizeColumnBottomMarkers();
    },


	initAddNewContentMenu: function()
	{
		function beforeContentMenuShow()
		{
			for (blockInfoKey in YAHOO.profile.block_info_list)
			{
				var blockInfo = YAHOO.profile.block_info_list[blockInfoKey];
				YAHOO.nexopia.Select.list["new_content_menu"].setEnabled(blockInfoKey, blockInfo.can_make_more());
			}
		}
		YAHOO.nexopia.Select.list["new_content_menu"].subscribe("beforeShow", beforeContentMenuShow);
		
		
		YAHOO.profile.Blocks.createNewContentButton = new YAHOO.widget.Button(
			"create_new_content_button", 
			{ 
				type: "button", 
				name: "create_new_content_button",
				value: "Add New Block"
			});
		function onButtonClick(event, that) 
		{  
			var selected = YAHOO.nexopia.Select.list["new_content_menu"].getSelected();
		
			if (selected) 
			{
				this.createNewBlock(selected.value);
			}
			else
			{
				alert("You must select a block to add.");
			}
		}
		YAHOO.profile.Blocks.createNewContentButton.addListener("click", onButtonClick, null, this);
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
		var height = element.offsetHeight;
		var width = element.offsetWidth;

		Dom.setStyle(element, "position", element.style.position || "relative");

		var glassDiv = document.createElement("div");
		glassDiv.className = "glass_div";
			
		element.appendChild(glassDiv);
	
		YAHOO.util.Dom.setStyle(glassDiv, "height", height + "px");
		YAHOO.util.Dom.setStyle(glassDiv, "width", width + "px");
		YAHOO.util.Dom.setStyle(glassDiv, "z-index", "21");
		YAHOO.util.Dom.setStyle(glassDiv, "position", "absolute");
		YAHOO.util.Dom.setStyle(glassDiv, "top", "0px");
		YAHOO.util.Dom.setStyle(glassDiv, "left", "0px");		
		
		// iframe shim needed to work in IE
		if (YAHOO.env.ua.ie > 5 && YAHOO.env.ua.ie <= 7) 
		{		
			var glassIframe = document.createElement("iframe");
			element.appendChild(glassIframe);
			YAHOO.util.Dom.setStyle(glassIframe, "height", height + "px");
			YAHOO.util.Dom.setStyle(glassIframe, "width", width + "px");
			YAHOO.util.Dom.setStyle(glassIframe, "z-index", "20");
			YAHOO.util.Dom.setStyle(glassIframe, "position", "absolute");
			YAHOO.util.Dom.setStyle(glassIframe, "top", "0px");
			YAHOO.util.Dom.setStyle(glassIframe, "left", "0px");
			YAHOO.util.Dom.setStyle(glassIframe, "opacity", "0");
		}
	},
	
	
	disableAllButtons: function()
	{
		if (YAHOO.profile.Blocks.createNewContentButton)
		{
			YAHOO.profile.Blocks.createNewContentButton.set("disabled", true);
		}
		
		for (var i = 0; i < YAHOO.profile.editableBlocks.length; i++)
		{
			var editableBlock = YAHOO.profile.editableBlocks[i];
			editableBlock.disableButtons();
		}
	},
	
	
	enableAllButtons: function()
	{
		if (YAHOO.profile.Blocks.createNewContentButton)
		{
			YAHOO.profile.Blocks.createNewContentButton.set("disabled", false);
		}
		
		for (var i = 0; i < YAHOO.profile.editableBlocks.length; i++)
		{
			var editableBlock = YAHOO.profile.editableBlocks[i];
			editableBlock.enableButtons();
		}		
	},
	
	
	createNewBlock: function(blockHashID)
	{
		var blockInfo = YAHOO.profile.block_info_list[blockHashID];
		var displayBlock = blockInfo.create_display_block();
		var editPath = displayBlock.edit_path();

		var formKey1 = document.getElementById("profile_block_form_key").value;
		var formKey2 = document.getElementById("profile_form_key").value;
		
		if (!blockInfo.can_make_more())
		{
			alert("You cannot create any more than " + displayBlock.max_number() + " " + displayBlock.title() + " blocks.");
			return;
		}
		
		YAHOO.profile.Blocks.disableAllButtons();
		
		// Only use an EditDialog when it's needed. Otherwise use a blank object. This prevents problems
		//  with the EditDialog for noneditable blocks from capturing save events from other EditDialogs.
		if (displayBlock.editable())
		{
			var editPanel = new YAHOO.profile.EditDialog("edit_panel", displayBlock);
			editPanel.setBody("<div class='edit_block'><div class='edit_title'>" + 
				"<img src=\"" + Site.staticFilesURL + "/Legacy/images/spinner.gif\"/>" + "</div></div>");
			editPanel.render(document.body);
			editPanel.show();
			editPanel.disableButtons();
		}
		else
		{
			var editPanel = new Object();
		}
		
		var callback = {
			success : function(o) {
				editPanel.afterClose = function()
				{
					YAHOO.profile.Blocks.enableAllButtons();
				};
					
				editPanel.afterSave = function (blockId) {
					
					var max_position = 0;
					for(var i=0; i<YAHOO.profile.display_block_list.length; i++)
					{
						var temp = YAHOO.profile.display_block_list[i];
						if(temp.columnid == displayBlock.columnid && temp.position >= max_position)
						{
							max_position = temp.position + 1;
						}
					}
					
					displayBlock.position = max_position;
					blockId = parseInt(blockId, 10);
					displayBlock.update(blockId);
					var refreshPath = displayBlock.refresh_path();
				
					var newBlockDiv = document.createElement("div");
					newBlockDiv.id = displayBlock.html_id;
					YAHOO.util.Dom.addClass(newBlockDiv, "block_container");
					YAHOO.util.Dom.addClass(newBlockDiv, "primary_block");
					var newInternalDiv = document.createElement("div");
					newInternalDiv.id = "data_" + blockId;
					newBlockDiv.appendChild(newInternalDiv);
					
					var defaultColumn = displayBlock.columnid;
					var bottomElement = document.getElementById("column_bottom_marker_" + defaultColumn);
					bottomElement.parentNode.insertBefore(newBlockDiv, bottomElement);

					YAHOO.util.Connect.asyncRequest('POST', refreshPath , new ResponseHandler({
						success: function(o) {

							var block = document.getElementById(displayBlock.html_id);
							
							// This is a hacky way to get rid of a gallery block that shouldn't have been 
							// created in the first place.
							// The real solution is to make the edit block work so that it doesn't have
							// a save button to begin with.
							if ( o.responseText == "<div id=\"profile_error\">The requested block does not exist</div>")
							{
								newBlockDiv.parentNode.removeChild(newBlockDiv);	
							}
							else 
							{
								YAHOO.profile.draggableBlocks.push(new YAHOO.profile.DraggableBlock(block, null, null, displayBlock));
								YAHOO.profile.editableBlocks.push(new YAHOO.profile.EditableBlock(block, displayBlock));
								YAHOO.profile.display_block_list.push(displayBlock);
							
								YAHOO.nexopia.Select.list["new_content_menu"].setSelected(null);
							
								var xy = YAHOO.util.Dom.getXY(block);
								window.scrollTo(xy[0], xy[1]);
							}
							
						},
						failure: function(o) {
							editPanel.afterClose();
						},
						scope: this
					}), "ajax=true&form_key[]=" + formKey1 + "&form_key[]=" + formKey2);
				};
				
				
				if (displayBlock.editable())
				{
					editPanel.beforeOpen = displayBlock.javascript_init_function();
					editPanel.afterDone = editPanel.afterSave;
					
					editPanel.setBody(o.responseText);
					editPanel.render();
					editPanel.enableButtons();
				}
				else
				{
					YAHOO.util.Connect.asyncRequest('POST', displayBlock.save_path(), {
						success: function(o) {
							var response = o.responseText;
							editPanel.afterSave(parseInt(response, 10));
							editPanel.afterClose();
						},
						failure: function(o) {
							editPanel.afterClose();
						},
						scope: this
					}, "ajax=true&form_key[]=" + formKey1 + "&form_key[]=" + formKey2 + "&module_name=" + displayBlock.module_name() +
						"&path=" + displayBlock.path);					
				}
			},
			failure : function(o) {
				alert("Error accessing handler for: " + editPath);
				editPanel.afterClose();
			}
		};

		var conn = YAHOO.util.Connect.asyncRequest("POST", editPath, callback, "form_key[]=" + formKey1 + "&form_key[]=" + formKey2);
	}
};

Overlord.assign({
	minion: "profile_edit",
	load: function(element) {
		YAHOO.profile.Blocks.init();
	}
});