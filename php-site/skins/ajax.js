function getHTTPObject() {
	var xmlhttp;

	/*@cc_on
		@if (@_jscript_version >= 5)
			try {
				xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
			} catch (e) {
			try {
				xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
				} catch (E) {
					xmlhttp = false;
				}
			}
		@else
			xmlhttp = false;
		@end @*/

	if (! xmlhttp && typeof XMLHttpRequest != 'undefined')
		try { xmlhttp = new XMLHttpRequest(); } catch (e) { xmlhttp = false; }

	return xmlhttp;
}

function Ajax (url, opts) {
	opts['method'] = (opts['method'] || 'get').toLowerCase();
	if (opts['method'] != 'get' && opts['method'] != 'post')
		opts['method'] = 'get';
	if (! opts['postdata'])
		opts['postdata'] = '';
	if (! opts['params'])
		opts['params'] = '';

	if (! opts['oncomplete'])
		opts['oncomplete'] = function () { alert('No oncomplete handler provided for ajax request. Request cancelled.'); };
	if (! opts['timeout'])
		opts['timeout'] = 0;
	if (! opts['ontimeout'])
		opts['ontimeout'] = function () { alert('No ontimeout handler provided for ajax request. Request cancelled.'); };

	this.url = url;
	this.opts = opts;
	this.headers = new Array();
	this.req = false;

	this.abort = function () {
		this.req.onreadystatechange = function () { };
		this.req.abort();
	};
	this.setHeaders = function (headers) {
		for (var header in headers)
			this.headers.push(header, headers[header]);
	};
	this.execute = function () {
		this.req = getHTTPObject();
		if (! this.req)
			return false;

		var theObj = this;
		this.req.onreadystatechange = function () {
			if (theObj.req.readyState == 4) {
				var funcComplete = theObj.opts['oncomplete'];
				funcComplete(theObj.req);
			}
		};

		if (this.opts['params'].length)
			url += (url.indexOf('?') == -1 ? '?' : '&') + this.opts['params'];

		this.req.open(this.opts['method'], url, true);

		if (opts['method'] == 'post') {
			this.headers.push('Content-type', 'application/x-www-form-urlencoded');

			if (this.req.overrideMimeType)
				this.headers.push('Connection', 'close');
		}

		if (this.headers.length) {
		    for (var i = 0; i < this.headers.length; i += 2)
				this.req.setRequestHeader(this.headers[i], this.headers[i + 1]);
		}

		this.req.send(this.opts['method'] == 'post' ? this.opts['postdata'] : null);
		return true;
	};
}

function cleanXML (root) {
	for (var i = 0; i < root.childNodes.length; i++) {
		var node = root.childNodes[i];

		if (node.nodeType == 1)
			cleanXML(node);
		else if (node.nodeType == 3 && !/\S/.test(node.nodeValue))
			node.parentNode.removeChild(node);
	}
}

function getValue (root) {
	var allText = root.nodeValue;

	for (var i = 1; i < root.parentNode.childNodes.length; i++) {
	var node = root.parentNode.childNodes[i];

		if (node.nodeType == 3)
			allText += node.nodeValue;
	}

	return allText;
}
