// require friends.js

Nexopia.Friends.Note = function(element) {
	this.editLink = element;
	this.root = this.editLink.parentNode.parentNode;
	this.note = YAHOO.util.Dom.getElementsByClassName('note', 'div', this.root)[0];
	this.form = this.root.getElementsByTagName('form')[0];
	this.text = YAHOO.util.Dom.getElementsByClassName("edit_notes", 'input', this.form)[0];
	YAHOO.util.Event.on(this.editLink, 'click', this.edit, this, true);
	YAHOO.util.Event.on(this.text, 'blur', this.save, this, true);
	YAHOO.util.Event.on(this.form, 'submit', this.save, this, true);
};

Nexopia.Friends.Note.prototype = {
	edit: function(event) {
		YAHOO.util.Event.preventDefault(event);
		YAHOO.util.Dom.setStyle(this.note, 'display', 'none');
		YAHOO.util.Dom.setStyle(this.editLink, 'display', 'none');
		YAHOO.util.Dom.setStyle(this.form, 'display', 'inline');
		YAHOO.util.Dom.setStyle(this.text, 'display', 'inline');
		this.text.focus();
		this.text.select();
	},
	save: function() {
		YAHOO.util.Dom.setStyle(this.text, 'display', 'none');
		YAHOO.util.Dom.setStyle(this.note, 'display', 'inline');
		YAHOO.util.Dom.setStyle(this.editLink, 'display', 'inline');
		this.note.innerHTML = "Updating...<img src=\"" + Site.staticFilesURL + "/Legacy/images/spinner.gif\"/>";
		YAHOO.util.Connect.setForm(this.form);
		that = this;
		YAHOO.util.Connect.asyncRequest('POST', this.form.action, {
			success: function() {
				that.note.innerHTML = that.text.value;
			},
			scope: this.element
		}, 'null=null');
	}
};

Overlord.assign({
	minion: "friends:notes:edit",
	load: function(element) {
		new Nexopia.Friends.Note(element);
	}
});

Overlord.assign({
	minion: "friends:notes:form",
    submit: function(event, element) {
        YAHOO.util.Event.preventDefault(event);
    }
});
