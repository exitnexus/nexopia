if(YAHOO.profile == undefined){
	YAHOO.namespace ("profile");
}

YAHOO.profile.ControlBlock =
{
	init: function()
	{
		this.setupCommentLink();
	},
	
	
	setupCommentLink: function()
	{
		var links = YAHOO.util.Dom.getElementsByClassName("comment_control_block_link", "a");
		if (links.length > 0)
		{
			var href = links[0].getAttribute("href");
		
			function gotoComments()
			{
				var block = document.getElementById("comments_profile_block");
			
				if (block)
				{
					var xy = YAHOO.util.Dom.getXY(block);
					window.scrollTo(xy[0], xy[1]);
				}
				else
				{
					window.location = href;
				}
			}

			for(var i=0; i < links.length; i++)
			{
				links[i].setAttribute("href", "javascript:;");
				YAHOO.util.Event.addListener(links[i], "click", gotoComments);
			}
		}
	},
	
	toggleFriend: function(hrefElement, username)
	{
		var parts = hrefElement.href.split("?");
		var link = parts[0];
		var params = parts[1];
		var dialogQuestion = hrefElement.attributes["dialog_question"].value;
		
		if (confirm(dialogQuestion))
		{
			YAHOO.util.Connect.asyncRequest('POST', link, new ResponseHandler({
				success: function(o) 
				{
					YAHOO.util.Connect.asyncRequest('GET', "/users/"+username+"/profile_blocks/Profile/control/friend_toggle:Body", new ResponseHandler({
						success: function(o) {
						},
						failure: function(o) {
						}
					}), "");
				},
				failure: function(o) 
				{

				},
				scope: this
			}), "ajax=true&" + params);
		}
	},
	
	
	toggleIgnore: function(userID, authKey, hrefElement)
	{
		var action = "ignore";
		var dialogQuestion = "Ignore this user?";
		var newLabelText = "UNIGNORE USER";
		var newAction = "unignore";
		
		var trElement = YAHOO.util.Dom.getAncestorByTagName(hrefElement, "tr");
		var hrefTextElement = YAHOO.util.Dom.getElementsByClassName("ignore_toggle_text_link", "a", trElement)[0];
		
		if (hrefTextElement.innerHTML == newLabelText)
		{
			action = "unignore";
			dialogQuestion = "Unignore this user?";
			newLabelText = "IGNORE USER";
			newAction = "ignore";
		}
		
		var link = "/messages.php?action="+action+"&id="+userID+"&k="+authKey;
		
		if (confirm(dialogQuestion))
		{
			YAHOO.util.Connect.asyncRequest('GET', link,
			{
				success: function(o) 
				{
					hrefTextElement.setAttribute("href", "/messages.php?action="+newAction+"&id="+userID+"&k="+authKey);
					hrefTextElement.innerHTML = newLabelText;
				},
				failure: function(o) 
				{
				
				},
				scope: this
			}, "ajax=true");
		}
	}
};

Overlord.assign({
	minion: "profile:control_block",
	load: function(element) {
		YAHOO.profile.ControlBlock.init();
	},
	order: 2
	
});

// These two Overlord calls are partially a hack for IE6 to make :hover work.  IE6 doesn't support :hover for anything other than <a> tags.
// So instead we add a "hover class" to table row element whenever we mouseover and set the colours that way.
// I also moved in some code to change the custom color icon color on mouseover/out.
Overlord.assign({
	minion: "profile:ie6_hover_hack",
	mouseover: function(event, element) {
		YAHOO.util.Dom.addClass(element, 'hover');		

		YAHOO.util.Dom.getElementsByClassName('color_icon', null, element, function() {
			this.setColor(YAHOO.util.Dom.getStyle(this.parentNode, "color"));
		});
		
	},
	mouseout: function(event, element) {
		YAHOO.util.Dom.removeClass(element, 'hover');		
		
		YAHOO.util.Dom.getElementsByClassName('color_icon', null, element, function() {
			this.resetColor();			
		});
	}	
});