<?

	include("mirrorconfig.inc.php");

	if(!isset($name) || strpos($name,'/')!==false || $REMOTE_ADDR != $masterip)
		exit;

	if(file_exists($docRoot . $bannerdir . $name))
		unlink($docRoot . $bannerdir . $name);
