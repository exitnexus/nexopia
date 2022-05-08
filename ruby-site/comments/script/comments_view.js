if(YAHOO.comments == undefined){
	YAHOO.namespace("comments");
}

YAHOO.comments.View =
{
	init: function()
	{
		YAHOO.util.Event.addListener("select_all_comments", "click", YAHOO.comments.View.select_all_click);

		var root_element = document.getElementById("comments_profile_block");
		if(!root_element)
		{
			root_element = document.getElementById("comments_page");
		}
	},
	
	select_all_click: function(e)
	{
		var input_list = YAHOO.util.Dom.getElementsByClassName("comment_delete_input", "input", "delete_comments_form", function(obj){});
		
		for(var i=0; i < input_list.length; i++)
		{
			input_list[i].checked = e.target.checked;
		}
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
	
		var bottom_comment_id = YAHOO.comments.Editor.min_comment_id();	
		var separator = "";
		if(!(target.host.charAt(target.host.length-1) == "/") && !(target.pathname.charAt(0) == "/"))
		{
			separator = "/";
		}
		var delete_path = "http://"+target.host+separator+target.pathname+"/dynamic"+target.search+"&last_id="+bottom_comment_id;

		YAHOO.util.Connect.asyncRequest("GET", delete_path, new ResponseHandler({
			success: function(o) {

				YAHOO.comments.View.update_comments_view(o);
			},
			scope: this
		}), "");
		
	},
	
	update_comments_view: function(connection_obj)
	{
		var result_obj = YAHOO.lang.JSON.parse(connection_obj.responseText);

		if(result_obj.success)
		{
			var error_row = document.getElementById("comments_post_error_row");
			if(error_row)
			{
				error_row.parentNode.removeChild(error_row);
			}
			
			var bottom_comment_id = YAHOO.comments.Editor.min_comment_id();
			
			for(var i=0; i < Nexopia.JSONData.comments_id_list.length; i++)
			{
				if(Nexopia.JSONData.comments_id_list[i] == parseInt(result_obj.removed_comment_id, 10))
				{
					Nexopia.JSONData.comments_id_list[i] = result_obj.comment_id;
					break;
				}
			}
			if(result_obj.removed_comment_id == bottom_comment_id)
			{
				bottom_comment_id = YAHOO.comments.Editor.min_comment_id();
			}
			
			var del_comment_obj = document.getElementById("user_comment_" + result_obj.removed_comment_id);
			YAHOO.util.Event.purgeElement(del_comment_obj, true);
			del_comment_obj.parentNode.removeChild(del_comment_obj);
			
			var new_element = document.createElement("div");
			new_element.innerHTML = result_obj.comment_content;
			
			var delete_control = document.getElementById("select_all_comments");
			if(delete_control)
			{
				delete_control.parentNode.insertBefore(new_element.firstChild, delete_control);
			}
			else
			{
				var bottom_comment = document.getElementById("user_comment_" + bottom_comment_id);
				bottom_comment.parentNode.appendChild(new_element.firstChild);
			}
			
			var new_comment = document.getElementById("user_comment_"+result_obj.comment_id);
			Overlord.summonMinions(new_comment);
		}
	}
};

Overlord.assign({
	minion: "comments:view",
	load: function(element){
		YAHOO.comments.View.init();
	}
});

Overlord.assign({
	minion: "comments:quick_delete",
	load: function(element){
		YAHOO.util.Event.addListener(element, "click", YAHOO.comments.View.quick_delete);
	}
})