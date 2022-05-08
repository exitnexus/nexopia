FriendFinder = {
	init: function() {
		YAHOO.util.Dom.getElementsByClassName('more_info', null, 'email_import', function(element) {
			new Revealer(element, null, {toggleInnerHTML: ""});
		});
		this.lastEmailRow = YAHOO.util.Dom.get('search_email_row_1');
		this.newEmailRow = this.lastEmailRow.cloneNode(true);
		YAHOO.util.Event.on('add_email_row', 'click', this.addEmailRow, this, true);
		YAHOO.util.Event.on('remove_email_row_1', 'click', this.removeEmailRow, this, true);
	},
	addEmailRow: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		
		
		var row_id_parts = (this.lastEmailRow.attributes['id'].value).split('_');
		var id_number = row_id_parts[3];
		
		id_number = parseInt(id_number) + 1;
		
		var new_row  = this.newEmailRow.cloneNode(true);
		new_row.attributes['id'].value = ("search_email_row").concat("_", id_number);
		new_row.value = "";
		var column = YAHOO.util.Dom.getLastChild(new_row);
		var link = YAHOO.util.Dom.getLastChild(column);
		
		link.attributes['id'].value = "remove_email_row_".concat(id_number);
		this.lastEmailRow = this.lastEmailRow.parentNode.insertBefore(new_row, this.lastEmailRow.nextSibling);
		YAHOO.util.Event.on("remove_email_row_".concat(id_number), 'click', this.removeEmailRow, this, true);
	},
	removeEmailRow: function(event)	{
		
		if(event)
		{
			YAHOO.util.Event.preventDefault(event);
		}
		var target = YAHOO.util.Event.getTarget(event, true);
		
		var target_id_parts = target.attributes['id'].value.split('_');
		var id_number = target_id_parts[3];
		var search_row = YAHOO.util.Dom.get("search_email_row_".concat(id_number));
		if(search_row.attributes['id'].value == this.lastEmailRow.attributes['id'].value){
			this.lastEmailRow = search_row.previousSibling;
		}
		search_row.parentNode.removeChild(search_row);
	}
};

GlobalRegistry.register_handler('email_import', FriendFinder.init, FriendFinder, true);