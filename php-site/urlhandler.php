<?

$uri = substr($_ENV['REQUEST_URI'], 1);

$pos1 = strpos($uri, '?');
$pos2 = strpos($uri, '/');

if($pos1 && $pos2)
	$pos = min($pos1, $pos2);
elseif($pos1)
	$pos = $pos1;
elseif($pos2)
	$pos = $pos2;
else
	$pos = 0;

if($pos){
	$cmd = substr($uri, 0, $pos);
	$args = substr($uri, $pos+1);
}else{
	$cmd = $uri;
}

//echo "handled<br>";

switch($cmd){
	case "profile":
		$_GET = parseGET($args);
		$_REQUEST += parseGET($args);

		$_SERVER['PHP_SELF'] = "/$cmd";

		include("profile.php");
		break;

	case "info":
		phpinfo();
		break;

	case "test":
		echo "<pre>";
		echo "URI: $args\n";
		echo "GET: "; print_r($_GET);
		echo "POST: "; print_r($_POST);
		echo "REQ: "; print_r($_REQUEST);
		echo "COOKIE: "; print_r($_COOKIE);

		echo "parsed GET: ";
		$vals = parseGET($args);
		print_r($vals);
		echo "</pre>";

		break;

	case "":
		$cmd = "index.php";

	default:
		$cmd = preg_replace("/[^a-zA-Z0-9.]/","", $cmd);

		$_GET = parseGET($args);
		$_REQUEST += parseGET($args);
		$_SERVER['PHP_SELF'] = "/$cmd";
		if(! (@include($cmd)))
			die("<h1>404</h1><br>$cmd");
		exit;
}

function parseGET($args){ //doesn't handle arrays yet
	$vals = array();
	$temp = explode('&', $args);
	foreach($temp as $v){
		list($k, $v) = explode('=', $v);

		$k = preg_replace("/[^a-zA-Z0-9\[\]]/","", urldecode($k));
		$v = urldecode($v);

		if(preg_match("/^([a-zA-Z][a-zA-Z0-9]*)(\[([a-zA-Z0-9]*)\])?\$/", $k, $matches)){
			if($matches[2]){ //is an array
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



