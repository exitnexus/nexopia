if(YAHOO.blog == undefined){
	YAHOO.namespace ("blog");
}

YAHOO.blog.BlogView =
{
	toggle: function(element)
	{
		// Find the elements under it that we either want to show or hide
		var blogPostDetails = YAHOO.util.Dom.getElementsByClassName('blog_post', 'div', element)[0];
		var blogPostUserFunctions = YAHOO.util.Dom.getElementsByClassName('user_functions', 'div', element)[0];
		var blogPostCollapsedUserFunctions = YAHOO.util.Dom.getElementsByClassName('collapsed_user_functions', 'div', element)[0];
		var blogExtraContent = YAHOO.util.Dom.getElementsByClassName('extra_content', 'div', element)[0];

		// Find the toggle symbol span that we want to change
		var blogPostToggle = YAHOO.util.Dom.getElementsByClassName('toggle', 'span', element)[0];
		
		// Figure out whether we're showing things or hiding them
		var currentSymbol = blogPostToggle.innerHTML;
		var nextDisplay = '';
		var collapseDisplay = '';
		var handler = '';
		if (currentSymbol== '+')
		{
			handler = 'show_details';
			nextDisplay = 'block';
			collapseDisplay = 'none';
			nextSymbol = "-";
		}
		else if (currentSymbol == '-')
		{
			handler = 'hide_details';
			nextDisplay = 'none';
			collapseDisplay = 'block';
			nextSymbol = "+";
		}
		
		// Do the actual show/hide
		YAHOO.util.Dom.setStyle(blogPostDetails, 'display', nextDisplay);
		YAHOO.util.Dom.setStyle(blogPostUserFunctions, 'display', nextDisplay);
		YAHOO.util.Dom.setStyle(blogPostCollapsedUserFunctions, 'display', collapseDisplay);
		YAHOO.util.Dom.setStyle(blogExtraContent, 'display', nextDisplay);
		blogPostToggle.innerHTML = nextSymbol;
		
		var id = YAHOO.blog.BlogView.getDatabaseID(element);

		// TODO: Show some sort of minimalistic 'user_functions' (like comments) in the header when the post is hidden,
		// as in the actual blog spec.		
		
		return '/my/blog/navigation/' + id.blogUserID + '/' + id.postID + '/' + handler;	
	},
	
	
	getDatabaseID: function(element)
	{
		var idString = element.id.replace(/blog_post_/,'');
		var idParts = idString.split(":");
		
		return { blogUserID: idParts[0], postID: idParts[1] };
	},
	
	
	inCollapseList: function(element)
	{
		var id = YAHOO.blog.BlogView.getDatabaseID(element);
		for(var i = 0; i < Nexopia.JSONData.navigation_list.length; i++)
		{
			var keySet = Nexopia.JSONData.navigation_list[i];
			if(id.blogUserID == keySet[0] && id.postID == keySet[1])
			{
				return true;
			}
		}
		return false;
	},
	
	
	initializePostCollapsing: function(element)
	{
		var list = YAHOO.util.Dom.getElementsByClassName('blog_post_container', 'div');
		for(var i = 0; i < list.length; i++)
		{
			if (YAHOO.blog.BlogView.inCollapseList(list[i]))
			{
				// Collapse the blog post
				YAHOO.blog.BlogView.toggle(list[i]);
			}
		}
	}
};

Overlord.assign({
	minion: "blog:select_all",
	click: function(event, element) {
		YAHOO.util.Event.preventDefault(event);
		var elements = YAHOO.util.Dom.getElementsByClassName('blog_post_select', 'input', null, function(checkbox) {checkbox.checked=true;});
		if(elements.length != 0)
		{
			if (YAHOO.blog.BlogView.deleteBtn)
			{
				YAHOO.blog.BlogView.deleteBtn.set("disabled", false);
			}
			if (YAHOO.blog.BlogView.changePermissionsBtn)
			{
				YAHOO.blog.BlogView.changePermissionsBtn.set("disabled", false);
			}
		}
	}
});

Overlord.assign({
	minion: "blog:select_none",
	click: function(event, element){
		YAHOO.util.Event.preventDefault(event);
		var elements = YAHOO.util.Dom.getElementsByClassName('blog_post_select', 'input', null, function(checkbox) {checkbox.checked=false;});
		if(elements.length != 0)
		{
			if (YAHOO.blog.BlogView.deleteBtn)
			{
				YAHOO.blog.BlogView.deleteBtn.set("disabled", true);
			}
			if (YAHOO.blog.BlogView.changePermissionsBtn)
			{
				YAHOO.blog.BlogView.changePermissionsBtn.set("disabled", true);
			}
		}		
	}
});

Overlord.assign({
	minion: "blog:show_details_toggle",
	click: function(event, element){
		YAHOO.util.Event.preventDefault(event);
		
		var blogPostContainer = YAHOO.util.Dom.getAncestorByClassName(element, 'blog_post_container');
		var postURL = YAHOO.blog.BlogView.toggle(blogPostContainer);
		var formKey = document.getElementById('viewer_navigation_form_key').value;
		
		// Make an Ajax post to record the setting. Only record for logged in users, of course
		YAHOO.util.Connect.asyncRequest('POST', postURL, {
			success: function(o) {
			},
			failure: function(o) {
			},
			scope: this
		}, "ajax=true&form_key[]=" + formKey);		
	}
});

Overlord.assign({
	minion: "blog:footer",
	load: function(element){
		YAHOO.blog.BlogView.deleteBtn = new YAHOO.widget.Button("delete_btn", {disabled:true});
		YAHOO.blog.BlogView.changePermissionsBtn = new YAHOO.widget.Button("change_permissions_btn", {disabled:true});
	}
});

Overlord.assign({
	minion: "blog:post_select",
	click: function(element){
		var disable = true;
		var postSelectElements = YAHOO.util.Dom.getElementsByClassName("blog_post_select", "input", "blog");
		for(var i = 0; i < postSelectElements.length; i++)
		{
			if (postSelectElements[i].checked)
			{
				disable = false;
				break;
			}
		}
		
		if (YAHOO.blog.BlogView.deleteBtn)
		{
			YAHOO.blog.BlogView.deleteBtn.set("disabled", disable);
		}
		if (YAHOO.blog.BlogView.changePermissionsBtn)
		{
			YAHOO.blog.BlogView.changePermissionsBtn.set("disabled", disable);
		}
	}
});

Overlord.assign({
	minion: "blog:filter",
	change: function(event, element) {
		var form = YAHOO.util.Dom.getAncestorByTagName(element, "form");
		form.submit();		
	}
});