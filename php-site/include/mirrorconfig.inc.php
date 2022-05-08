<?

	$imagemasters = array(	"65.254.38.178" => "images.nexopia.com",
							"209.51.131.210" => "images.nexopia.com");

	$docRoot ="/home/enternexus/public_html";


	$imgdir = "/users/";
	$thumbdir = "/users/thumbs/";
	$bannerdir = "/banners/";

	$lastfile = '/last.txt';

	$errorLogging=true;
	include_once("include/errorlog.php");


	ignore_user_abort(true);
	set_time_limit(60);

//*
//cgi
	$sapi = php_sapi_name();

	if($sapi == 'cli'){
		$params = explode("&",$_SERVER['QUERY_STRING']);
		foreach($params as $param){
			list($k,$v) = explode("=", $param);
			global $$k;
			$$k = urldecode($v);
		}
		echo "\n\n";
	}
//*/


