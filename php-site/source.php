<?

	$login = 1;
	include_once("include/general.lib.php");

	if(!isset($config['showsource']) || !$config['showsource'])
		die("error1");

	if(!in_array($userData['userid'], $debuginfousers))
		die("error2");


	$file = getREQval('file');
	$k = getREQval('k');
	
//	if(!checkKey($file,$k)) die("error3");

	
	if(strpos(realpath($docRoot . $file),$docRoot) !== 0)
		die("error4");
	
	highlight_file($docRoot . $file);
