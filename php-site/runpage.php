<?

// these are files that implement the new pagehandler system.
$possiblefiles = array(
	'/^\/empty page/' => 'newempty.php',
	'/^\/games/' => 'officegames.php',
	'/^\/galleries/' => 'gallery.php',
	'/^\/manage\/galleries/' => 'managegallery.php',
	'/^\/manage\/pictures/' => 'managepicture.php',
	'/^\/wiki/' => 'wiki.php',
	'/^\/help/' => 'help.php',
);

//*

	$_SERVER['PHP_SELF'] = $_SERVER['REQUEST_URI'];

	if($pos = strpos($_SERVER['PHP_SELF'], '?')){
		$_SERVER['PHP_SELF'] = substr($_SERVER['PHP_SELF'], 0, $pos);

		$args = substr($_SERVER['REQUEST_URI'], $pos+1);

		$_GET = parseGET($args);
		$_REQUEST += $_GET;

		unset($args);
	}

	unset($pos);

//*/

foreach ($possiblefiles as $match => $filename)
{
	// only load if it matches the regex above
	if (preg_match($match, urldecode($_SERVER['REQUEST_URI'])) && include_once($filename))
		exit();
}

$login = 0;
$simplepage = true;
include_once "include/general.lib.php";
header("Status: 404 Not Found");
incHeader();
print("<center>404 Error. This isn't the page you're looking for.</center>"); // improve muchly.
incFooter();


//*

function parseGET($args){ //doesn't handle multi-dimensional arrays yet
	$vals = array();
	$temp = explode('&', $args);
	foreach($temp as $v){
		if($pos = strpos($v, '=')){
			list($k, $v) = explode('=', $v);
		}else{
			$k = $v;
			$v = '';
		}

		$k = preg_replace("/[^a-zA-Z0-9_\[\]]/","", urldecode($k));
		$v = urldecode($v);

		if(preg_match("/^([a-zA-Z][a-zA-Z0-9_]*)(\[([a-zA-Z0-9_]*)\])?\$/", $k, $matches)){
			if(isset($matches[2])){ //is an array
				if($matches[3]){ //named/numbered key
					$vals[$matches[1]][$matches[3]] = $v;
				}else{
					$vals[$matches[1]][] = $v;
				}
			}else{
				$vals[$matches[1]] = $v;
			}
		}
	}

	return $vals;
}
//*/
