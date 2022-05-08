if(YAHOO.gallery == undefined){
	YAHOO.namespace("gallery");
}

YAHOO.gallery.CommentsView =
{
	init: function()
	{
		YAHOO.gallery.CommentsView.current_page = Nexopia.JSONData['gallery_comments_current_page'];
		
		YAHOO.util.Event.addListener("select_all_comments", "click", YAHOO.gallery.CommentsView.select_all_click);
	},
	
	init_paging: function(element)
	{	
		YAHOO.gallery.CommentsView.current_page = Nexopia.JSONData['gallery_comments_current_page'];
		
		YAHOO.util.Event.addListener(element, "click", YAHOO.gallery.CommentsView.get_page);
	},
	
	get_page: function(e)
	{
		if (e) {
			YAHOO.util.Event.preventDefault(e);
		}
		
		// In some cases the target of the event is the paging arrow instead of the link
		// on the image.  So if we get the image tag we just go to the parent which
		// should be the link we're looking for.
		var target = YAHOO.util.Event.getTarget(e);
		if(target.tagName.toLowerCase() == "img")
		{
			target = target.parentNode;
		}

		var ajax_path = Nexopia.JSONData['gallery_comments_page_path'];		
		var page_path = "http://"+ target.host + ajax_path + target.search;
		
		var error_row = document.getElementById("comments_user_message_row");
		if(error_row)
		{
			error_row.parentNode.removeChild(error_row);
		}
		
		YAHOO.util.Connect.asyncRequest("GET", page_path, new ResponseHandler({
			success: function(o) {
			},
			failure: function(o) {
				//Should change this into generic function to display error messages.
				alert("Epic fail!");
			},
			scope: this
		}), "");
	},
	
	init_editor: function() {
		YAHOO.util.Event.on("comment_post_submit", "click", YAHOO.gallery.CommentsView.submit_post);
		YAHOO.util.Event.addListener("comment_text", "focus", YAHOO.gallery.CommentsView.clear_input);
		
		var text_area = document.getElementById("comment_text");
		text_area.value = "Post a comment...";
		
		// Disable the submit button on load so the user can't post the initial text.
		var post_button = YAHOO.util.Dom.get("comment_post_submit");
		if (post_button)
		{
			post_button.disabled = true;
		}
	},
	
	admin_get_params: function()
	{
		var temp_arr = [];
		if(Nexopia.JSONData['get_params'] && Nexopia.JSONData['get_params'] != "")
		{
			temp_arr.push(Nexopia.JSONData['get_params']);
		}
		
		return temp_arr;
	},
	
	select_all_click: function(e)
	{
		var input_list = YAHOO.util.Dom.getElementsByClassName("comment_delete_input", "input", "delete_comments_form", function(obj){});
		
		for(var i=0; i < input_list.length; i++)
		{
			input_list[i].checked = e.target.checked;
		}
	},
	
	clear_delete_inputs: function(type, args)
	{
		var select_all_input = document.getElementById("select_all_comments");
		select_all_input.checked = false;
		
		var root_page = document.getElementById("delete_comments_form");
		var input_list = YAHOO.util.Dom.getElementsByClassName("comment_delete_input", "input", root_page, function(obj){});
		
		for(var i=0; i < input_list.length; i++)
		{
			input_list[i].checked = false;
		}
	},
	
	submit_post: function(e) {

		if (e) {
			YAHOO.util.Event.preventDefault(e);
		}
		
		var comments_form = document.getElementById("comment_write_form");
		
		var post_button = document.getElementById("comment_post_submit");
		var spinner = document.getElementById("post_comment_spinner");
		if(post_button && spinner)
		{
			YAHOO.util.Dom.setStyle(post_button, "display", "none");
			post_button.disabled = true;
			YAHOO.util.Dom.setStyle(spinner, "display", "block");
		}
		
		YAHOO.util.Connect.setForm(comments_form);
		YAHOO.util.Connect.asyncRequest(comments_form.method, comments_form.action + "/dynamic", new ResponseHandler({
			success: function(o) {
				YAHOO.gallery.CommentsView.enable_editor();
				// YAHOO.gallery.CommentsView.post_update_comments_view(o);
			},
			failure: function(o) {
				alert("Epic fail!");
			},
			scope: this
		}), "");
	},
	
	post_update_comments_view: function(connection_obj)
	{
		var result_obj = YAHOO.lang.JSON.parse(connection_obj.responseText);
		
		// We only want to insert the comment if we got a successful return and
		// we're on the first page.
		if( result_obj.success && (YAHOO.gallery.CommentsView.current_page == 0) )
		{
			// Since we mad a successful post we want to clear the error message at the top
			// of the comments section if it exists.
			var error_row = document.getElementById("comments_user_message_row");
			if(error_row)
			{
				error_row.parentNode.removeChild(error_row);
			}
			
			var top_comment_id = YAHOO.gallery.CommentsView.min_comment_id();
			var bottom_comment_id = YAHOO.gallery.CommentsView.max_comment_id();
			var visible_comments_count = YAHOO.gallery.CommentsView.num_comments_displayed();
			
			var top_comment = document.getElementById("user_gallery_comment_" + top_comment_id);
			var bottom_comment = document.getElementById("user_gallery_comment_" + bottom_comment_id);
			
			var new_element = document.createElement("div");
			new_element.innerHTML = result_obj.comment_content;
			
			// if we have a comment on the page already, then we want to add the new comment below the
			// existing one.
			if(bottom_comment)
			{
				bottom_comment.parentNode.appendChild(new_element.firstChild);
			}
			// Otherwise we just insert the new comment.
			else
			{
				var root = document.getElementById("gallery_comments_individual_page_wrapper");
				if(root)
				{
					root.appendChild(new_element.firstChild);
				}
			}
			
			// Attach any JS actions that should be attached to a comment object.
			var new_comment = document.getElementById("user_gallery_comment_" + result_obj.comment_id);
			if(new_comment)
			{
				Overlord.summonMinions(new_comment);
			}
			
			// Scroll the top comment off if we end up with more than 5 on the page.
			if( top_comment && (visible_comments_count >= 5) )
			{
				YAHOO.util.Event.purgeElement(top_comment, true);
				bottom_comment.parentNode.removeChild(top_comment);
			}
		}
		
		// If the post wasn't successful post the error message.
		// If the post was successful, but we're not on the first page, then
		// Post a message saying we were successful.
		if( !result_obj.success || (YAHOO.gallery.CommentsView.current_page > 0) )
		{
			YAHOO.gallery.CommentsView.display_user_error(result_obj);
		}
		
		// Re-enable the editor so we can post another innane comment.
		YAHOO.gallery.CommentsView.enable_editor();
	},
	
	quick_delete: function(e)
	{
		if (e) {
			YAHOO.util.Event.preventDefault(e);
		}
		
		if(!confirm("Delete comment?"))
		{
			return;
		}
		
		var target = YAHOO.util.Event.getTarget(e);
		if(target.tagName.toLowerCase() == "img")
		{
			target = target.parentNode;
		}
		
		var bottom_comment_id = YAHOO.gallery.CommentsView.min_comment_id();		
		
		var separator = "";
		if(!(target.host.charAt(target.host.length-1) == "/") && !(target.pathname.charAt(0) == "/"))
		{
			separator = "/";
		}
		
		var delete_path = Site.wwwURL+separator+target.pathname+"/dynamic"+target.search+"&last_id="+bottom_comment_id;

		YAHOO.util.Connect.asyncRequest("GET", delete_path, new ResponseHandler({
			success: function(o) {
				YAHOO.gallery.CommentsView.delete_update_comments_view(o);
			},
			scope: this
		}), "");
	},
	
	delete_update_comments_view: function(connection_obj)
	{
		var result_obj = YAHOO.lang.JSON.parse(connection_obj.responseText);
		
		if(result_obj.success)
		{
			var error_row = document.getElementById("comments_user_message_row");
			if(error_row)
			{
				error_row.parentNode.removeChild(error_row);
			}
			
			var bottom_comment_id = YAHOO.gallery.CommentsView.min_comment_id();
			
			var del_comment_obj = document.getElementById("user_gallery_comment_" + result_obj.removed_comment_id);
			YAHOO.util.Event.purgeElement(del_comment_obj, true);
			del_comment_obj.parentNode.removeChild(del_comment_obj);
			
			//Check to see if there is no content
			if(result_obj.comment_content == null || result_obj.comment_content == "")
			{
				return;
			}
			
			if(result_obj.removed_comment_id == bottom_comment_id)
			{
				bottom_comment_id = YAHOO.gallery.CommentsView.min_comment_id();
			}

			var new_element = document.createElement("div");
			new_element.innerHTML = result_obj.comment_content;

			var bottom_comment = document.getElementById("user_gallery_comment_" + bottom_comment_id);
			bottom_comment.parentNode.appendChild(new_element.firstChild);
			
			var new_comment = document.getElementById("user_gallery_comment_" + result_obj.comment_id);
			if(new_comment)
			{
				Overlord.summonMinions(new_comment);
			}
		}
		else
		{
			YAHOO.gallery.CommentsView.display_user_error(result_obj);
		}
	},
	
	display_user_error: function(result_obj)
	{
		var error_node = document.getElementById("gallery_comments_post_error");
		var success_node = document.getElementById("gallery_comments_post_success");
		if(error_node && !result_obj.success)
		{
			error_node.innerText = result_obj.error;
			return;
		}
		else if((error_node && result_obj.success) || (success_node && !result_obj.success))
		{
			var message_row = document.getElementById("comments_user_message_row");
			if(message_row)
			{
				message_row.parentNode.removeChild(message_row);
			}
		}
		else if(success_node && result_obj.success)
		{
			return;
		}
		
		var new_row = document.createElement("tr");
		new_row.id = "comments_user_message_row";
		
		var left_col = document.createElement("td");
		var right_col = document.createElement("td");

		var new_element = document.createElement("div");
		
		if(result_obj.success)
		{
			var text = document.createTextNode("Comment post successful. Refresh the page to view your comment.");
			new_element.appendChild(text);
			
			new_element.id = "comments_post_success";
		}
		else
		{
			var text = document.createTextNode(result_obj.error);
			new_element.appendChild(text);
		
			new_element.id = "gallery_comments_post_error";
		}
		
		new_row.appendChild(left_col);
		new_row.appendChild(right_col);
		right_col.appendChild(new_element);
	
		var comments_row = document.getElementById("comments_editor_row");
	
		comments_row.parentNode.insertBefore(new_row, comments_row);
	},
	
	enable_editor: function() {
		var spinner = document.getElementById("post_comment_spinner");
		var post_button = document.getElementById("comment_post_submit");
		var text_area = document.getElementById("comment_text");
		
		text_area.value = "Post a comment...";
		YAHOO.util.Event.addListener(text_area, "focus", YAHOO.gallery.CommentsView.clear_input, this);
		YAHOO.util.Dom.setStyle(post_button, "display", "block");
		
		if(post_button && spinner)
		{
			YAHOO.util.Dom.setStyle(post_button, "display", "block");
			post_button.disabled = true;
			YAHOO.util.Dom.setStyle(spinner, "display", "none");
		}
	},
	
	clear_input: function(e) {
		if (e) {
			YAHOO.util.Event.preventDefault(e);
		}
		
		// clear the listener off so it doesn't go again if they click out, then back into the editor.
		YAHOO.util.Event.removeListener("comment_text", "focus", YAHOO.gallery.CommentsView.clear_input);
		
		// clear the text in the editor
		YAHOO.util.Dom.get("comment_text").value = "";
		
		// enable the post button so they can post their comment.
		YAHOO.util.Dom.get("comment_post_submit").disabled = false;
	},
	
	min_comment_id: function()
	{
		var root = document.getElementById("gallery_comments_block");
		
		var comments_list = YAHOO.util.Dom.getElementsByClassName("comment_view_container", "div", "gallery_comments_page_wrapper", function(obj){});
		var min_id;
		var temp, temp_id;
		
		for(var i=0; i < comments_list.length; i++)
		{
			temp = comments_list[i].id.split("_");
			temp_id = parseInt(temp[3], 10);
			if(min_id == null)
			{
				min_id = temp_id;
			}
			min_id = Math.min(temp_id, min_id);	
		}
		
		return min_id;
	},
	
	max_comment_id: function()
	{
		var root = document.getElementById("gallery_comments_block");
		
		var comments_list = YAHOO.util.Dom.getElementsByClassName("comment_view_container", "div", "gallery_comments_page_wrapper", function(obj){});
		var max_id = 0;
		var temp, temp_id;
		
		for(var i=0; i < comments_list.length; i++)
		{
			temp = comments_list[i].id.split("_");
			temp_id = parseInt(temp[3], 10);
			if(max_id == null)
			{
				max_id = temp_id;
			}
			max_id = Math.max(temp_id, max_id);
		}
		
		return max_id;
	},
	
	num_comments_displayed: function()
	{
		var root = document.getElementById("gallery_comments_block");
		var comments_list = YAHOO.util.Dom.getElementsByClassName("comment_view_container", "div", "gallery_comments_page_wrapper", function(obj){});
		
		return comments_list.length;
	}
};

Overlord.assign({
	minion: "gallery_comments:view",
	load: function(element){
		YAHOO.gallery.CommentsView.init();
	}
});

Overlord.assign({
	minion: "gallery_comments:quick_delete",
	load: function(element){
		YAHOO.util.Event.addListener(element, "click", YAHOO.gallery.CommentsView.quick_delete);
	}
});

Overlord.assign({
	minion: "gallery_comments:write",
	load: function(element){
		YAHOO.gallery.CommentsView.init_editor();
	}
});

Overlord.assign({
	minion: "gallery_comments:paging_control",
	load: function(element){
		YAHOO.gallery.CommentsView.init_paging(element);
	}
});

Overlord.assign({
	minion: "gallery_comments:select_all",
	click: function(event){
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		YAHOO.util.Dom.getElementsByClassName('comment_delete_input', 'input', null, function(checkbox) {checkbox.checked = true;});	
	},
	scope: YAHOO.gallery.CommentsView
});

Overlord.assign({
	minion: "gallery_comments:select_none",
	click: function(event){
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		YAHOO.util.Dom.getElementsByClassName('comment_delete_input', 'input', null, function(checkbox) {checkbox.checked = false;});	
	},
	scope: YAHOO.gallery.CommentsView
});

