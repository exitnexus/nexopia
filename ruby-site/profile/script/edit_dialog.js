if(YAHOO.profile == undefined){
	YAHOO.namespace("profile");
}

YAHOO.profile.EditDialog = function(el, profileDisplayBlock) {
    YAHOO.profile.EditDialog.superclass.constructor.call(this, el, 
	{
		width:"560px",
		fixedcenter:true,
		close:false,
		draggable:false,
		zindex:24,
		modal:true,
		visible:false,
		effect:{effect:YAHOO.widget.ContainerEffect.FADE,duration:0.50},
		profileDisplayBlock: profileDisplayBlock
	});
};


YAHOO.extend(YAHOO.profile.EditDialog, YAHOO.widget.Panel, {
	init: function(el, config)
	{
		YAHOO.profile.EditDialog.superclass.init.call(this, el, config);
		
		this.profileDisplayBlock = config["profileDisplayBlock"];
		
		this.dialogButtonBar = document.createElement("div");
		this.dialogButtonBar.className = "button_bar";

		this.innerElement.appendChild(this.dialogButtonBar);

		var dialog = this;
		var refreshPath = this.profileDisplayBlock.refresh_path();
		var savePath = 	this.profileDisplayBlock.save_path();

		this.visibilitySelector = document.createElement("select");
		this.visibilitySelector.name = "block_visibility";

		for (var i = 0; i < YAHOO.profile.visibility_options.length; i++)
		{
			var option = document.createElement('option');
			option.text = YAHOO.profile.visibility_options[i][0];
			option.value = YAHOO.profile.visibility_options[i][1];
			try
			{
				this.visibilitySelector.add(option, null); // standards compliant
			}
			catch(ex)
			{
				this.visibilitySelector.add(option); // IE only
			}
			
		}

		for(var i = 0; i < YAHOO.profile.visibility_options.length; i++)
		{
			if (YAHOO.profile.visibility_options[i][1] == this.profileDisplayBlock.visibility)
			{
				this.visibilitySelector.selectedIndex = i;
				break;				
			}
		}

		this.dialogButtonBar.appendChild(this.visibilitySelector);
		
		function save(onSuccess)
		{
			dialog.disableButtons();
			
			var formKey1 = document.getElementById("profile_block_form_key").value;
			var formKey2 = document.getElementById("profile_form_key").value;
			var visibility = dialog.visibilitySelector.options[dialog.visibilitySelector.selectedIndex].value;
			
			var editForm = document.getElementById("block_edit_form");
			YAHOO.util.Connect.setForm(editForm);
			YAHOO.util.Connect.asyncRequest('POST', savePath, {
				success: function(o) {
					onSuccess(o.responseText);
				},
				failure: function(o) {
					dialog.enableButtons();
					
					dialog.destroy();
				},
				scope: this
			}, "ajax=true&form_key=" + formKey1 + "&form_key=" + formKey2 + "&module_name=" + dialog.profileDisplayBlock.module_name() +
				"&path=" + dialog.profileDisplayBlock.path + "&visibility=" + dialog.visibilitySelector.options[dialog.visibilitySelector.selectedIndex].value);
				
			dialog.profileDisplayBlock.visibility = visibility;
		}
		
		function refresh()
		{
			dialog.disableButtons();
			
			YAHOO.util.Connect.asyncRequest('GET', refreshPath , new ResponseHandler({
				success: function(o) {
					dialog.enableButtons();
					
					dialog.destroy();
				},
				failure: function(o) {
					dialog.enableButtons();
					
					dialog.destroy();
				},
				scope: this
			}), "ajax=true");
		}
		
		if (!this.profileDisplayBlock.explicit_save())
		{
			// Done Button
			this.doneButton = new YAHOO.widget.Button({
				id: "done_button_" + this.id,
				type: "button",
				label: "done",
				container: this.dialogButtonBar
			});
							
			function doneClick(e)
			{
				function done(response)
				{
					if (!dialog.afterDone)
					{
						refresh();
					}
					else
					{
						dialog.afterDone(response);

						dialog.enableButtons();

						dialog.destroy();
					}
				}
				
				
				if (dialog.profileDisplayBlock.new_block())
				{
					save(done);
				}
				else
				{
					var formKey1 = document.getElementById("profile_block_form_key").value;
					var formKey2 = document.getElementById("profile_form_key").value;
					var visibility = dialog.visibilitySelector.options[dialog.visibilitySelector.selectedIndex].value;
				
					YAHOO.util.Connect.asyncRequest('POST', savePath, {
						success: function(o) {},
						failure: function(o) {},
						scope: this
					}, "ajax=true&form_key=" + formKey1 + "&form_key=" + formKey2 + "&module_name=" + dialog.profileDisplayBlock.module_name() +
						"&path=" + dialog.profileDisplayBlock.path + "&visibility=" + visibility);
					
					dialog.profileDisplayBlock.visibility = visibility;
					
					done();
				}
			}

			this.doneButton.addListener("click", doneClick);
		}
		else
		{
			// Cancel Button
			this.cancelButton = new YAHOO.widget.Button({
				id: "cancel_button_" + this.id,
				type: "button",
				label: "cancel",
				container: this.dialogButtonBar
			});

			function cancelClick(e)
			{
				dialog.destroy();
			}

			this.cancelButton.addListener("click", cancelClick);
			
			// Save Button
			this.saveButton = new YAHOO.widget.Button({
				id: "save_button_" + this.id,
				type: "button",
				label: "save",
				container: this.dialogButtonBar
			});

			function saveClick(e)
			{
				function onSuccess(response)
				{
					if (!dialog.afterSave)
					{
						refresh();
					}
					else
					{
						dialog.afterSave(response);
						
						dialog.enableButtons();

						dialog.destroy();
					}
				}
				
				save(onSuccess);				
			}

			this.saveButton.addListener("click", saveClick);
		}

		Dom.setStyle(this.dialogButtonBar, "right", "12px");
		Dom.setStyle(this.dialogButtonBar, "top", "12px");
		Dom.setStyle(this.dialogButtonBar, "position", "absolute");
		Dom.setStyle(this.dialogButtonBar, "z-index", "25"); 

		function openHandler(type, args, dialog)
		{
			if (dialog.beforeOpen)
			{
				dialog.beforeOpen(dialog);
			}
		}
		this.beforeShowEvent.subscribe(openHandler, this);	
	},
	
	
	disableButtons: function()
	{
		if (this.doneButton)
		{
			this.doneButton.set("disabled", true);
		}
		
		if (this.saveButton)
		{
			this.saveButton.set("disabled", true);
		}
		
		if (this.cancelButton)
		{
			this.cancelButton.set("disabled", true);
		}
	},
	
	
	enableButtons: function()
	{
		if (this.doneButton)
		{
			this.doneButton.set("disabled", false);
		}
		
		if (this.saveButton)
		{
			this.saveButton.set("disabled", false);
		}
		
		if (this.cancelButton)
		{
			this.cancelButton.set("disabled", false);
		}
	},
	
	/*
	afterSave: function(saveResponse)
	{
		
	},
	*/
	
	/*
	afterDone: function(doneResponse)
	{
		
	},
	*/
	/*
	beforeOpen: function(dialog)
	{
		
	},
	*/
	
	afterCancel: function(cancelResponse)
	{
		
	}
});