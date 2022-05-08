if(YAHOO.profile == undefined){
	YAHOO.namespace("profile");
}

YAHOO.profile.EditableBlock = function(element, profileDisplayBlock) 
{
	this.profileDisplayBlock = profileDisplayBlock;
	
	if((profileDisplayBlock.editable() && !profileDisplayBlock.in_place_editable()) || profileDisplayBlock.removable())
	{
		this.buttonBar = document.createElement("div");
		this.buttonBar.className = "button_bar";
		element.appendChild(this.buttonBar);
		
		var root_div = document.getElementById(profileDisplayBlock.html_id);
		var header_accessory_list = YAHOO.util.Dom.getElementsByClassName("header_accessory", "div", root_div, function(){});
		
		for(var i=0; i<header_accessory_list.length; i++)
		{
			YAHOO.util.Dom.setStyle(header_accessory_list[i], "display", "none");
		}	
	}
	
	if (profileDisplayBlock.editable() || profileDisplayBlock.in_place_editable())
	{
		if(!profileDisplayBlock.in_place_editable())
		{
			this.editButton = new YAHOO.widget.Button({
				id: "edit_button_" + element.id,
				type: "button",
				label: "edit",
				container: this.buttonBar
			});
		}
		else
		{
			this.editButton = new YAHOO.widget.Button(profileDisplayBlock.custom_edit_button(),
				{
					type: "button",
					name: profileDisplayBlock.custom_edit_button(),
					value: "nuts"
				});
		}

		function editClick(e, editableBlock) {
			var editPath = 	profileDisplayBlock.edit_path();

			YAHOO.profile.Blocks.disableAllButtons();
			var editPanel = new YAHOO.profile.EditDialog("edit_panel", editableBlock.profileDisplayBlock);
			editPanel.beforeOpen = editableBlock.profileDisplayBlock.javascript_init_function();
			editPanel.setBody("<div class='edit_block'><div class='edit_title'>" + 
				"<img src=\"" + Site.staticFilesURL + "/Legacy/images/spinner.gif\"/>" + "</div></div>");
			editPanel.render(document.body);
			editPanel.show();
			editPanel.disableButtons();
			
			var formKey1 = document.getElementById("profile_block_form_key").value;
			var formKey2 = document.getElementById("profile_form_key").value;
			
			var callback = {
				success : function(o) {
					editPanel.setBody(o.responseText);
					editPanel.render();
					
					// YUI isn't always properly setting the zIndex when it is a property on a Panel, so it has
					// to be set here after the render call.
					editPanel.cfg.setProperty("zIndex", 24);
					editPanel.enableButtons();
					
					YAHOO.profile.Blocks.enableAllButtons();
				},
				failure : function(o) {
					alert("Error accessing handler for: " + editPath);
					YAHOO.profile.Blocks.enableAllButtons();
				}
			}

			var conn = YAHOO.util.Connect.asyncRequest("POST", editPath, callback, "form_key[]=" + formKey1 + "&form_key[]=" + formKey2);
		}
		
		this.editButton.addListener("click", editClick, this);
	}
	
	if (profileDisplayBlock.removable())
	{
		this.removeButton = new YAHOO.widget.Button({
			id: "remove_button_" + element.id,
			type: "button",
			label: "remove",
			container: this.buttonBar
		});
		
		if(profileDisplayBlock.editable())
		{
			this.removeButton.addClass("yui-button-spacer");
		}
	
		function removeClick(e, editableBlock) {
			if (confirm("Remove block?")) 
			{
				var removePath = profileDisplayBlock.remove_path();
		
				var formKey1 = document.getElementById("profile_block_form_key").value;
				var formKey2 = document.getElementById("profile_form_key").value;
				
				YAHOO.profile.Blocks.disableAllButtons();
				
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
						YAHOO.profile.Blocks.enableAllButtons();
					},
					failure : function(o) {
						alert("Error accessing handler for: " + removePath);
						YAHOO.profile.Blocks.enableAllButtons();
					}
				}

				var conn = YAHOO.util.Connect.asyncRequest("POST", removePath, callback, "form_key[]=" + formKey1 + "&form_key[]=" + formKey2);
			}
		}

		this.removeButton.addListener("click", removeClick, this);
	}
	
	if(this.buttonBar)
	{
		Dom.setStyle(element, "position", element.style.position || "relative");
	
		Dom.setStyle(this.buttonBar, "right", "6px");
		Dom.setStyle(this.buttonBar, "top", "6px");
		Dom.setStyle(this.buttonBar, "position", "absolute");
		Dom.setStyle(this.buttonBar, "z-index", "22");
	
		// Fix a stupid IE6 rendering bug
		if (element.offsetWidth - (this.buttonBar.offsetLeft + this.buttonBar.offsetWidth) < 6)
		{
			Dom.setStyle(this.buttonBar, "right", "12px");
		}

	/*
		// Stupid YUI version that would do all the ifram shim stuff IF IT WORKED!!!
		// This is the path to madness!!! I leave it here only as a warning to all who
		// may venture near. You may uncomment these lines and say, "I see nothing wrong!"
		// But just wait! Drag an object and then find that you can't drag it again until
		// you drag another object over it. Or see the edit and remove buttons suddenly
		// become inaccessible for no reason after a drag and drop, even though the z-index
		// properties suggest that there is nothing wrong. Try positioning without using
		// the "context" property and find that YUI replaces your positions with its own
		// strange calculations. Could it work some day (i.e. after a new version of YUI)? 
		// Is there some fundamental switch that would fix it all that I'm just missing? 
		// Maybe. But make sure to have a psychiatrist on speed dial before attempting
		// any changes.
	
		var glassDiv = new YAHOO.widget.Overlay("glass_div_" + element.id, { 
			context: [element.id, "tl", "tl"],
			width: element.offsetWidth + "px", 
			height: element.offsetHeight + "px",
			zIndex: 21,
			iframe: true }); 
		glassDiv.render(element);
		Dom.setStyle(glassDiv.element, "top", "0px");
		Dom.setStyle(glassDiv.element, "left", "0px");
		Dom.setStyle(glassDiv.element, "position", "absolute");
	*/

		var height = element.offsetHeight;
		var width = element.offsetWidth;

		var glassDiv = document.createElement("div");
		glassDiv.className = "glass_div";

		element.appendChild(glassDiv);

		Dom.setStyle(glassDiv, "height", height + "px");
		Dom.setStyle(glassDiv, "width", width + "px");
		Dom.setStyle(glassDiv, "z-index", "21"); 
		Dom.setStyle(glassDiv, "position", "absolute");
		Dom.setStyle(glassDiv, "top", "0px");
		Dom.setStyle(glassDiv, "left", "0px");
		if (profileDisplayBlock.moveable())
		{
			Dom.setStyle(glassDiv, "cursor", "move");
		}	

		// iframe shim needed to work in IE
		if (YAHOO.env.ua.ie > 5 && YAHOO.env.ua.ie <= 7) 
		{
			var glassIframe = document.createElement("iframe");
			glassIframe.className = "glass_iframe";
		
			element.appendChild(glassIframe);
		
			Dom.setStyle(glassIframe, "height", height + "px");
			Dom.setStyle(glassIframe, "width", width + "px");
			Dom.setStyle(glassIframe, "z-index", "20");
			Dom.setStyle(glassIframe, "position", "absolute");
			Dom.setStyle(glassIframe, "top", "0px");
			Dom.setStyle(glassIframe, "left", "0px");
			Dom.setStyle(glassIframe, "opacity", "0");
		}
	}
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
	},
	
	
	enableButtons: function()
	{
		if (this.editButton)
		{
			this.editButton.set("disabled", false);
		}
		
		if (this.removeButton)
		{
			this.removeButton.set("disabled", false);
		}
	},
	
		
	disableButtons: function()
	{
		if (this.editButton)
		{
			this.editButton.set("disabled", true);
		}
		
		if (this.removeButton)
		{
			this.removeButton.set("disabled", true);
		}
	}
};