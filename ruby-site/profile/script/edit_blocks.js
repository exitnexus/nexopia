if(YAHOO.profile == undefined){
	YAHOO.namespace("profile");
}

YAHOO.profile.EditableBlock = function(element, profileDisplayBlock) 
{
	this.profileDisplayBlock = profileDisplayBlock;
	
	this.buttonBar = document.createElement("div");
	this.buttonBar.className = "button_bar";
	element.appendChild(this.buttonBar);
	
	if (profileDisplayBlock.editable())
	{
		var editButton = new YAHOO.widget.Button({
			id: "edit_button_" + element.id,
			type: "button",
			label: "edit",
			container: this.buttonBar
		});
	
		function editClick(e, editableBlock) {
			var editPath = 	profileDisplayBlock.edit_path();
		
			var callback = {
				success : function(o) {
					var editPanel = new YAHOO.profile.EditDialog("edit_panel", editableBlock.profileDisplayBlock);
					editPanel.beforeOpen = editableBlock.profileDisplayBlock.javascript_init_function();
					
					editPanel.setBody(o.responseText);
					editPanel.render(document.body);
					editPanel.show();

					// After the panel is shown and centered, keep it from repositioning as the user scrolls 
					// down the page.
					editPanel.cfg.setProperty("fixedcenter", false);
				},
				failure : function(o) {
					alert("Error accessing handler for: " + editPath);
				}
			}

			var conn = YAHOO.util.Connect.asyncRequest("GET", editPath, callback);
		}
		editButton.addListener("click", editClick, this);
	}
	
	if (profileDisplayBlock.removable())
	{
		var removeButton = new YAHOO.widget.Button({
			id: "remove_button_" + element.id,
			type: "button",
			label: "remove",
			container: this.buttonBar
		});
	
		function removeClick(e, editableBlock) {
			if (confirm("Remove block?")) 
			{
				var removePath = profileDisplayBlock.remove_path();
		
				var formKey1 = document.getElementById("profile_block_form_key").value;
				var formKey2 = document.getElementById("profile_form_key").value;
		
				var callback = {
					success : function(o) {
						var elementToRemove = document.getElementById(profileDisplayBlock.html_id);
						elementToRemove.parentNode.removeChild(elementToRemove);
						for (var i = 0; i < YAHOO.profile.display_block_list.length; i++)
						{
							if (profileDisplayBlock === YAHOO.profile.display_block_list[i])
							{
								YAHOO.profile.display_block_list.splice(i,1);
								break;
							}
						}
					},
					failure : function(o) {
						alert("Error accessing handler for: " + removePath);
					}
				}

				var conn = YAHOO.util.Connect.asyncRequest("POST", removePath, callback, "form_key=" + formKey1 + "&form_key=" + formKey2);
			}
		}

		removeButton.addListener("click", removeClick, this);
	}
	
	Dom.setStyle(this.buttonBar, "right", "6px");
	Dom.setStyle(this.buttonBar, "top", "6px");
	Dom.setStyle(this.buttonBar, "position", "absolute");
	Dom.setStyle(this.buttonBar, "z-index", "22");
	
	Dom.setStyle(element, "position", element.style.position || "relative");

	var glassDiv = document.createElement("div");
	element.appendChild(glassDiv);
	
	Dom.setStyle(glassDiv, "height", element.offsetHeight + "px");
	Dom.setStyle(glassDiv, "width", element.offsetWidth + "px");
	Dom.setStyle(glassDiv, "z-index", "21"); 
	Dom.setStyle(glassDiv, "position", "absolute");
	Dom.setStyle(glassDiv, "top", "0px");
	Dom.setStyle(glassDiv, "left", "0px");
	Dom.setStyle(glassDiv, "cursor", "move");
};


YAHOO.profile.EditableBlock.prototype = 
{
	setVisible: function(visible)
	{
		var display = "none";
		if (visible)
		{
			display = "block";
		}
		
		Dom.setStyle(this.buttonBar, "display", display);
		
		this.visible = visible;
	},
	
	
	toggleVisibility: function()
	{
		this.setVisible(!this.visible);
	}
};