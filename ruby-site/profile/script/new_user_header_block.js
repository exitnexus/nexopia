if(YAHOO.profile == undefined){
	YAHOO.namespace ("profile");
}

YAHOO.profile.NewUserTaskBlock = {
	close_block: function(e)
	{
		if(e)
		{
			YAHOO.util.Event.preventDefault(e);
		}
		
		var close_form = document.getElementById("user_task_block_close");
		
		if(close_form == null)
		{
			return;
		}
		
		YAHOO.util.Connect.setForm(close_form);
		YAHOO.util.Connect.asyncRequest(close_form.method, close_form.action, new ResponseHandler({
			success: function(o) {
			},
			failure: function(o) {
			},
			scope: this
		}), "");
		
		var header_block = document.getElementById("new_user_header_block");
		
		if(header_block == null)
		{
			return;
		}
		
		header_block.parentNode.removeChild(header_block);
	}
};

Overlord.assign({
	minion: "profile:new_user_task_block_close",
	click: function(event, element){
		YAHOO.profile.NewUserTaskBlock.close_block(event);
	}
});