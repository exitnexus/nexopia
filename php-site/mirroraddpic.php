<?

	include("include/mirrorconfig.inc.php");

	if(!isset($id) || !is_numeric($id))
		exit;

	if(!isset($imagemasters[$REMOTE_ADDR]))
		exit;

	$imagemaster = $imagemasters[$REMOTE_ADDR];

	register_shutdown_function('addpic');
	exit;

function addpic(){
	global $id, $docRoot, $lastfile, $imgdir, $thumbdir, $imagemaster;

	umask(0);

	$end = $id;

	clearstatcache();

	$fh = fopen($docRoot . $lastfile, 'w+b');    // use 'r+b' so file can be read and written, create if needed
	if( !$fh )
		exit;

	$i=20;
	while($i--){
		clearstatcache();
		$lf = @flock($fh, LOCK_EX);

		if($lf)
			break;
		usleep(rand(50,500));
	}

	if( !$lf ){  //something failed
		fclose($fh);
		exit;
	}

	$last = fread($fh, filesize($docRoot . $lastfile));

	if(empty($last))
		$last = $id - 1;

	for($id = $last + 1; $id <= $end; $id++){

		if(!is_dir($docRoot . $imgdir . floor($id/1000)))
			@mkdir($docRoot . $imgdir . floor($id/1000),0777);

		$remotefile = "http://" . $imagemaster . $imgdir . floor($id/1000) . "/$id.jpg";
		$localfile = 				  $docRoot . $imgdir . floor($id/1000) . "/$id.jpg";

		$remote = fopen($remotefile,'r');
		if(!$remote)    die("error: Can't open remote image: $remotefile");

		$local = fopen($localfile,'w');
		if(!$local)     die("error: Can't open local image: $localfile");

		while($buf = fread($remote,4096))
			fwrite($local,$buf);

		fclose($remote);
		fclose($local);


		$remotefile = "http://" . $imagemaster . $thumbdir . floor($id/1000) . "/$id.jpg";
		$localfile = 				  $docRoot . $thumbdir . floor($id/1000) . "/$id.jpg";

		if(!is_dir($docRoot . $thumbdir . floor($id/1000)))
			@mkdir($docRoot . $thumbdir . floor($id/1000),0777);

		$remote = fopen($remotefile,'r');
		if(!$remote)    die("error: Can't open remote thumb: $remotefile");

		$local = fopen($localfile,'w');
		if(!$local)     die("error: Can't open local thumb: $localfile");

		while($buf = fread($remote,4096))
			fwrite($local,$buf);

		fclose($remote);
		fclose($local);
	}

	fwrite($fh, $end);
	fflush($fh);
	flock($fh, LOCK_UN);
	fclose($fh);
}

