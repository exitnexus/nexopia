if (typeof DOMParser == "undefined") {
   DOMParser = function () {};

   DOMParser.prototype.parseFromString = function (str, contentType) {
      if (typeof ActiveXObject != "undefined") {
         var d = new ActiveXObject("MSXML2.DomDocument");
         d.loadXML(str);
         return d;
	  }
	  else if (typeof XMLHttpRequest != "undefined") {
         var req = new XMLHttpRequest;
         req.open("GET", "data:" + (contentType || "application/xml") + ";charset=utf-8," + encodeURIComponent(str), false);
         if (req.overrideMimeType)
            req.overrideMimeType(contentType);
         req.send(null);
         return req.responseXML;
      }
   }
}

function getXMLHttp () {
	var funcs = [
		function () { return new XMLHttpRequest(); },
		function () { return new ActiveXObject('Msxml2.XMLHTTP'); },
		function () { return new ActiveXObject('Microsoft.XMLHTTP'); }
	];

	var ret = false;

	for (var func in funcs) {
		var lambda = funcs[func];
		try {
			ret = lambda();
			break;
		}
		catch (e) {
			ret = false;
		}
	}

	return ret;
}

function Ajax (url, opts) {
	opts['method'] = (opts['method'] || 'get').toLowerCase();
	if (opts['method'] != 'get' && opts['method'] != 'post') {
		alert('Ajax(): invalid request method "' + opts['method'] + '", defaulting to "get".');
		opts['method'] = 'get';
	}

	if (! opts['postdata'])
		opts['postdata'] = '';

	if (! opts['params'])
		opts['params'] = '';

	if (! opts['oncomplete'])
		opts['oncomplete'] = function () { alert('No oncomplete handler provided for ajax request.'); };

	if (! opts['timeout'])
		opts['timeout'] = 0;

	if (! opts['ontimeout'])
		opts['ontimeout'] = function () { alert('No ontimeout handler provided for ajax request.'); };

	this.url = url;
	this.opts = opts;
	this.headers = {};
	this.req = false;
	this.tmrTimeout = null;

	this.abort = function () {
		this.req.onreadystatechange = function () { };
		this.req.abort();
	};

	this.setHeaders = function (headers) {
		for (var header in headers)
			this.headers[header] = headers[header];
	};

	this.execute = function () {
		this.req = getXMLHttp();
		if (! this.req)
			return false;

		var theObj = this;
		this.req.onreadystatechange = function () {
			if (theObj.req.readyState == 4) {
				clearTimeout(theObj.tmrTimeout);
				theObj.tmrTimeout = null;

				var funcComplete = theObj.opts['oncomplete'];
				funcComplete(theObj.req);
			}
		};

		if (this.opts['params'].length)
			url += (url.indexOf('?') == -1 ? '?' : '&') + this.opts['params'];

		this.req.open(this.opts['method'], url, true);

		if (opts['method'] == 'post') {
			this.setHeaders( {'Content-type': 'application/x-www-form-urlencoded'} );

			if (this.req.overrideMimeType)
				this.setHeaders( { 'Connection': 'close' } );
		}

		for (var header in this.headers)
			this.req.setRequestHeader(header, this.headers[header]);

		this.req.send(this.opts['method'] == 'post' ? this.opts['postdata'] : null);

		if (this.opts['timeout'] > 0) {
			var theObj2 = this;
			this.tmrTimeout = setTimeout(function () {
				theObj2.abort();
				theObj2.tmrTimeout = null;

				var lambda = theObj2.opts['ontimeout'];
				lambda();
			}, this.opts['timeout']);
		}

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
