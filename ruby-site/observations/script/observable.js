Observables = {
	init: function() {
		var buttons = document.getElementsByName("remove_button");
		for (var i = 0; i < buttons.length; i++){
			var button = buttons[i];
			YAHOO.util.Event.on(button, "submit", function(e, button){
				YAHOO.util.Connect.setForm(button);
				YAHOO.util.Connect.asyncRequest('POST', button.attributes.action.value, {
					success: function(o) {
						var li = document.getElementById("list_" + this.id);
						li.parentNode.removeChild(li);
					},
					failure: function() {
						alert("Failure");
					},
					id: button.id.substr(3)
					
				}, 'ajax=true');
				YAHOO.util.Event.preventDefault(e);
			}, button);
		}
	}
}



GlobalRegistry.register_handler("observables", Observables.init, Observables, true);