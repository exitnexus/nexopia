if(YAHOO.blog == undefined){
	YAHOO.namespace ("blog");
}

YAHOO.blog.BlogComments =
{
	/*
			This function contains all the fun for displaying the reply enhanced text editor. In addition to
			moving the editor around the page we also have to resize it to ensure it fits and resize the smilies
			tab so it scrolls properly. We also need to adjust the form action so it posts to the proper place 
			(and replies to the correct comment).
	*/
	show_reply_editor: function(e)
	{
		YAHOO.util.Event.preventDefault(e);
		
		var target = YAHOO.util.Event.getTarget(e);
		
		var editor = document.getElementById("blog_comment_editor");
		
		var id_parts = target.id.split("_");
		var comment_id = id_parts[3];
		
		var comment = document.getElementById("blog_comment_"+comment_id);
		
		var actual_comment = YAHOO.util.Dom.getElementsByClassName("single_comment_wrapper", "div", comment, function(e){})[0];

		//calculate the editor width. Use of magic number is needed. The picture is 100px wide plus 12px padding, thus the 112px.
		var comment_width = YAHOO.util.Dom.getStyle(comment, "width");
		var text_editor_width = parseInt(comment_width, 10) - 112;

		if( isNaN(text_editor_width) ) {
			text_editor_width = comment.offsetWidth - 112; 
		}

		//Reconfigure the form action path
		var base_url = YAHOO.util.Dom.get("blog_base_comment_url");
		var editor_form = document.getElementById("dynamic_blog_comment_form");
		base_url = base_url.value;		
		base_url = base_url +"/" + comment_id + "/submit";
		editor_form.action = base_url;
		
		var text_editor = YAHOO.util.Dom.getElementsByClassName("enhanced_text_box_wrapper", "div", editor_form, null)[0];
		
		//Reinitialize the smilies tab
		var smilies_container = YAHOO.util.Dom.getElementsByClassName("smilies", "div", "dynamic_blog_comment_form")[0];
		var editor_tab_body = smilies_container.parentNode;

		editor_tab_body.removeChild(smilies_container);
		
		var editor_obj = Nexopia.enhanced_text_editor_list['blog_comment_content'];
		editor_obj.text_box_width = text_editor_width;
		var pop = editor_obj.population();

		var new_smilies = editor_obj.build_smilies(pop[3].content[0], editor_tab_body);
		editor_tab_body.appendChild(new_smilies);
		
		//Move the editor back to the first tab and clear any input from the textarea
		editor_obj.activate_tab(0);
		editor_obj.text_box.value = "";

		editor.parentNode.removeChild(editor);

		YAHOO.util.Dom.insertAfter(editor, actual_comment);
		YAHOO.util.Dom.setStyle(editor, "display", "block");
		
		/*
			We have to resize the editor and the text field within for IE. If we don't the edges
			of the editor extend outside of the bounds of the container. It only gets worse the
			further the comment is indented.
		*/
		YAHOO.util.Dom.setStyle(editor, "width", comment_width);
		YAHOO.util.Dom.setStyle(text_editor, "width", text_editor_width + "px");		

	},
	
	post_comment: function(e)
	{
		if (e) {
			YAHOO.util.Event.preventDefault(e);
		}
		
		var editor_form = document.getElementById("dynamic_blog_comment_form");
		
		YAHOO.util.Connect.setForm(editor_form);
		YAHOO.util.Connect.asyncRequest(editor_form.method, editor_form.action + "/dynamic", new ResponseHandler({
			success: function(o) {
				YAHOO.blog.BlogComments.update_comments_view(o);
			},
			failure: function(o) {
				alert("Epic fail!");
			},
			scope: this
		}), "");
	},
	
	
	/*
		This function handles explicitly setting the width for blog comments. This prevents large images
		from spilling out and breaking the layout. This needs a javascript solution because the width of
		blog comments varies depending on their depth in the tree. As such a simple CSS fix won't do.
		
		We need to keep the CSS workaround in place for non javascript users. It's not pretty, but it
		makes the blog comments still usable.
	*/
	handle_comment_overflow: function(root_element)
	{
		var i;
		var temp;
		
		for(i=0; i < root_element.childNodes.length; i++)
		{
			temp = root_element.childNodes[i];
			YAHOO.blog.BlogComments.set_element_width(temp, 0);
		}
	},

	set_element_width: function(element, depth)
	{
		var i;
		var comment_regex = /^blog_comment_\d*$/;
		var temp;
		var temp_width;
		var max_width = YAHOO.blog.BlogComments.blog_comment_width();
		var comment_indent = 15;
		
		if(element.id && element.id.match(comment_regex))
		{
			/*
				We use the max width as the actual width of one of the blog_container elements. It's the nearest
				parent element to the blog comment with a specified width. Once we grab that we can figure out
				the width of the blog comment. Knowing this allows us to use static values for the widths of the
				comment content and comment body elements. We know they are 122px and 128px smaller, respectively.
				
				We could try to figure out dynamically the amount those elements are smaller, however this would be 
				difficult as we'd have to try to get all the margins, paddings, and borders accounted for in all 
				of the relevant parent elements prior to calculating the width.
				
				With that being an expensive task, this function will have to be manually updated if we change the
				display of blog comments.
			*/
			temp_width = max_width-depth*comment_indent;
			YAHOO.util.Dom.setStyle(element, "width", temp_width +"px");
			YAHOO.util.Dom.setStyle(element, "overflow", "hidden");
			
			var content = YAHOO.util.Dom.getElementsByClassName("comment_content", "div", element, null)[0];
			var content_width = max_width - 122 - depth*comment_indent;
			YAHOO.util.Dom.setStyle(content, "width", content_width + "px");
			
			var comment_body = YAHOO.util.Dom.getElementsByClassName("comment_body", "div", content, null)[0];
			var body_width = max_width - 128 - depth*comment_indent;
			YAHOO.util.Dom.setStyle(comment_body, "width", body_width + "px");
			
			for(i=0; i < element.childNodes.length; i++)
			{
				temp = element.childNodes[i];
				YAHOO.blog.BlogComments.set_element_width(temp, depth+1);
			}
		}
	},
		
	blog_comment_width: function()
	{
		if(!YAHOO.blog.BlogComments.blog_width)
		{
			var temp_list = YAHOO.util.Dom.getElementsByClassName("blog_container", "div", "blog", null);
		
			if(temp_list.length > 0)
			{
				YAHOO.blog.BlogComments.blog_width = parseInt(YAHOO.util.Dom.getStyle(temp_list[0], "width"), 10);
			
				if(!YAHOO.blog.BlogComments.blog_width)
				{
					YAHOO.blog.BlogComments.blog_width = 738;
				}
			}
			else
			{
				YAHOO.blog.BlogComments.blog_width = 738;
			}
		}
		
		return YAHOO.blog.BlogComments.blog_width;
	}
};

Overlord.assign({
	minion: "blog:comment_reply",
	load: function(element) {
		YAHOO.util.Event.addListener(element, "click", YAHOO.blog.BlogComments.show_reply_editor);
	}
});

Overlord.assign({
	minion: "blog_comments:select_all",
	click: function(event, element) {
		YAHOO.util.Event.preventDefault(event);
		var elements = YAHOO.util.Dom.getElementsByClassName('blog_comment_delete_input', 'input', null, function(checkbox) {checkbox.checked=true;});
		if (elements.length != 0 && YAHOO.blog.BlogComments.deleteBtn)
		{
			YAHOO.blog.BlogComments.deleteBtn.set("disabled", false);
		}
	}
});

Overlord.assign({
	minion: "blog_comments:select_none",
	click: function(event, element){
		YAHOO.util.Event.preventDefault(event);
		var elements = YAHOO.util.Dom.getElementsByClassName('blog_comment_delete_input', 'input', null, function(checkbox) {checkbox.checked=false;});
		if (elements.length != 0 && YAHOO.blog.BlogComments.deleteBtn)
		{
			YAHOO.blog.BlogComments.deleteBtn.set("disabled", true);
		}
	}
});

Overlord.assign({
	minion: "blog_comments:comment_functions",
	load: function(element){
		YAHOO.blog.BlogComments.deleteBtn = new YAHOO.widget.Button("delete_btn", {disabled:true});
	}
});

Overlord.assign({
	minion: "blog_comments:post_select",
	click: function(element){
		var disable = true;
		var postSelectElements = YAHOO.util.Dom.getElementsByClassName("blog_comment_delete_input", "input", "blog_comments_delete_form");
		for(var i = 0; i < postSelectElements.length; i++)
		{
			if (postSelectElements[i].checked)
			{
				disable = false;
				break;
			}
		}
		
		if (YAHOO.blog.BlogComments.deleteBtn)
		{
			YAHOO.blog.BlogComments.deleteBtn.set("disabled", disable);
		}
	}
});

Overlord.assign({
	minion: "blog_comments:dynamic_comment_reply_editor",
	order: 10,
	load: function(element)
	{
		YAHOO.util.Dom.setStyle(element, "display", "none");
	}
});

Overlord.assign({
	minion: "blog_comments:comment_overflow",
	load: function(element)
	{
		YAHOO.blog.BlogComments.handle_comment_overflow(element);
	}
});
