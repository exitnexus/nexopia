<?

	include("mirrorconfig.inc.php");

	if(!isset($name) || strpos($name,'/')!==false || $REMOTE_ADDR != $masterip)
		exit;

	if(file_exists($docRoot . $bannerdir . $name))
		exit;

//	echo "remote: http://" . $imagemaster . $imgdir . $name . "\n";
	$remote = fopen("http://" . $imagemaster . $bannerdir . $name,'r');
	if(!$remote)    die("error: Can't open remote banner");

//	echo "local:" . $docRoot . $imgdir . $name . "\n";
	$local = fopen($docRoot . $bannerdir . $name,'w');
	if(!$local)     die("error: Can't open local banner");

	while($buf = fread($remote,4096)){
		fwrite($local,$buf);
//		echo $buf;
	}

	fclose($remote);
	fclose($local);


