if(YAHOO.blog == undefined){
	YAHOO.namespace ("blog");
}

/*
	This is needed for the blog views count. It get inclued on a few blog pages when the
	 blog user account has been view rate limited. It's included from view_blog, view_selected_entries and view_blog_post.
*/
YAHOO.blog.BlogViewCount =
{
	init: function(request_form)
	{
		if(request_form.tagName.toLowerCase() == "form" && request_form.id == "request_blog_view")
		{
			YAHOO.util.Connect.setForm(request_form);
			YAHOO.util.Connect.asyncRequest(request_form.method, request_form.action, {
				success: function(o) {
				},
				failure: function(o) {
				},
				scope: this
			}, "");
		}
	}
};

Overlord.assign({
	minion: "request_blog_view",
	load: function(element) {
		YAHOO.profile.BlogViewCount.init(element);
	}
});