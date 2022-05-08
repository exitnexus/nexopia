FriendsOfFriends = {
	init: function() {
		var add = YAHOO.util.Dom.getElementsByClassName("add");
		for (var i=0;i<add.length;i++) {
			FriendsOfFriends.registerAjaxForm(add[i]);
			new ToolTip(add[i], {text:"Add as Friend"});
		}
		var hide = YAHOO.util.Dom.getElementsByClassName("hide");
		for (var i=0;i<hide.length;i++) {
			FriendsOfFriends.registerAjaxForm(hide[i]);
			new ToolTip(hide[i], {text:"Hide"});
		}
		var connected_friends = YAHOO.util.Dom.getElementsByClassName("connected_friend");
		for (var i=0;i<connected_friends.length;i++) {
			var friend = connected_friends[i];
			var name = friend.id.match(/picture_\d+_(.+)/)[1];
			new ToolTip(friend, {text:name});
		}
	},
	//Sets up functionality to do ajax form submission and then hide wrapping element
	registerAjaxForm: function(element) {
		YAHOO.util.Event.on(element, "click", function(e) {
			YAHOO.util.Event.preventDefault(e);
			element.innerHTML = "<img src='"+Site.staticFilesURL+"/Legacy/images/spinner.gif'";
			YAHOO.util.Connect.asyncRequest("post", element.href, {
				success: function() {
					var li = YAHOO.util.Dom.getAncestorByClassName(element, 'friend_list_entry');
					li.parentNode.removeChild(li);
				},
				scope: element
			}, "null=null");
		});
	}
};

GlobalRegistry.register_handler("friends_of_friends", FriendsOfFriends.init, FriendsOfFriends, true);