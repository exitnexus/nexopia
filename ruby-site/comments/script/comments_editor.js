if(YAHOO.comments == undefined){
	YAHOO.namespace("comments");
}

YAHOO.comments.Editor =
{
	init: function() {
		YAHOO.util.Event.on("comment_post_submit", "click", YAHOO.comments.Editor.submit_post);
		YAHOO.util.Event.addListener("comment_text", "focus", YAHOO.comments.Editor.clear_input);
		
		var text_area = document.getElementById("comment_text");
		text_area.value = "Post a comment...";
		
		// Disable the submit button on load so the user can't post the initial text.
		var post_button = YAHOO.util.Dom.get("comment_post_submit");
		if (post_button)
		{
			post_button.disabled = true;
		}
	},
	
	clear_input: function(e) {
		if (e) {
			YAHOO.util.Event.preventDefault(e);
		}
		
		// clear the listener off so it doesn't go again if they click out, then back into the editor.
		YAHOO.util.Event.removeListener("comment_text", "focus", YAHOO.comments.Editor.clear_input);
		
		// clear the text in the editor
		YAHOO.util.Dom.get("comment_text").value = "";
		
		// enable the post button so they can post their comment.
		YAHOO.util.Dom.get("comment_post_submit").disabled = false;
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
				YAHOO.comments.Editor.update_comments_view(o);
			},
			failure: function(o) {
				alert("Epic fail!")
			},
			scope: this
		}), "");
	},
	
	update_comments_view: function(connection_obj)
	{
		var result_obj = YAHOO.lang.JSON.parse(connection_obj.responseText);
		var current_page = parseInt(Nexopia.JSONData['current_page'], 10);

		if(result_obj.success && current_page == 0)
		{
			var message_row = document.getElementById("comments_user_message_row");
			if(message_row)
			{
				message_row.parentNode.removeChild(message_row);
			}
			
			var top_comment_id = YAHOO.comments.Editor.max_comment_id();
			var bottom_comment_id = YAHOO.comments.Editor.min_comment_id();
			var remove_bottom_comment = false;
			var profile_block = document.getElementById("comments_profile_block")
			
			var comment_limit = 20;
			if(profile_block)
			{
				comment_limit = 5;
			}

			if(Nexopia.JSONData.comments_id_list.length >= comment_limit)
			{
				for(var i=0; i < Nexopia.JSONData.comments_id_list.length; i++)
				{
					if(Nexopia.JSONData.comments_id_list[i] == parseInt(bottom_comment_id, 10))
					{
						Nexopia.JSONData.comments_id_list[i] = result_obj.comment_id;
						remove_bottom_comment = true;
						break;
					}
				}
			}
			else
			{
				Nexopia.JSONData.comments_id_list.push(result_obj.comment_id);
			}
			
			var top_comment = document.getElementById("user_comment_" + top_comment_id);
			if(!top_comment)
			{
				var delete_form = document.getElementById("delete_comments_form");
				var conversation_wrapper = document.getElementById("conversation_wrapper");
			}
			var bottom_comment = document.getElementById("user_comment_" + bottom_comment_id);
			
			var new_element = document.createElement("div");
			new_element.innerHTML = result_obj.comment_content;
			
			if(top_comment)
			{
				top_comment.parentNode.insertBefore(new_element.firstChild, top_comment);
			}
			else
			{
				if(profile_block)
				{
					var placeholder = document.getElementById("comment_placeholder");
					placeholder.parentNode.insertBefore(new_element.firstChild, placeholder);
				}
				else if(delete_form)
				{
					delete_form.appendChild(new_element.firstChild);
				}
				else
				{
					conversation_wrapper.appendChild(new_element.firstChild);
				}
			}
			
			if(remove_bottom_comment)
			{
				YAHOO.util.Event.purgeElement(bottom_comment, true);
				bottom_comment.parentNode.removeChild(bottom_comment);
			}
			var new_comment = document.getElementById("user_comment_"+result_obj.comment_id);
			Overlord.summonMinions(new_comment);
		}
		
		if(!result_obj.success || current_page != 0)
		{
			
			YAHOO.comments.Editor.show_user_message(result_obj);
		}
		
		var spinner = document.getElementById("post_comment_spinner");
		var post_button = document.getElementById("comment_post_submit");
		var text_area = document.getElementById("comment_text");
		
		text_area.value = "Post a comment...";
		YAHOO.util.Event.addListener(text_area, "focus", YAHOO.comments.Editor.clear_input, this);
		YAHOO.util.Dom.setStyle(post_button, "display", "block");
		
		if(post_button && spinner)
		{
			YAHOO.util.Dom.setStyle(post_button, "display", "block");
			post_button.disabled = true;
			YAHOO.util.Dom.setStyle(spinner, "display", "none");
		}
		
		// Check to see if this is still needed. It might be unnecessary after the refactoring.
		//
		// This is a bit of a hack to fix a problem where the "color" menu of the enhanced text editor for
		// comments was cut off if there are not at least 2 comments. This is due to block_containers being
		// overflow: hidden in order to prevent content inside from throwing off the layout. In the case
		// of comments, each comment has an overflow: hidden property, so the property on the outer block,
		// while being extra careful, is not necessary. Thus, in this one case, we're resetting it with
		// the following Javascript.
		var container = YAHOO.util.Dom.getAncestorByClassName("comment_write", "block_container");
		if (container)
		{
			YAHOO.util.Dom.setStyle(container, "overflow", "visible");
		}
	},
	
	show_user_message: function(result_obj)
	{	
		var error_node = document.getElementById("comments_post_error");
		var success_node = document.getElementById("comments_post_success");
		if(error_node && !result_obj.success)
		{
			error_node.innerText = result_obj.error;
			return;
		}
		else if((error_node && result_obj.success) || (success_node && !result_obj.success))
		{
			var error_row = document.getElementById("comments_user_message_row");
			if(error_row)
			{
				error_row.parentNode.removeChild(error_row);
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
		if(!result_obj.success)
		{
			var text = document.createTextNode(result_obj.error);
			new_element.appendChild(text);
	
			new_element.id = "comments_post_error";
		}
		else
		{
			var link = document.createElement("a");
			link.href = Nexopia.JSONData["page_url"]+"?page=0";
			var text = document.createTextNode("Comment post successful. You can view your comment ");
			var link_text = document.createTextNode("here");
			link.appendChild(link_text);
			new_element.appendChild(text);
			new_element.appendChild(link);
			new_element.appendChild(document.createTextNode("."));
			
			new_element.id = "comments_post_success";
		}
		
		new_row.appendChild(left_col);
		new_row.appendChild(right_col);
		right_col.appendChild(new_element);
	
		var comments_row = document.getElementById("comments_editor_row");
	
		comments_row.parentNode.insertBefore(new_row, comments_row);
	},
	
	max_comment_id: function()
	{
		return Math.max.apply(Math, Nexopia.JSONData.comments_id_list);
	},
	
	min_comment_id: function()
	{
		return Math.min.apply(Math, Nexopia.JSONData.comments_id_list);
	}
};

Overlord.assign({
	minion: "comments:write",
	load: function(element) {
		YAHOO.comments.Editor.init();
	}
});