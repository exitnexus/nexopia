SecureForm = {
	getFormKey: function() {
		return escape(document.getElementsByName('form_key')[0].value);
	},
	getRawFormKey: function() {
		return document.getElementsByName('form_key')[0].value;
	}
};