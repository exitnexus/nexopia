/* 
	This is the base class for AsyncPanel and DivPanel. It should not be used directly. Use one of the
	subclasses. The subclasses are classically used via a minion_name on an HTML element.
	
	Optional attributes in addition to minion_name for all subclasses:
	
	exit_path: 	The path to post any included form and/or external form (on the page, but outside of the
				dialog, pointed to by "form_id") to. A spinner will again be displayed instead of the dialog,
				and control will return to the user once the post request has been made via ajax.
	form_id: 	The id of a form that is on the page, but not in the dialog box (this is especially useful 
				when you're selecting a bunch of elements on the page to do an operation on). Note that if
				there is also a form in the dialog, that form's data will be appended to the parameter string
				before setting the form referenced by "form_id" so that the parameters from both forms get
				passed to the "exit_path" handler.
	ajax_exit: 	"true" or "false". If "true" (which is the default if this attribute is not set), the panel
				will save via an async request and attempt to refresh any elements returned in the usual way
				that ResponseHandler refreshes elements. If "false", the panel will save via the regular
				form post (which means there needs to either be a form_id specified, a form within the dialog,
				or both) mechanism (as if the user had hit a "submit" button).
				
	Important: Inside the dialog, there should be a maximum of ONE form.
	
	Special element classes inside the dialog:
	
	button.nexopia_panel_close: 			A button with this class will get a click listener automatically added to it
	 										that will close the dialog.
	button.nexopia_panel_save_and_close: 	A button with this class will get a click listener automatically added to it
	 										that will post all the inner form data, plus any form data in an optional
											external form (identified by "form_id"), to the page handler specified by
											"exit_path".
*/
// TODO: use configuration objects instead of parameters to abstract out the minion init code
NexopiaPanel = function(cfg) {
	this.cfg = cfg;
};

NexopiaPanel.prototype = {
	overlay: null, //the YUI panel widget
	spinnerBody: "<div id='nexopia_panel_spinner'><img src='"+Site.staticFilesURL+"/nexoskel/images/large_spinner.gif'/></div>",
	showSpinner: function() {
		this.initOverlay();
		this.overlay.setBody(this.spinnerBody);
		this.overlay.render(document.body);
		this.overlay.center();
	},
	open: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
			var element = YAHOO.util.Event.getTarget(event);
			//linkBeforeOpenMap is a map of element ids to functions, if an element id is in the map
			//the function is executed with the element, if it returns false we do not open a panel
			if(NexopiaPanel.linkBeforeOpenMap[element.id])
			{
				if(!NexopiaPanel.linkBeforeOpenMap[element.id](element))
				{
					return;
				}
			}	
		}
		
		NexopiaPanel.current = this;
		this.drawPanel();
		
		var forms = YAHOO.util.Dom.getElementsBy(function(el){return true;},'form',this.overlay.innerElement);
		if(forms.length > 0)
		{
			var inputs = YAHOO.util.Dom.getElementsBy(function(el){return true;},'input',forms[0]);
			if (inputs.length > 0)
			{
				inputs[0].focus();
			}
		}
	},
	disableButtons: function()
	{
		// Remove any close button listeners
		var closeButtons = YAHOO.util.Dom.getElementsByClassName('nexopia_panel_close', 'button', this.overlay.innerElement);
		for(var i = 0; i < closeButtons.length; i++)
		{
			closeButtons[i].disabled = true;
			YAHOO.util.Event.removeListener(closeButtons[i], 'click');
		}
		
		// Remove any close button listeners
		var saveAndCloseButtons = YAHOO.util.Dom.getElementsByClassName('nexopia_panel_save_and_close', 'button', this.overlay.innerElement);
		for(var i = 0; i < saveAndCloseButtons.length; i++)
		{
			saveAndCloseButtons[i].disabled = true;
			YAHOO.util.Event.removeListener(saveAndCloseButtons[i], 'click');
		}
	},
	close: function(event, element) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		
		this.disableButtons();
		
		this.overlay.destroy();
		this.overlay = null;
		NexopiaPanel.current = null;
	},
	postAndClose: function(event, element) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		
		this.disableButtons();
		
		var that = this;
		var form = YAHOO.util.Dom.getElementsBy(function(el){return true;},'form',this.overlay.innerElement)[0];

		var extra = new Array();
		
		// If we have an external form_id, we'll want to set it as the form to use in the asyncRequest. However, if
		// we also have the internal form, we'll want to take all of it's data and append it to the asyncRequest's
		// parameter string
		if (this.cfg.form_id)
		{
			var outerForm = document.getElementById(this.cfg.form_id);
			if (form)
			{
				for(var i = 0; i < form.elements.length; i++)
				{
					if(form.elements[i].name != null && form.elements[i].name != "")
					{
						var input = document.createElement("input");
						input.type = "hidden";
						input.name = form.elements[i].name;
						input.value = form.elements[i].value;
						extra[extra.length] = input;
						outerForm.appendChild(input);
					}
				}
			}
			form = outerForm;
		}
		
		function removeExtraElements()
		{
			for(var i = 0; i < extra.length; i++)
			{
				form.removeChild(extra[i]);
			}
		};

		if (this.cfg.ajax_exit)
		{
			YAHOO.util.Connect.setForm(form);

			this.showSpinner();
			// For some reason, the minified version of container doesn't seem to be able to set the zIndex properly,
			// so we are doing that again after the panel has been initialized.
			this.overlay.cfg.setProperty("zIndex", 500);
		
			YAHOO.util.Connect.asyncRequest(this.cfg.exit_method, this.cfg.exit_path, new ResponseHandler({
				success: function(o) {
					if (form)
					{
						removeExtraElements();
						form.reset();
					}
					that.close();
				},
				failure: function(o) {
					if (form)
					{
						removeExtraElements();
						form.reset();
					}
					that.close();
				},
				scope: this
			}), "ajax=true");
		}
		else
		{
			form.method = this.cfg.exit_method;
			form.action = this.cfg.exit_path;
			form.submit();
		}
	},
	redirect: function()
	{
		window.location = this.cfg.exit_path;
	},
	//returns a reference to the yui overlay, initializing it if it doesn't exist.
	initOverlay: function() {
		if (!this.overlay) {
			this.overlay = new YAHOO.widget.Panel("nexopia_panel", {
				fixedcenter: false,
				visible: true,
				modal:true,
				close:false,
				draggable:false,
				zIndex: 500,
				underlay: "none"
			});
			this.overlay.render(document.body);
			
			// For some reason, the minified version of container doesn't seem to be able to set the zIndex properly,
			// so we are doing that again after the panel has been initialized.
			this.overlay.cfg.setProperty("zIndex", 500);
			
			// Fix for a problem in Firefox 2 where the panel won't show.
			if(YAHOO.env.ua.gecko < 1.9)
			{
				YAHOO.util.Dom.setStyle(this.overlay.innerElement, 'position', 'relative');
			}
		}
	},	
	initializeButtons: function()
	{
		var closeButtons = YAHOO.util.Dom.getElementsByClassName('nexopia_panel_close', null, this.overlay.innerElement);
		for(var i = 0; i < closeButtons.length; i++)
		{
			YAHOO.util.Event.on(closeButtons[i], 'click', this.close, this, true);
		}
		
		var postAndCloseButtons = YAHOO.util.Dom.getElementsByClassName('nexopia_panel_save_and_close', null, this.overlay.innerElement);
		for(var i = 0; i < postAndCloseButtons.length; i++)
		{
			YAHOO.util.Event.on(postAndCloseButtons[i], 'click', this.postAndClose, this, true);
		}
		
		var redirectButtons = YAHOO.util.Dom.getElementsByClassName('nexopia_panel_redirect', null, this.overlay.innerElement);
		for(var i = 0; i < redirectButtons.length; i++)
		{
			YAHOO.util.Event.on(redirectButtons[i], 'click', this.redirect, this, true);
		}
	},
	render: function()
	{
		this.overlay.render(document.body);
		this.overlay.element = this.overlay.element.firstChild; //This bypasses a centering problem in some versions of firefox
		this.overlay.center();
		Overlord.summonMinions(this.overlay.element);

		this.initializeButtons();		
	}
};

NexopiaPanel.createConfig = function(element)
{
	var ajaxExit = null;
	var ajaxExitString = element.getAttribute('ajax_exit');
	
	if (ajaxExitString == null || ajaxExitString == "")
	{
		ajaxExit = true;
	}
	else
	{
		ajaxExit = ajaxExitString == "true";
	}
	
	var exitMethod = element.getAttribute('exit_method');
	if (exitMethod == null || exitMethod == "")
	{
		exitMethod = "post";
	}
	
	return { 
		exit_path: element.getAttribute('exit_path'), 
		exit_method: exitMethod,
		form_id: element.getAttribute('form_id'), 
		ajax_exit: ajaxExit
	};
};

NexopiaPanel.setup = function(element, panel)
{
	if (element.id) {
		NexopiaPanel.linkMap[element.id] = panel;
	}
	YAHOO.util.Event.on(element, 'click', panel.open, panel, true);
};

NexopiaPanel.linkBeforeOpenMap = {};
NexopiaPanel.linkMap = {};
NexopiaPanel.current = null; //This will refer to the currently open panel if a panel is open