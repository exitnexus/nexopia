function plus_plus_expand_payment(which) {
	var types = new Array('mobile', 'land', 'interac', 'credit', 'mail');
	for (i in types) {
		var expanded = document.getElementById(types[i] + '_expanded');
		var elem = document.getElementById(types[i]);
		if ((types[i] == which) && (expanded.value == '0')) {
			elem.className = 'expanded';
			elem = document.getElementById(types[i] + '_graphic_max');
			YAHOO.util.Dom.setStyle(elem, 'display', 'block');
			elem = document.getElementById(types[i] + '_graphic_min');
			YAHOO.util.Dom.setStyle(elem, 'display', 'none');
			expanded.value = '1';
		} else {
			elem.className = 'hidden';
			elem = document.getElementById(types[i] + '_graphic_max');
			YAHOO.util.Dom.setStyle(elem, 'display', 'none');
			elem = document.getElementById(types[i] + '_graphic_min');
			YAHOO.util.Dom.setStyle(elem, 'display', 'block');
			expanded.value = '0';
		}
	}
}

function plus_plus_update_total(which) {
	var total = 0;
	var usernames = document.getElementsByName('username_' + which);
	var elems = document.getElementsByName('select_' + which);
	for (var i = 0; i < elems.length; i++) {
		var username = usernames[i].value;
		if (username == '') continue;
		var elem = elems[i];
		var value = parseInt(elem.options[elem.selectedIndex].value);
		total += value;
	}
	var totalElem = document.getElementById('total_' + which);
	totalElem.innerHTML = total;
}

function plus_plus_update_all_totals() {
	var types = new Array('land', 'interac', 'credit', 'mail', 'pluspin');
	for (i in types) {
		plus_plus_update_total(types[i]);
	}
	
}

Overlord.assign({
	minion: "plus:plus:mobile",
	click: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}

		plus_plus_expand_payment('mobile');
	}
});

Overlord.assign({
	minion: "plus:plus:land",
	click: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}

		plus_plus_expand_payment('land');
	}
});

Overlord.assign({
	minion: "plus:plus:interac",
	click: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}

		plus_plus_expand_payment('interac');
	}
});

Overlord.assign({
	minion: "plus:plus:credit",
	click: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}

		plus_plus_expand_payment('credit');
	}
});

Overlord.assign({
	minion: "plus:plus:mail",
	click: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}

		plus_plus_expand_payment('mail');
	}
});

// We allow user to add an unlimited number of usernames to
// a section (such as pay-by-Interac).  This function gets
// the last number, essentially the count of usernames.
function plus_plus_get_last(which) {
	var last = 1;
	var elem = null;
	do {
		elem = document.getElementById(which + '_option_' + last);
		if (elem != null) {
			last++;
		}
	} while (elem != null);
	return last - 1;
}

Overlord.assign({
	minion: "plus:plus:interac_add",
	click: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		
		var last = plus_plus_get_last('interac') + 1;
		if (last > 32) {
			alert('Too many usernames!');
			return;
		}
		var newElem = document.createElement('div');
		newElem.className = 'interac_option';
		newElem.id = 'interac_option_' + last;
		var innerHTML =
		 	document.getElementById('interac_option_1').innerHTML;
		newElem.innerHTML = innerHTML;
		// Next two lines work around IE problems
		newElem.getElementsByTagName('input')[0].value = '';
		newElem.getElementsByTagName('input')[1].value = 'Username';
		newElem.getElementsByTagName('select')[0].selectedIndex = 0;
		var appendTo = document.getElementById('interac_options');
		appendTo.appendChild(newElem);
		Overlord.summonMinions(newElem);
	}
});

Overlord.assign({
	minion: "plus:plus:credit_add",
	click: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		
		var last = plus_plus_get_last('credit') + 1;
		if (last > 32) {
			alert('Too many usernames!');
			return;
		}
		var newElem = document.createElement('div');
		newElem.className = 'credit_option';
		newElem.id = 'credit_option_' + last;
		var innerHTML =
		 	document.getElementById('credit_option_1').innerHTML;
		newElem.innerHTML = innerHTML;
		// Next two lines work around IE problems
		newElem.getElementsByTagName('input')[0].value = '';
		newElem.getElementsByTagName('input')[1].value = 'Username';
		newElem.getElementsByTagName('select')[0].selectedIndex = 0;
		var appendTo = document.getElementById('credit_options');
		appendTo.appendChild(newElem);
		Overlord.summonMinions(newElem);
	}
});

Overlord.assign({
	minion: "plus:plus:mail_add",
	click: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		
		var last = plus_plus_get_last('mail') + 1;
		if (last > 32) {
			alert('Too many usernames!');
			return;
		}
		var newElem = document.createElement('div');
		newElem.className = 'mail_option';
		newElem.id = 'mail_option_' + last;
		var innerHTML =
		 	document.getElementById('mail_option_1').innerHTML;
		newElem.innerHTML = innerHTML;
		// Next two lines work around IE problems
		newElem.getElementsByTagName('input')[0].value = '';
		newElem.getElementsByTagName('input')[1].value = 'Username';
		newElem.getElementsByTagName('select')[0].selectedIndex = 0;
		var appendTo = document.getElementById('mail_options');
		appendTo.appendChild(newElem);
		Overlord.summonMinions(newElem);
	}
});

Overlord.assign({
	minion: "plus:plus:pluspin_add",
	click: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		
		var last = plus_plus_get_last('pluspin') + 1;
		if (last > 32) {
			alert('Too many usernames!');
			return;
		}
		var newElem = document.createElement('div');
		newElem.className = 'pluspin_option';
		newElem.id = 'pluspin_option_' + last;
		var innerHTML =
		 	document.getElementById('pluspin_option_1').innerHTML;
		newElem.innerHTML = innerHTML;
		// Next two lines work around IE problems
		newElem.getElementsByTagName('input')[0].value = '';
		newElem.getElementsByTagName('input')[1].value = 'Username';
		newElem.getElementsByTagName('select')[0].selectedIndex = 0;
		var appendTo = document.getElementById('pluspin_options');
		appendTo.appendChild(newElem);
		Overlord.summonMinions(newElem);
	}
});

Overlord.assign({
	minion: "plus:plus:pluspin_add_pin",
	click: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		
		var next_id = 1;
		var elem = null;
		do {
			elem = document.getElementById('pin_value_' + next_id);
			if (elem != null) {
				next_id++;
			}
		} while (elem != null);
		if (next_id > 32) {
			alert('Too many plus pins!');
			return;
		}

		var newElem = document.createElement('div');
		newElem.id = 'pin_' + next_id;
		var appendTo = document.getElementById('pluspin_pins');
		appendTo.appendChild(newElem);
		
		// Call Ruby to add the contents.  We already had the code to
		// validate the pin, so this was the easiest approach.
		var form_key = document.getElementsByName("form_key[]");
		var postData = "form_key=" + form_key[0].value +
		 	"&which=" + next_id;
		YAHOO.util.Connect.asyncRequest("POST",
			'plus/add_pluspin', new ResponseHandler({}),
			postData);
	}
});

Overlord.assign({
	minion: "plus:plus:username",
	focus: function() {
		if (this.value == 'Username') this.value = '';
		if (this.value == 'Invalid Username') this.value = '';
	}
});

Overlord.assign({
	minion: "plus:plus:username",
	blur: function(event) {
		var value = this.value;
		value = value.replace(/^\s+/g, ''); // strip leading
		value = value.replace(/\s+$/g, ''); // strip trailing
		if (value != '') {
			this.value = value;
			// Validate username
			var form_key = document.getElementsByName("form_key[]");
			var postData = "form_key=" + form_key[0].value +
			 	"&validator=" + encodeURIComponent(value);
			YAHOO.util.Connect.asyncRequest("POST",
				'plus/check_username', 
				{ success:function(o) {
					if (o.responseText == '') {
						o.argument[0].value = 'Invalid Username';
					} else {
						var text = o.responseText;
						var colon = text.lastIndexOf(':');
						var username = text.substring(0, colon);
						var escaped = text.substring(colon + 1);
						o.argument[0].value = username;
						var elem;
						var elems = o.argument[0].parentNode.getElementsByTagName('input');
						elems[0].value = escaped;
					}
					plus_plus_update_all_totals();
				},
				  failure:function(o) {
					o.argument[0].value = 'Invalid Username';
					plus_plus_update_all_totals();
				},
				  argument:[this] },
				postData);
		} else {
			this.value = 'Username';
		}
	}
});

Overlord.assign({
	minion: "plus:plus:select_land",
	change: function() {
		plus_plus_update_total('land');
	}
});

Overlord.assign({
	minion: "plus:plus:select_interac",
	change: function() {
		plus_plus_update_total('interac');
	}
});

Overlord.assign({
	minion: "plus:plus:select_credit",
	change: function() {
		plus_plus_update_total('credit');
	}
});

Overlord.assign({
	minion: "plus:plus:select_mail",
	change: function() {
		plus_plus_update_total('mail');
	}
});

Overlord.assign({
	minion: "plus:plus:select_pluspin",
	change: function() {
		plus_plus_update_total('pluspin');
	}
});

function plus_plus_validate(which) {
	var usernames = document.getElementsByName('username_' + which);
	var amts = document.getElementsByName('select_' + which);
	var okay = false;
	for (var i = 0; i < usernames.length; i++) {
		if (usernames[i].value == '') continue;
		var value = amts[i].options[amts[i].selectedIndex].value;
		if (value != '0') {
			okay = true;
			break;
		}
	}
	var total = document.getElementById('total_' + which);
	if (total.innerHTML == '0') okay = false;
	if (okay) {
		return true;
	} else {
		alert("Please select valid users and amounts");
		return false;
	}
}

// Convert our data into the format that PHP expects, then submit it.
function plus_plus_submit(which, paymentmethod) {
	if (!plus_plus_validate(which)) return false;
	
	var postData = '?paymentmethod=' + paymentmethod + '&action=Complete';
	var usernames = document.getElementsByName('username_' + which);
	var amts = document.getElementsByName('select_' + which);
	for (var i = 0; i < amts.length; i++) {
		if (usernames[i].value == '') continue;
		var value = amts[i].options[amts[i].selectedIndex].value;
		if (value != '0') {
			postData += '&user[]=' + usernames[i].value;
			postData += '&amount[]=' + amts[i].value;
		}
	}
	
	var pins = document.getElementsByName('pins');
	var pin_values = document.getElementsByName('pin_values');
	for (var i = 0; i < pins.length; i++) {
		if (pins[i].id == 'secret_0') continue;
		var value = pin_values[i].value;
		if (value != '0') {
			postData += "&voucher[]=" + encodeURIComponent(pins[i].value);
		}
	}

	// Okay, submit back through to php side
	var form = document.getElementById('plus_form');
	form.action += postData; // Should possibly POST this instead of GET it
	form.submit();
}

Overlord.assign({
	minion: "plus:plus:buy_interac",
	click: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		
		plus_plus_submit('interac', 'moneris');
	}
});

Overlord.assign({
	minion: "plus:plus:buy_credit",
	click: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		
		plus_plus_submit('credit', 'credit');
	}
});

Overlord.assign({
	minion: "plus:plus:buy_mail",
	click: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		
		plus_plus_submit('mail', 'mail');
	}
});

Overlord.assign({
	minion: "plus:plus:buy_mobile",
	click: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		
		// Check that they specified a valid username
		var usernames = document.getElementsByName('username_mobile');
		var okay = false;
		for (var i = 0; i < usernames.length; i++) {
			if (usernames[i].value == '') continue;
			okay = true;
			break;
		}
		if (!okay) {
			alert("Please select a valid user name");
			return false;
		}

		var form = document.getElementById('plus_form');
		form.action = '/plus/pp_mobile_start';
		form.submit();
	}
});

Overlord.assign({
	minion: "plus:plus:buy_land",
	click: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		
		if (!plus_plus_validate('land')) return;
		var form = document.getElementById('plus_form');
		form.action = '/plus/pp_phone_start';
		form.submit();
	}
});

Overlord.assign({
	minion: "plus:plus:buy_pluspin",
	click: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		
		if (!plus_plus_validate('pluspin')) return;
		var pin_total = document.getElementById('pin_total');
		var total_pluspin = document.getElementById('total_pluspin');
		if (pin_total.innerHTML != total_pluspin.innerHTML) {
			alert("Total amount of Plus Pins and\ntotal amount to users do not match");
			return;
		}
		
		plus_plus_submit('pluspin', 'voucher');
	}
});

Overlord.assign({
	minion: "plus:plus:buy_voucher",
	click: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		
		var value =
		 	parseInt(document.getElementById('pin_value_0').value);
		if (value != 2) {
			alert('Please enter valid voucher');
			return;
		}

		// Don't call plus_plus_submit because Php side uses
		// action=Activate instead of action=Complete, and we have
		// to do other things a bit differently as well.
		var postData = '?action=Activate';
		var secret = document.getElementById('secret_0').value;
		postData += '&freevoucher=' + encodeURIComponent(secret);

		// Okay, submit back through to php side
		var form = document.getElementById('plus_form');
		form.action += postData; // Should possibly POST this instead of GET it
		form.submit();
	}
});

function plus_plus_update_pluspin_amt() {
	var total = 0;
	
	var elems = document.getElementsByName('pin_values');
	for (var i = 0; i < elems.length; i++) {
		if (elems[i].id == 'pin_value_0') continue;
		total += parseFloat(elems[i].value);
	}
	var totalElem = document.getElementById('pin_total');
	totalElem.innerHTML = parseInt(total);
}

Overlord.assign({
	minion: "plus:pluspin:pin",
	blur: function(event) {
		var value = this.value;
		if ((value == 'Invalid PIN') || (value == '')) return;
		this.value = 'Validating...';
		// Validate pin
		var which = this.parentNode.id.split('_')[1];
		var form_key = document.getElementsByName("form_key[]");
		var postData = "form_key=" + form_key[0].value;
		postData += "&validator=" + encodeURIComponent(value);
		postData += "&which=" + which;
		// Add all the other plus pins
		var i = 1;
		do {
			var elem = document.getElementById('secret_' + i);
			if (elem != null) {
				var value = elem.value;
				postData += "&otherpins[]=" + encodeURIComponent(value);
				i++;
			}
		} while (elem != null);

		var that = this.parentNode;
		YAHOO.util.Connect.asyncRequest("POST",
			'plus/check_pluspin', new ResponseHandler({
			success:function(o) {
				var pin_value = parseInt(document.getElementById('pin_value_' + which).value);
				if ((pin_value == 2) && (which > 0)) {
					// Free voucher
					var elem = document.getElementById('pin_0');
					elem.className = 'expanded';
					elem = document.getElementById('pluspin_pins_container');
					elem.className = 'hidden';
					document.getElementById('secret_0').value = document.getElementById('secret_' + which).value;
					document.getElementById('pin_value_0').value = document.getElementById('pin_value_' + which).value;
				} else if ((pin_value > 2) && (which == 0)) {
					// Not a free voucher
					var elem = document.getElementById('pin_0');
					elem.className = 'hidden';
					elem = document.getElementById('pluspin_pins_container');
					elem.className = 'expanded';
					document.getElementById('secret_1').value = document.getElementById('secret_0').value;
					document.getElementById('pin_value_1').value = document.getElementById('pin_value_0').value;
				}
				plus_plus_update_pluspin_amt();
			},
			failure: function() {
				this.parentNode.removeChild(this);
				plus_plus_update_pluspin_amt();
			},
			scope: this
			}), postData);
	}
});

Overlord.assign({
	minion: "plus:pp_validation_fail:retry_phone",
	click: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		
		var form = document.getElementById('form');
		form.submit();
	}
});

