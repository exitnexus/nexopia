Nexopia.Friends = {	
	init: function()
	{
		this.remove_friend_elements = [];
		this.add_friend_elements = [];
		this.reverse_remove_friend_elements = [];
		this.comment_elements = [];
		this.message_elements = [];
		this.online_elements = [];
		this.offline_elements = [];
		
		//find all the elements for each tooltip
		var add_friend_tooltip;
		var remove_friend_tooltip;
		var reverse_remove_friend_tooltip;
		var readd_friend_tooltip;
		var comment_tooltip;
		var message_tooltip;
		var online_tooltip;
		var offline_tooltip;
		
		var friends_list = YAHOO.util.Dom.getElementsByClassName("friend", "div", "friends_layout", null);
		
		var i;
		var temp;
		var friend_id_regex = /^friend_\d*$/;
		
		for(i=0;i<friends_list.length;i++)
		{
			temp = friends_list[i];
			if(!temp.id.match(friend_id_regex))
			{
				continue;
			}
			
			for(j=0; j<temp.childNodes.length; j++)
			{
				temp_child = temp.childNodes[j];
				
				if(YAHOO.util.Dom.hasClass(temp_child, "actions"))
				{
					Nexopia.Friends.setup_actions(temp_child);
				}
				else if(YAHOO.util.Dom.hasClass(temp_child, "pictures_div"))
				{
					Nexopia.Friends.setup_user_status(temp_child);
				}
			}
		}
		/*
		alert("Online: " + Nexopia.Friends.online_elements.length);
		alert("Offline: " + Nexopia.Friends.offline_elements.length);
		alert("Message: " + Nexopia.Friends.message_elements.length);
		alert("Comments: " + Nexopia.Friends.comment_elements.length);
		alert("Add Friend: " + Nexopia.Friends.add_friend_elements.length);
		alert("Remove Friend: " + Nexopia.Friends.remove_friend_elements.length);
		alert("Reverse Remove Friend: " + Nexopia.Friends.reverse_remove_friend_elements.length);
		*/
		add_friend_tooltip = new YAHOO.widget.Tooltip(document.createElement("div"), {context: Nexopia.Friends.add_friend_elements, text: "Add as Friend"});
		remove_friend_tooltip = new YAHOO.widget.Tooltip(document.createElement("div"), {context: Nexopia.Friends.remove_friend_elements, text: "Remove Friend"});
		reverse_remove_friend_tooltip = new YAHOO.widget.Tooltip(document.createElement("div"), {context: Nexopia.Friends.reverse_remove_friend_elements, text: "Remove from Friends list"});
		readd_friend_tooltip = new YAHOO.widget.Tooltip(document.createElement("div"), {context: [], text: "Add as Friend"});
		comment_tooltip = new YAHOO.widget.Tooltip(document.createElement("div"), {context: Nexopia.Friends.comment_elements, text: "Comment"});
		message_tooltip = new YAHOO.widget.Tooltip(document.createElement("div"), {context: Nexopia.Friends.message_elements, text: "Send Message"});
		online_tooltip = new YAHOO.widget.Tooltip(document.createElement("div"), {context: Nexopia.Friends.online_elements, text: "Online"});
		offline_tooltip = new YAHOO.widget.Tooltip(document.createElement("div"), {context: Nexopia.Friends.offline_elements, text: "Offline"});
	},
	
	setup_actions: function(el)
	{
		var remove_friend = YAHOO.util.Dom.getElementsByClassName("remove_friend", "a", el, null);
		var reverse_remove_friend = YAHOO.util.Dom.getElementsByClassName("reverse_remove_friend", "a", el, null);
		var add_friend = YAHOO.util.Dom.getElementsByClassName("add_friend", "a", el, null);
		var message = YAHOO.util.Dom.getElementsByClassName("friend_send_message", "a", el, null);
		var comment = YAHOO.util.Dom.getElementsByClassName("friend_comments", "a", el, null);
		
		if(remove_friend.length > 0)
		{
			Nexopia.Friends.remove_friend_elements.push(remove_friend[0]);
		}
		if(reverse_remove_friend.length > 0)
		{
			Nexopia.Friends.reverse_remove_friend_elements.push(reverse_remove_friend[0]);
		}
		if(add_friend.length > 0)
		{
			Nexopia.Friends.add_friend_elements.push(add_friend[0]);
		}
		if(message.length > 0)
		{
			Nexopia.Friends.message_elements.push(message[0]);
		}
		if(comment.length > 0)
		{
			Nexopia.Friends.comment_elements.push(comment[0]);
		}
	},
	
	setup_user_status: function(el)
	{
		var i;
		var j;
		
		for(i=0; i<el.childNodes.length; i++)
		{
			var temp = el.childNodes[i];
			if(YAHOO.util.Dom.hasClass(temp, "online_offline"))
			{
				for(j=0;j<temp.childNodes.length; j++)
				{
					if(YAHOO.util.Dom.hasClass(temp.childNodes[j], "friend_offline"))
					{
						Nexopia.Friends.offline_elements.push(temp.childNodes[j]);
					}
					else if(YAHOO.util.Dom.hasClass(temp.childNodes[j], "friend_online"))
					{
						Nexopia.Friends.online_elements.push(temp.childNodes[j]);
					}
				}
			}
		}
	}
};

Friends = function()
{
	
};



Friends.prototype =
{
	
};

Friends.AddLink = function(element, replacement_element) {
	this.element = element;
	this.replacement_element = replacement_element;
	YAHOO.util.Event.on(this.element, 'click', this.submit, this, true);
};

Friends.AddLink.prototype = {
	submit: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}

		var spinner = new Spinner({ context: [this.element, "tr"], offset: [-3,-4] , lazyload: true });
		this.element.style.visibility = "hidden";
		spinner.on();
		var that = this;
		
		YAHOO.util.Connect.asyncRequest('POST', this.element.href, new ResponseHandler({
			success: function() {
				spinner.off();
				if (that.replacement_element)
				{
					this.parentNode.replaceChild(that.replacement_element, that.element);					
					// init_custom_color_icons([that.replacement_element]);
				}
				else
				{
					this.parentNode.removeChild(this);
				}
			},
			failure: function() {
				spinner.off();
				this.style.visibility = "visible";
			},
			scope: this.element
		}), "ajax=true&form_key[]=" + Nexopia.json(this.element));
	}
};

Overlord.assign({
	minion: "friends:add_friend",
	load: function(element) {
		var replacement_img = document.createElement('img');
		YAHOO.util.Dom.addClass(replacement_img, "color_icon");
		replacement_img.src = Site.coloredImgURL(Nexopia.Utilities.deduceImgColor(element)) +"/friends/images/icon_friend_true.gif";
		new Friends.AddLink(element, replacement_img);
	},
	order: -1
});

Overlord.assign({
	minion: "friends:remove_friend",
	load: function(element) {
		
	},
    click: function(event, element) {
        YAHOO.util.Event.preventDefault(event);
		form_key = Nexopia.json(element);
		YAHOO.util.Connect.asyncRequest('POST', element.href, new ResponseHandler({}), "ajax=true&form_key[]=" + form_key);
    }
});

Overlord.assign({
	minion: "friends:reverse_remove_friend",
	load: function(element) {
		
	},

    click: function(event, element) {
		YAHOO.util.Event.preventDefault(event);
		form_key = Nexopia.json(element);
		YAHOO.util.Connect.asyncRequest('POST', element.href, new ResponseHandler({}), "ajax=true&form_key[]=" + form_key);
    }
});

Overlord.assign({
	minion: "friends:re_add_friend",
	load: function(element) {
		
	},

  click: function(event, element) {
  	YAHOO.util.Event.preventDefault(event);
		form_key = Nexopia.json(element);
		YAHOO.util.Connect.asyncRequest('POST', element.href, new ResponseHandler({}), "ajax=true&form_key[]=" + form_key);
	}
});

Overlord.assign({
	minion: "friends:truncated",
	load: function(element) {
		new Truncator(element, {
			height: 30,
			expandable: true,
			collapsible: true,
			suffix: "...&nbsp;<a href=''>[more]</a>",
			expandedSuffix: "&nbsp;<a href=''>[less]</a>",
			tooltip: false
		});
	}
});

Overlord.assign({
	minion: "friends:init_tooltips",
	load: function(element) {
		Nexopia.Friends.init();
	}
})