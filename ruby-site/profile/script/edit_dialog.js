if(YAHOO.profile == undefined){
	YAHOO.namespace("profile");
}

YAHOO.profile.EditDialog = function(el, profileDisplayBlock) {
    YAHOO.profile.EditDialog.superclass.constructor.call(this, el, 
	{
		xy: [(YAHOO.util.Dom.getDocumentWidth() - 580) / 2, YAHOO.util.Dom.getDocumentScrollTop() + 140],
		width:"580px",
		close:false,
		draggable:false,
		zindex:24,
		modal:true,
		visible:false,
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
		
		if(!YAHOO.profile.admin_user)
		{
			this.visibilitySelector = document.createElement("select");
			this.visibilitySelector.name = "block_visibility";
	
			var exclude_list = this.profileDisplayBlock.visibility_exclude();
			for (var i = 0; i < YAHOO.profile.visibility_options.length; i++)
			{
				var add_option = true;
				for (var j=0; j < exclude_list.length; j++)
				{
					if (YAHOO.profile.visibility_options[i][1] == exclude_list[j])
					{
						add_option = false;
						break;
					}
				}
				
				if (add_option)
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
			}
	
			for(var i = 0; i < this.visibilitySelector.options.length; i++)
			{
				if (this.visibilitySelector.options[i].value == this.profileDisplayBlock.visibility ||
					this.visibilitySelector.options.length - 1 == i)
				{
					this.visibilitySelector.selectedIndex = i;
					break;				
				}
			}
			
			// If we have one option or less, don't show the visibility selector because it serves no
			// purpose to allow selection of only one item.
			if (this.visibilitySelector.options.length <= 1)
			{
				this.visibilitySelector.style.display = "none";
			}
			
			this.dialogButtonBar.appendChild(this.visibilitySelector);
		}

		
		var formKey1 = document.getElementById("profile_block_form_key").value;
		var formKey2 = document.getElementById("profile_form_key").value;			
		
		function save(onSuccess)
		{
			dialog.disableButtons();			
			
			var visibilityPostArg = "";
			if(!YAHOO.profile.admin_user)
			{
				var visibility = dialog.visibilitySelector.options[dialog.visibilitySelector.selectedIndex].value;
				visibilityPostArg = "&visibility=" + visibility;
				dialog.profileDisplayBlock.visibility = visibility;
			}
			
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
			}, "ajax=true&form_key[]=" + formKey1 + "&form_key[]=" + formKey2 + "&module_name=" + dialog.profileDisplayBlock.module_name() +
				"&path=" + dialog.profileDisplayBlock.path + visibilityPostArg);
		}
		
		function refresh()
		{			
			dialog.disableButtons();
			
			YAHOO.util.Connect.asyncRequest('POST', refreshPath , new ResponseHandler({
				success: function(o) {
					dialog.enableButtons();
					
					dialog.destroy();
				},
				failure: function(o) {
					dialog.enableButtons();
					
					dialog.destroy();
				},
				scope: this
			}), "ajax=true&form_key[]=" + formKey1 + "&form_key[]=" + formKey2);
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
			this.doneButton.addClass("yui-button-spacer");
			
			function doneClick(e)
			{
				function done(response)
				{
					if (!dialog.afterDone && !dialog.profileDisplayBlock.in_place_editable())
					{
						refresh();
					}
					else
					{
						if(dialog.afterDone)
						{
							dialog.afterDone(response);
						}
						
						dialog.enableButtons();
						dialog.destroy();
					}
				}

				var avail_visibility_options = YAHOO.profile.visibility_options.length - dialog.profileDisplayBlock.visibility_exclude().length;
				
				if (dialog.profileDisplayBlock.new_block())
				{
					save(done);
				}
				else if(avail_visibility_options > 2)
				{
					var formKey1 = document.getElementById("profile_block_form_key").value;
					var formKey2 = document.getElementById("profile_form_key").value;
					
					var visibilityPostArg = "";
					if(!YAHOO.profile.admin_user)
					{
						var visibility = dialog.visibilitySelector.options[dialog.visibilitySelector.selectedIndex].value;
						visibilityPostArg = "&visibility=" + visibility;
						dialog.profileDisplayBlock.visibility = visibility;
					}
					
					YAHOO.util.Connect.asyncRequest('POST', savePath, {
						success: function(o) {},
						failure: function(o) {},
						scope: this
					}, "ajax=true&form_key[]=" + formKey1 + "&form_key[]=" + formKey2 + "&module_name=" + dialog.profileDisplayBlock.module_name() +
						"&path=" + dialog.profileDisplayBlock.path + visibilityPostArg);
					
					done();
				}
				else
				{
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
			this.cancelButton.addClass("yui-button-spacer");

			function cancelClick(e)
			{
				dialog.destroy();
				if (dialog.afterClose)
				{
					dialog.afterClose();
				}
			}

			this.cancelButton.addListener("click", cancelClick);
			
			// Save Button
			this.saveButton = new YAHOO.widget.Button({
				id: "save_button_" + this.id,
				type: "button",
				label: "save",
				container: this.dialogButtonBar
			});
			this.saveButton.addClass("yui-button-spacer");

			function saveClick(e)
			{
				if (dialog.beforeSave)
				{
					if (!dialog.beforeSave())
					{
						return;
					}
				}
				
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
					
					if (dialog.afterClose)
					{
						dialog.afterClose();
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

		function changeBodyHandler(type, args, dialog)
		{
			Overlord.summonMinions(dialog.innerElement);
			
			if (dialog.beforeOpen)
			{
				dialog.beforeOpen(dialog);
			}
			
			var inputWrapperDiv = YAHOO.util.Dom.getElementsByClassName("edit_form", "div", dialog.body)[0];
			if(inputWrapperDiv)
			{
				inputWrapperDiv.style.display = 'none';
				
				var bdParent = YAHOO.util.Dom.getAncestorByClassName(inputWrapperDiv, 'bd');
				bdParent.style.overflow = 'auto';
				setTimeout(function() {inputWrapperDiv.style.display = 'block';}, 20);
			}
		}
		this.changeBodyEvent.subscribe(changeBodyHandler, this);
				
		function afterOpenHandler(type, args, dialog)
		{	
			var inputWrapperDiv = YAHOO.util.Dom.getElementsByClassName("edit_form", "div", dialog.body)[0];
			var bdParent = YAHOO.util.Dom.getAncestorByClassName(inputWrapperDiv, 'bd');
			bdParent.style.overflow = 'auto';
			inputWrapperDiv.style.display = 'block';
		}
		this.showEvent.subscribe(afterOpenHandler, this);
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