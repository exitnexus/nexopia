<?

	// these are files that implement the new pagehandler system.
	$possiblefiles = array(
		'/^\/galleries/' => 'gallery.php',
		'/^\/manage\/galleries/' => 'managegallery.php',
		'/^\/manage\/pictures/' => 'managepicture.php',
		'/^\/wiki/' => 'wiki.php',
		'/^\/help/' => 'help.php',
		'/^\/skincontest/' => 'skincontest.php',
//		'/^\/music/' => 'music_section.php',
		'/^\/contest/' => 'contests.php',
		'/^\/googlesearch/' => 'googlesearch.php',
		'/^\/capitalex/' => 'capitalex.php',
		'/^\/careers/' => 'careers.php',
		'/^\/about/' => 'aboutus.php',
		'/^\/advertis(e|ing)/' => 'advertise.php',
		'/^\/plus/' => 'plus.php',
		'/^\/dame/' => 'dame.php',

		'/^\/video/' => 'ruby_passthru.php',
		'/^\/test_stuff/' => 'ruby_passthru.php',
		'/^\/pastebin/' => 'ruby_passthru.php',
		'/^\/music/' => 'ruby_passthru.php',
		'/^\/content/' => 'ruby_passthru.php',
		'/^\/admin/' => 'ruby_passthru.php',
		'/^\/account/' => 'ruby_passthru.php',
		'/^\/my/' => 'ruby_passthru.php',
		'/^\/users/' => 'ruby_passthru.php',
		'/^\/terms/' => 'terms.php',
		'/^\/googleprofile/' => 'googleprofile.php',
		'/^\/tynt/' => 'ruby_passthru.php',
		'/^\/eyewonder/' => 'ruby_passthru.php',
		'/^\/promotions/' => 'ruby_passthru.php',
	);

//lighty doesn't parse the GET variables in a 404, so we do it manually
	$_SERVER['PHP_SELF'] = $_SERVER['REQUEST_URI'];

	if($pos = strpos($_SERVER['PHP_SELF'], '?')){
		$_SERVER['PHP_SELF'] = substr($_SERVER['PHP_SELF'], 0, $pos);

		$args = substr($_SERVER['REQUEST_URI'], $pos+1);

		$_GET = parseGET($args);
		$_REQUEST += $_GET;

		unset($args);
	}

	unset($pos);

//load the php file that has the right page handler
	foreach($possiblefiles as $match => $filename){
		if(preg_match($match, $_SERVER['REQUEST_URI'])){

			unset($possiblefiles, $match);

			include($filename);
			exit();
		}
	}

//if no page found, this is a real 404
	$login = 0;
	$simplepage = 1;
	$accepttype = false;
	include_once("include/general.lib.php");

	if($config['passthrough_all_unrecognized'])
	{
		include('ruby_passthru.php');
	} else {
		$_SERVER['REQUEST_URI'] = "/fourohfour.php";
		header("Status: 404 Not Found");
		incHeader();
		echo "<center>404 Error. This isn't the page you're looking for.</center>"; // improve muchly.
		incFooter();
	}

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

		if(preg_match("/^([a-zA-Z][a-zA-Z0-9_]*)(\[([a-zA-Z0-9_]*)?\])?$/", $k, $matches)){
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
