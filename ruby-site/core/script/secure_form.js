SecureForm = {
	getFormKey: function() {
		var form_key = document.getElementsByName('form_key[]')[0];
		return encodeURIComponent(form_key.value);
	},
	getRawFormKey: function() {
		return document.getElementsByName('form_key[]')[0].value;
	}
};