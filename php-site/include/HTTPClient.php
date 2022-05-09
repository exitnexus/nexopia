<?php
/*

   Net_HTTP_Client class
	
@DESCRIPTION 

   HTTP Client component
	suppots methods HEAD, GET, POST
	1.0 and 1.1 compliant
	WebDAV methods tested against Apache/mod_dav
	Documentation @ http://lwest.free.fr/doc/php/lib/net_http_client-en.html
	
@SYNOPSIS

	include "Net/HTTP/Client.php";

	$http = new Net_HTTP_Client();
	$http->connect( "localhost", 80 ) or die( "connect problem" );
	$status = $http->get( "/index.html" );
	if( $status != 200 )
		die( "Problem : " . $http->getStatusMessage() . "\n" );
	$http->disconnect();

	

@CHANGES
	0.1 initial version
	0.2 documentation completed
	    + getHeaders(), getBody()
		 o Post(), Connect()
	0.3 DAV enhancements:
		 + Put() method
	0.4 continued DAV support 
		 + Delete(), Move(), MkCol(), Propfind()  methods
		 o added url property, remove host and port properties
		 o Connect, Net_HTTP_Client (use of this.url)
		 o processBody() : use non-blocking to fix a socket pblm
	0.5 debug support
		 + setDebug()
		 + debug levels definitions (DBG*)
	0.6 + Lock() method
		 + setCredentials() method and fix - thanks Thomas Olsen
		 + support for Get( full_url )
		 o fix POST call (duplicate content-length) - thanks to Javier Sixto
	0.7 + OPTIONS method support
	    + addCookie and removeCookies methods
	    o fix the "0" problem
	    o undeifned variable warning fixed
	    
	
@VERSION
	0.7
	
@INFORMATIONS

 Compatibility : PHP 4 >= 4.0b4
		 created : May 2001
  LastModified : Sep 2002


@AUTHOR
	Leo West <west_leo@yahoo-REMOVE-.com>

@TODO
	remaining WebDAV methods: UNLOCK PROPPATCH
	
	
*/


/// debug levels , use it as Client::setDebug( DBGSOCK & DBGTRACE )
define( "DBGTRACE", 1 ); // to debug methods calls
define( "DBGINDATA", 2 ); // to debug data received
define( "DBGOUTDATA", 4 ); // to debug data sent
define( "DBGLOW", 8 ); // to debug low-level (usually internal) methods
define( "DBGSOCK", 16 ); // to debug socket-level code

/// internal errors
define( "ECONNECTION", -1 ); // connection failed
define( "EBADRESPONSE", -2 ); // response status line is not http compliant

define( "CRLF", "\r\n" );


class Net_HTTP_Client 
{

	// @private
	/// array containg server URL, similar to array returned by parseurl()
	public $url; 
	/// server response code eg. "304"
	public $reply;
	/// server response line eg. "200 OK"
	public $replyString;
	/// HTPP protocol version used
	public $protocolVersion = "1.0";	
	/// internal buffers
	public $requestHeaders, $requestBody;
	/// TCP socket identifier
	public $socket = false;
	/// proxy informations
	public $useProxy = false;
	public $proxyHost, $proxyPort;
	/// debugging flag
	public $debug = 0;
		
	/**
	 * Net_HTTP_Client
	 * constructor
	 * Note : when host and port are defined, the connection is immediate
	 * @seeAlso connect
	 **/	
	function Net_HTTP_Client( $host= NULL, $port= NULL )
	{
		if( $this->debug & DBGTRACE ) echo "Net_HTTP_Client( $host, $port )\n";
			
		if( $host != NULL ) {
			$this->connect( $host, $port );
		}
	}

	/**
	 * turn on debug messages
	 * @param level a combinaison of debug flags
	 * @see debug flags ( DBG..) defined at top of file
	 **/	
	function setDebug( $level )
	{
		if( $this->debug & DBGTRACE ) echo "setDebug( $level )\n";
		$this->debug = $level;
	}
	
		
	/**
	 * turn on proxy support
	 * @param proxyHost proxy host address eg "proxy.mycorp.com"
	 * @param proxyPort proxy port usually 80 or 8080
	 **/	
	function setProxy( $proxyHost, $proxyPort )
	{
		if( $this->debug & DBGTRACE ) echo "setProxy( $proxyHost, $proxyPort )\n";
		$this->useProxy = true;
		$this->proxyHost = $proxyHost;
		$this->proxyPort = $proxyPort;
	}
	
		
	/**
	 * setProtocolVersion
	 * define the HTTP protocol version to use
	 *	@param version string the version number with one decimal: "0.9", "1.0", "1.1"
	 * when using 1.1, you MUST set the mandatory headers "Host"
	 * @return boolean false if the version number is bad, true if ok
	 **/	
	function setProtocolVersion( $version )
	{
		if( $this->debug & DBGTRACE ) echo "setProtocolVersion( $version )\n";
			
		if( $version > 0 and $version <= 1.1 ) {
			$this->protocolVersion = $version;
			return true;
		} else {
			return false;
		}
	}

	/**
	 * set a username and password to access a protected resource
	 * Only "Basic" authentication scheme is supported yet
	 *	@param username string - identifier
	 *	@param password string - clear password
	 **/
	function setCredentials( $username, $password )
	{
		$hdrvalue = base64_encode( "$username:$password" );
		$this->addHeader( "Authorization", "Basic $hdrvalue" );
	}
	
	/**
	 * define a set of HTTP headers to be sent to the server
	 * header names are lowercased to avoid duplicated headers
	 *	@param headers hash array containing the headers as headerName => headerValue pairs
	 **/	
	function setHeaders( $headers )
	{
		if( $this->debug & DBGTRACE ) echo "setHeaders( $headers ) \n";
		if( is_array( $headers )) {
			foreach( $headers as $name => $value ) {
				$this->requestHeaders[$name] = $value;
			}
		}
	}
	
	/**
	 * addHeader
	 * set a unique request header
	 *	@param headerName the header name
	 *	@param headerValue the header value, ( unencoded)
	 **/	
	function addHeader( $headerName, $headerValue )
	{
		if( $this->debug & DBGTRACE ) echo "addHeader( $headerName, $headerValue )\n";
		$this->requestHeaders[$headerName] = $headerValue;
	}

	/**
	 * removeHeader
	 * unset a request header
	 *	@param headerName the header name
	 **/	
	function removeHeader( $headerName ) 
	{
		if( $this->debug & DBGTRACE )	echo "removeHeader( $headerName) \n";
		unset( $this->requestHeaders[$headerName] );
	}

	/**
	 * addCookie
	 * set a session cookie, that will be used in the next requests.
	 * this is a hack as cookie are usually set by the server, but you may need it 
	 * it is your responsabilty to unset the cookie if you request another host
	 * to keep a session on the server
	 *	@param string the name of the cookie
	 *	@param string the value for the cookie
	 **/	
	function addCookie( $cookiename, $cookievalue ) 
	{
		if( $this->debug & DBGTRACE )	echo "addCookie( $cookiename, $cookievalue ) \n";
		$cookie = $cookiename . "=" . $cookievalue;
		$this->requestHeaders["Cookie"] = $cookie;
	}

	/**
	 * removeCookie
	 * unset cookies currently in use
	 **/	
	function removeCookies() 
	{
		if( $this->debug & DBGTRACE )	echo "removeCookies() \n";
		unset( $this->requestHeaders["Cookie"] );
	}

	/**
	 * Connect
	 * open the connection to the server
	 * @param host string server address (or IP)
	 * @param port string server listening port - defaults to 80
	 * @return boolean false is connection failed, true otherwise
	 **/
	function Connect( $host, $port = NULL ) 
	{
		if( $this->debug & DBGTRACE ) echo "Connect( $host, $port ) \n";
		
		$this->url['scheme'] = "http";
		$this->url['host'] = $host;
		if( $port != NULL )
			$this->url['port'] = $port;
		return true;
	}

	/**
	 * Disconnect
	 * close the connection to the  server
	 **/
	function Disconnect() 
	{
		if( $this->debug & DBGTRACE ) echo "Disconnect()\n";
		if( $this->socket )
			fclose( $this->socket );
	}

	/**
	 * head
	 * issue a HEAD request
	 * @param uri string URI of the document
	 * @return string response status code (200 if ok)
	 * @seeAlso getHeaders()
	 **/
	function Head( $uri )
	{
		if( $this->debug & DBGTRACE ) echo "Head( $uri )\n";
		$this->responseHeaders = $this->responseBody = "";
		$uri = $this->makeUri( $uri );
		if( $this->sendCommand( "HEAD $uri HTTP/$this->protocolVersion" ) )
			$this->processReply();
		return $this->reply;
	}
	
	
	/**
	 * get
	 * issue a GET http request
	 * @param uri URI (path on server) or full URL of the document
	 * @return string response status code (200 if ok)
	 * @seeAlso getHeaders(), getBody()
	 **/
	function Get( $url )
	{
		if( $this->debug & DBGTRACE ) echo "Get( $url )\n";
		$this->responseHeaders = $this->responseBody = "";
		$uri = $this->makeUri( $url );
		
		if( $this->sendCommand( "GET $uri HTTP/$this->protocolVersion" ) )
			$this->processReply();
		return $this->reply;
	}
	
	/**
	 * Options
	 * issue a OPTIONS http request
	 * @param uri URI (path on server) or full URL of the document
	 * @return array list of options supported by the server or NULL in case of error
	 **/
	function Options( $url )
	{
		if( $this->debug & DBGTRACE ) echo "Options( $url )\n";
		$this->responseHeaders = $this->responseBody = "";
		$uri = $this->makeUri( $url );
		
		if( $this->sendCommand( "OPTIONS $uri HTTP/$this->protocolVersion" ) )
			$this->processReply();
		if( @$this->responseHeaders["Allow"] == NULL )
			return NULL; 
		else
			return explode( ",", $this->responseHeaders["Allow"] );
	}
	
	/**
	 * Post
	 * issue a POST http request
	 * @param uri string URI of the document
	 * @param query_params array parameters to send in the form "parameter name" => value
	 * @return string response status code (200 if ok)
	 * @example 
	 *   $params = array( "login" => "tiger", "password" => "secret" );
	 *   $http->post( "/login.php", $params );
	 **/
	function Post( $uri, $query_params="" )
	{
		if( $this->debug & DBGTRACE ) echo "Post( $uri, $query_params )\n";
		$uri = $this->makeUri( $uri );
		if( is_array($query_params) ) {
			$postArray = array();
			foreach( $query_params as $k=>$v ) {
				$postArray[] = urlencode($k) . "=" . urlencode($v);
			}
			$this->requestBody = implode( "&", $postArray);
		}
		// set the content type for post parameters
		$this->addHeader( 'Content-Type', "application/x-www-form-urlencoded" );
// done in sendCommand()		$this->addHeader( 'Content-Length', strlen($this->requestBody) );

		if( $this->sendCommand( "POST $uri HTTP/$this->protocolVersion" ) )
			$this->processReply();
		$this->removeHeader('Content-Type');
		$this->removeHeader('Content-Length');
		$this->requestBody = "";
		return $this->reply;
	}

	/**
	 * Put
	 * Send a PUT request
	 * PUT is the method to sending a file on the server. it is *not* widely supported
	 * @param uri the location of the file on the server. dont forget the heading "/"
	 * @param filecontent the content of the file. binary content accepted
	 * @return string response status code 201 (Created) if ok
	 * @see RFC2518 "HTTP Extensions for Distributed Authoring WEBDAV"
	 **/
	function Put( $uri, $filecontent )
	{
		if( $this->debug & DBGTRACE ) echo "Put( $uri, [filecontent not displayed )\n";
		$uri = $this->makeUri( $uri );
		$this->requestBody = $filecontent;
		if( $this->sendCommand( "PUT $uri HTTP/$this->protocolVersion" ) )
			$this->processReply();
		return $this->reply;
	}
		
	/**
	 * Send a MOVE HTTP-DAV request
	 * Move (rename) a file on the server
	 * @param srcUri the current file location on the server. dont forget the heading "/"
	 * @param destUri the destination location on the server. this is *not* a full URL
	 * @param overwrite boolean - true to overwrite an existing destinationn default if yes
	 * @return string response status code 204 (Unchanged) if ok
	 * @see RFC2518 "HTTP Extensions for Distributed Authoring WEBDAV"
	 **/
	function Move( $srcUri, $destUri, $overwrite=true )
	{
		if( $this->debug & DBGTRACE ) echo "Move( $srcUri, $destUri, $overwrite )\n";
		if( $overwrite )
			$this->requestHeaders['Overwrite'] = "T";
		else
			$this->requestHeaders['Overwrite'] = "F";
		
		$destUrl = $this->url['scheme'] . "://" . $this->url['host'];
		if( $this->url['port'] != "" )
			$destUrl .= ":" . $this->url['port'];
		$destUrl .= $destUri;
		$this->requestHeaders['Destination'] =  $destUrl;
		
		if( $this->sendCommand( "MOVE $srcUri HTTP/$this->protocolVersion" ) )
			$this->processReply();
		return $this->reply;
	}

	/**
	 * Send a COPY HTTP-DAV request
	 * Copy a file -allready on the server- into a new location
	 * @param srcUri the current file location on the server. dont forget the heading "/"
	 * @param destUri the destination location on the server. this is *not* a full URL
	 * @param overwrite boolean - true to overwrite an existing destination - overwrite by default
	 * @return string response status code 204 (Unchanged) if ok
	 * @see RFC2518 "HTTP Extensions for Distributed Authoring WEBDAV"
	 **/
	function Copy( $srcUri, $destUri, $overwrite=true )
	{
		if( $this->debug & DBGTRACE ) echo "Copy( $srcUri, $destUri, $overwrite )\n";
		if( $overwrite )
			$this->requestHeaders['Overwrite'] = "T";
		else
			$this->requestHeaders['Overwrite'] = "F";
		
		$destUrl = $this->url['scheme'] . "://" . $this->url['host'];
		if( $this->url['port'] != "" )
			$destUrl .= ":" . $this->url['port'];
		$destUrl .= $destUri;
		$this->requestHeaders['Destination'] =  $destUrl;
		
		if( $this->sendCommand( "COPY $srcUri HTTP/$this->protocolVersion" ) )
			$this->processReply();
		return $this->reply;
	}


	/**
	 * Send a MKCOL HTTP-DAV request
	 * Create a collection (directory) on the server
	 * @param uri the directory location on the server. dont forget the heading "/"
	 * @return string response status code 201 (Created) if ok
	 * @see RFC2518 "HTTP Extensions for Distributed Authoring WEBDAV"
	 **/
	function MkCol( $uri )
	{
		if( $this->debug & DBGTRACE ) echo "Mkcol( $uri )\n";
		// $this->requestHeaders['Overwrite'] = "F";		
		if( $this->sendCommand( "MKCOL $uri HTTP/$this->protocolVersion" ) )
			$this->processReply();
		return $this->reply;
	}

	/**
	 * Delete a file on the server using the "DELETE" HTTP-DAV request
	 * This HTTP method is *not* widely supported
	 * Only partially supports "collection" deletion, as the XML response is not parsed
	 * @param uri the location of the file on the server. dont forget the heading "/"
	 * @return string response status code 204 (Unchanged) if ok
	 * @see RFC2518 "HTTP Extensions for Distributed Authoring WEBDAV"
	 **/
	function Delete( $uri )
	{
		if( $this->debug & DBGTRACE ) echo "Delete( $uri )\n";
		if( $this->sendCommand( "DELETE $uri HTTP/$this->protocolVersion" ) )
			$this->processReply();
		return $this->reply;
	}

	/**
	 * PropFind
	 * implements the PROPFIND method
	 * PROPFIND retrieves meta informations about a resource on the server
	 * XML reply is not parsed, you'll need to do it
	 * @param uri the location of the file on the server. dont forget the heading "/"
	 * @param scope set the scope of the request. 
	 *         O : infos about the node only
	 *         1 : infos for the node and its direct children ( one level)
	 *         Infinity : infos for the node and all its children nodes (recursive)
	 * @return string response status code - 207 (Multi-Status) if OK
	 * @see RFC2518 "HTTP Extensions for Distributed Authoring WEBDAV"
	 **/
	function PropFind( $uri, $scope=0 )
	{
		if( $this->debug & DBGTRACE ) echo "Propfind( $uri, $scope )\n";
		$this->requestHeaders['Depth'] = $scope;
		if( $this->sendCommand( "PROPFIND $uri HTTP/$this->protocolVersion" ) )
			$this->processReply();
		return $this->reply;
	}


	/**
	 * Lock - WARNING: EXPERIMENTAL
	 * Lock a ressource on the server. XML reply is not parsed, you'll need to do it
	 * @param $uri URL (relative) of the resource to lock
	 * @param $lockScope -  use "exclusive" for an eclusive lock, "inclusive" for a shared lock
	 * @param $lockType - acces type of the lock : "write"
	 * @param $lockScope -  use "exclusive" for an eclusive lock, "inclusive" for a shared lock	 
	 * @param $lockOwner - an url representing the owner for this lock
	 * @return server reply code, 200 if ok
	 **/
	function Lock( $uri, $lockScope, $lockType, $lockOwner )
	{
		$body = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>
<D:lockinfo xmlns:D='DAV:'>
<D:lockscope><D:$lockScope/></D:lockscope>\n<D:locktype><D:$lockType/></D:locktype>
	<D:owner><D:href>$lockOwner</D:href></D:owner>
</D:lockinfo>\n";
		
		$this->requestBody = utf8_encode( $body );
		if( $this->sendCommand( "LOCK $uri HTTP/$this->protocolVersion" ) )
			$this->processReply();
		return $this->reply;
	}
//<? //PSPad thinks the php block ended above

	/**
	 * Unlock - WARNING: EXPERIMENTAL
	 * unlock a ressource on the server
	 * @param $uri URL (relative) of the resource to unlock
	 * @param $lockToken  the lock token given at lock time, eg: opaquelocktoken:e71d4fae-5dec-22d6-fea5-00a0c91e6be4
	 * @return server reply code, 204 if ok
	 **/
	function Unlock( $uri, $lockToken )
	{
		$this->addHeader( "Lock-Token", "<$lockToken>" );
		if( $this->sendCommand( "UNLOCK $uri HTTP/$this->protocolVersion" ) )
			$this->processReply();
		return $this->reply;
	}

	/**
	 * getHeaders
	 * return the response headers
	 * to be called after a Get() or Head() call
	 * @return array headers received from server in the form headername => value
	 * @seeAlso get, head
	 **/
	function getHeaders()
	{
		if( $this->debug & DBGTRACE ) echo "getHeaders()\n";
		if( $this->debug & DBGINDATA ) { 
			echo "DBG.INDATA responseHeaders="; print_r( $this->responseHeaders );
		}
		return $this->responseHeaders;
	}

	/**
	 * getHeader
	 * return the response header "headername"
	 * @param headername the name of the header
	 * @return header value or NULL if no such header is defined
	 **/
	function getHeader( $headername )
	{
		if( $this->debug & DBGTRACE ) echo "getHeaderName( $headername )\n";
		return $this->responseHeaders[$headername];
	}

	/**
	 * getBody
	 * return the response body
	 * invoke it after a Get() call for instance, to retrieve the response
	 * @return string body content
	 * @seeAlso get, head
	 **/
	function getBody()
	{
		if( $this->debug & DBGTRACE ) echo "getBody()\n";
		return $this->responseBody;
	}

	/** 
	  * getStatus return the server response's status code 
	  * @return string a status code
	  * code are divided in classes (where x is a digit)
	  *  - 20x : request processed OK
	  *  - 30x : document moved
	  *  - 40x : client error ( bad url, document not found, etc...)
	  *  - 50x : server error 
	  * @see RFC2616 "Hypertext Transfer Protocol -- HTTP/1.1"
	  **/
	function getStatus() 
	{
		return $this->reply;
	}
  
	
	/** 
	  * getStatusMessage return the full response status, of the form "CODE Message"
	  * eg. "404 Document not found"
	  * @return string the message 
	  **/
	function getStatusMessage() 
	{
		return $this->replyString;
	}




	/*********************************************
	 * @scope only protected or private methods below
	 **/

	/** 
	  * send a request
	  * data sent are in order
	  * a) the command
	  * b) the request headers if they are defined
	  * c) the request body if defined
	  * @return string the server repsonse status code
	  **/
	function sendCommand( $command ) 
	{		
		if( $this->debug & DBGLOW ) echo "sendCommand( $command )\n";
		$this->responseHeaders = array();
		$this->responseBody = "";

		// connect if necessary		
		if( $this->socket == false or feof( $this->socket) ) {
			
			if( $this->useProxy ) {
				$host = $this->proxyHost;
				$port = $this->proxyPort;
			} else {
				$host = $this->url['host'];
				$port = $this->url['port'];
			}
			if( $port == "" )  $port = 80;
			$this->socket = fsockopen( $host, $port, $this->reply, $this->replyString );
			if( $this->debug & DBGSOCK ) echo "connexion( $host, $port) => $this->socket\n";
			if( ! $this->socket ) {
				if( $this->debug & DBGSOCK ) echo "FAILED : $this->replyString ($this->reply)\n";
				return false;
			}
		}
				
		if( $this->requestBody != ""  ) {
			$this->addHeader( "Content-Length", strlen( $this->requestBody ) );
		}

		$this->request = $command;
		$cmd = $command . CRLF;
		if( is_array( $this->requestHeaders) ) {
			foreach( $this->requestHeaders as $k => $v ) {
				$cmd .= "$k: $v" . CRLF;
			}
		}
		
		if( $this->requestBody != ""  ) {
			$cmd .= CRLF . $this->requestBody;
		}

		// unset body (in case of successive requests)
		$this->requestBody = "";
		if( $this->debug & DBGOUTDATA ) echo "DBG.OUTDATA Sending\n$cmd\n";
		
		fputs( $this->socket, $cmd . CRLF );
		return true;
	}

	function processReply()
	{
		if( $this->debug & DBGLOW ) echo "processReply()\n";

		$this->replyString = trim(fgets( $this->socket,1024) );
		
		if( preg_match( "|^HTTP/\S+ (\d+) |i", $this->replyString, $a )) {
			$this->reply = $a[1];
		} else {
			$this->reply = EBADRESPONSE;
		}
		if( $this->debug & DBGINDATA ) echo "replyLine: $this->replyString\n";

		//	get response headers and body
		$this->responseHeaders = $this->processHeader();
		$this->responseBody = $this->processBody();
//		if( $this->responseHeaders['set-cookie'] )
//			$this->addHeader( "cookie", $this->responseHeaders['set-cookie'] );
		return $this->reply;
	}
	
	/** 
	  * processHeader() reads header lines from socket until the line equals $lastLine
	  * @scope protected
	  * @return array of headers with header names as keys and header content as values
	  **/
	function processHeader( $lastLine = CRLF )
	{
		if( $this->debug & DBGLOW ) echo "processHeader( [lastLine] )\n";
		$headers = array();
		$finished = false;
		
		while ( ( ! $finished ) && ( ! feof($this->socket)) ) {
			$str = fgets( $this->socket, 1024 );
			if( $this->debug & DBGINDATA ) echo "HEADER : $str";
			$finished = ( $str == $lastLine );
			if ( !$finished ) {
				list( $hdr, $value ) = split( ": ", $str, 2 );
				// nasty workaround broken multiple same headers (eg. Set-Cookie headers) @FIXME 
				if( isset( $headers[$hdr]) )
					$headers[$hdr] .= "; " . trim($value);
				else
					$headers[$hdr] = trim($value);
			}
		}
		return $headers;
	}

	/** 
	  * processBody() reads the body from the socket 
	  * the body is the "real" content of the reply
	  * @return string body content 
	  * @scope private
	  **/
	function processBody()
	{
		$failureCount = 0;
		if( $this->debug & DBGLOW ) echo "processBody()\n";
/*		if( $this->responseHeaders['Content-Length'] ) 
		{
			$length = $this->responseHeaders['Content-Length'];
			$data = fread( $this->socket, $length );
			if( $this->debug & DBGSOCK ) echo "DBG.SOCK socket_read using Content-Length ($length)\n";

		} else {*/

			$data = "";
			$counter = 0;
//			socket_set_blocking( $this->socket, false );
			do{
				$status = socket_get_status( $this->socket );
				if( $status['eof'] == 1 ) {
					if( $this->debug & DBGSOCK ) echo "DBG.SOCK status eof met, finished socket_read\n";			
					break;
				}
				if( $status['unread_bytes'] > 0 ) {
					$buffer = fread( $this->socket, $status['unread_bytes'] );
					$counter = 0;
				} else {
					$buffer = fread( $this->socket, 1024 );
					$failureCount++;
					usleep(2);
				}
				$data .= $buffer;
				if( $this->debug & DBGSOCK )
					echo implode( " | ", $status ), "\n";
				
			} while(  $status['unread_bytes'] > 0 || $counter++ < 10 );

			if( $this->debug & DBGSOCK ) {
				echo "DBG.SOCK Counter:$counter\nRead failure #: $failureCount\n";
				echo "         Socket status: "; print_r($status);
			}
//			socket_set_blocking( $this->socket, true );
//		}
		return $data;
	}


	/**
	 * Calculate and return the URI to be sent ( proxy purpose )
	 * @param the local URI
	 * @return URI to be used in the HTTP request
	 * @scope private
	 **/
	 
	function makeUri( $uri )
	{
		$a = parse_url( $uri );

		if( isset($a['scheme']) && isset($a['host']) ) {
			$this->url = $a;
		} else {
			unset( $this->url['query']);
			unset( $this->url['fragment']);
			$this->url = array_merge( $this->url, $a );
		}		
		if( $this->useProxy ) {
			$requesturi= "http://" . $this->url['host'] . ( empty($this->url['port']) ? "" : ":" . $this->url['port'] ) . $this->url['path'] . ( empty($this->url['query']) ? "" : "?" . $this->url['query'] );
		} else {
			$requesturi = $this->url['path'] . (empty( $this->url['query'] ) ? "" : "?" . $this->url['query']);
		}
		return $requesturi;
	}
	
} // end class Net_HTTP_Client

