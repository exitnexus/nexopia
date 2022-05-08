<?

	$login = 0;
	$simplepage = 1;
	$accepttype = false;

	require_once('include/general.lib.php');

	// headers to pass from user request to ruby site
	$keep_headers = array(
		'User-agent', 'Accept', 'Accept-language', //'Accept-encoding',
		'Accept-charset', 'Referer', 'Cookie', 'Cache-control', 'Content-length', 'Content-type'
	);


	// build the request to the ruby server
	$request = array(
		'method'	=> isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'UNKNOWN',
		'path'		=> $_SERVER['REQUEST_URI'],
		'headers'	=> array("Host: $rubydomain"),
		'postdata'	=> ''
	);

	// not a request we want to process - refuse request
	if (! in_array($request['method'], array('GET', 'POST', 'HEAD'))) {
		header("HTTP/1.1 405 Method Not Allowed");
		header("Allow: GET, POST, HEAD");
		echo "<h1>Method Not Allowed</h1>";

		exit;
	}

	// add :Body to the URL (at the end of the URL, or before the query string if one exists)
	//$request['path'] = ($pos = strpos($request['path'], '?')) === false ? "{$request['path']}:Body" : substr_replace($request['path'], ':Body', $pos, 0);

	// retrieve user headers that are to be passed through to ruby
	foreach ($_SERVER as $key => $val) {
		if (substr($key, 0, 5) == 'HTTP_') {
			$key = ucfirst(strtolower(str_replace('_', '-', substr($key, 5))));

			if (array_search($key, $keep_headers) !== false)
				$request['headers'][] = "${key}: ${val}";
		}
	}

	// if request is POST, add the post content
	if ($request['method'] == 'POST')
		$request['postdata'] = file_get_contents('php://input');

//choose a ruby server to connect to
	if(!is_array($rubysite))
		$rubysite = array($rubysite);

	$host = $rubysite[array_rand($rubysite)];
	$port = 80;

	if(strpos($host, ':'))
		list($host, $port) = explode(':', $host);

	// open socket to ruby host, fail with server error if it fails
	if ( ($fh = fsockopen($host, $port)) === false ) {
		header("HTTP/1.1 500 Internal Server Error");
		echo "<h1>Internal Server Error</h1>";

		exit;
	}
	
	// write request to the ruby server
	fwrite($fh, "{$request['method']} {$request['path']} HTTP/1.0\r\n" . join("\r\n", $request['headers']) . "\r\n\r\n{$request['postdata']}");

	// parse the ruby server's response
	$response = array(
		'status'	=> '',
		'code'		=> 0,
		'headers'	=> array(),
		'content'	=> ''
	);

	$stage = 'status';
	while ( ($line = fgets($fh)) !== false ) {
		if ($stage == 'status') {
			$response['status'] = str_replace('HTTP/1.0', 'HTTP/1.1', rtrim($line, "\r\n"));
			$response['code'] = substr($response['status'], 9, 3);
			$stage = 'headers';
			continue;
		}

		elseif ($stage == 'headers') {
			if (strlen($line = rtrim($line, "\r\n")) == 0) {
				$stage = 'content';
				continue;
			}

			list($key, $val) = preg_split('/:\s+/', $line, 2);
			$key = ucfirst(strtolower($key));

			if (! isset($response['headers'][$key]))
				$response['headers'][$key] = array();

			$response['headers'][$key][] = str_replace($host, $_SERVER['HTTP_HOST'], $val);
		}

		else
			$response['content'] .= $line;
	}

	// redirects and content-types other than *html* are passed through to the user
	if ($response['code'] == 301 || $response['code'] == 302 ||
		($response['headers']['Content-type'][0] && strpos($response['headers']['Content-type'][0], 'html') === false ) ||
		preg_match('/:Body$/', $_SERVER['REQUEST_URI'])) {

		if (isset($response['headers']['X-status']) && $response['headers']['X-status'][0] != '')
			header("HTTP/1.1 {$response['headers']['X-status'][0]}");
		else
			header($response['status']);

		foreach ($response['headers'] as $key => $vals) {
			foreach ($vals as $val) {
				if (substr($key, 0, 2) != 'X-' && $key != 'Connection') {
					if (strcasecmp($key, "Set-Cookie")) {
						//don't join headers for things other than cookies
						header("${key}: $val", true);
					} else {
						//join headers for cookies
						header("${key}: $val", false);
					}
				}
			}
		}

		echo $response['content'];
	}

	// page needs header and footer, with ruby response in between
	else {
		if (isset($response['headers']['X-modules'][0])) {
			$modules = split('/', $response['headers']['X-modules'][0]);
		} else {
			$modules = array();
		}
		if (isset($response['headers']['X-skeleton'][0])) {
			$skeleton = $response['headers']['X-skeleton'][0];
		} else {
			$skeleton = false;
		}
		if (isset($response['headers']['X-user-skin'][0])) {
			$skininfo = split('/', $response['headers']['X-user-skin'][0]);
			$skinuser = $skininfo[0];
			$skinrev = $skininfo[1];
			$skinname = $skininfo[2];
			$userskinpath = "/users/$skinuser/style/$reporev/$skinrev/$skinname.css";
		} else {
			$userskinpath = false;
		}

		if (isset($response['headers']['X-status']) && $response['headers']['X-status'][0] != '')
			header("HTTP/1.1 {$response['headers']['X-status'][0]}");
		else
			header($response['status']);

		$width = true;
		$leftblocks = array();

		$rightblocks = array();

		foreach ($response['headers'] as $key => $vals) {
			foreach ($vals as $val) {
				if ($key == 'X-width')
					$width = $val;

				elseif ($key == 'X-leftblocks' && $val != '')
					$leftblocks = array_merge($leftblocks, preg_split('/,\s*/', $val));

				elseif ($key == 'X-rightblocks' && $val != '')
					$rightblocks = array_merge($rightblocks, preg_split('/,\s*/', $val));

				elseif (substr($key, 0, 2) != 'X-' && $key != 'Connection' && $key != 'Content-length')
				{
					if (strcasecmp($key, "Set-Cookie")) {
						//don't join headers for things other than cookies
						header("${key}: $val", true);
					} else {
						//join headers for cookies
						header("${key}: $val", false);
					}
				}

			}
		}
		
		incHeader($width, $leftblocks, $rightblocks, $skeleton, $modules, $userskinpath);
		echo '<div class="ruby_content">';
		echo $response['content'];
		echo '</div>';
		incFooter();
	}
