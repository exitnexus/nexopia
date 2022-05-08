function deleteComment(id){
	if (!confirm("Delete comment?"))
		return;
	
	var form = YAHOO.util.Dom.get("delete_comments_form");
	YAHOO.util.Connect.setForm(form);
	YAHOO.util.Connect.asyncRequest('POST', form.ajax_delete_url.value, new ResponseHandler({
		success: function(o) {
		},
		failure: function(o) {
		}
	}), "comment_id[]=" + id);
}


CommentList =
{
	toggleIgnore: function(userID, authKey, hrefElement)
	{
		var action = "ignore";
		var dialogQuestion = "Ignore this user?";
		var newLabelText = "Unignore User";
		var newAction = "unignore";
				
		if (hrefElement.innerHTML == newLabelText)
		{
			action = "unignore";
			dialogQuestion = "Unignore this user?";
			newLabelText = "Ignore User";
			newAction = "ignore";
		}
		
		var link = "/messages.php?action="+action+"&id="+userID+"&k="+authKey;
		
		if (confirm(dialogQuestion))
		{
			YAHOO.util.Connect.asyncRequest('GET', link,
			{
				success: function(o) 
				{
					var links = YAHOO.util.Dom.getElementsBy(
						function (e) 
						{ 
							return e.href == hrefElement.href 
						}, 
						'a',
						YAHOO.util.Dom.getAncestorByClassName (hrefElement, "comments_section"));
					
					for (var i = 0; i < links.length; i++)
					{
						links[i].setAttribute("href", "/messages.php?action="+newAction+"&id="+userID+"&k="+authKey);
						links[i].innerHTML = newLabelText;
					}
					
					// hrefElement.setAttribute("href", "/messages.php?action="+newAction+"&id="+userID+"&k="+authKey);
					// hrefElement.innerHTML = newLabelText;
				},
				failure: function(o) 
				{
				
				},
				scope: this
			}, "ajax=true");
		}
	}
}


Overlord.assign({
	minion: "comments:footer", 
	load: function(element) {
		
		YAHOO.util.Event.on("select_all_comments", "click", function(event){
			var root = YAHOO.util.Dom.get("comments_page");
			var that = this;
			YAHOO.util.Dom.getElementsByClassName("comment_delete", "input", root, function(input){
				if (that.checked)
					input.checked = true;
				else
					input.checked = false;
			});
		});
		
		YAHOO.util.Event.on("comments_delete_button", "click", function(event){
			var root = YAHOO.util.Dom.get("comments_page");
			if (event) {
				YAHOO.util.Event.preventDefault(event);
			}

			if (!confirm("Delete comments?"))
				return false;
		
			var list = "";
			YAHOO.util.Dom.getElementsByClassName("comment_delete", "input", root, function(input){
				if (input.checked)
					list += "comment_id[]=" + input.id + "&";
			});
			
			var form = YAHOO.util.Dom.get("delete_comments_form");
			YAHOO.util.Connect.setForm(form);
			YAHOO.util.Connect.asyncRequest('POST', form.ajax_delete_url.value, new ResponseHandler({
				success: function(o) {
				},
				failure: function(o) {
				},
				scope: this
			}), list);
			
		});
	}
});

