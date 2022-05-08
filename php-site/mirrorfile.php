<?

	if(empty($file))
		die("bad file name");

	require_once("include/general.lib.php");

	$mirroring = array('picdir','thumbdir','bannerdir','basefiledir','gallerypicdir','gallerythumbdir','imagedir','smilydir');


	$mirrors = $cache->hdget("mirrors", 'getMirrors');

	$control = "";
	foreach($mirrors['www'] as $mirror){
		if($mirror['control'] == $REMOTE_ADDR){
			$control = $mirror['domain'];
			break;
		}
	}
	if(!$control)
		die("Permission Denied");

	if(strlen($file) < 100 && strpos($file,"..") === false && strpos($file,"?") === false){
		$extension = strtolower(substr($file,strrpos($file,".")+1));

		if($extension == "php" || $extension == "php3")
			break;

		foreach($mirroring as $item){
			if(substr($file, 0, strlen($config[$item])) == $config[$item]){ //starts with one of the dirs

				if(!file_exists($docRoot . $file)){
					$dirs = explode("/", dirname($file));

					umask(0);

					$basedir = $docRoot;
					foreach($dirs as $dir){
						if(!is_dir("$basedir/$dir"))
							@mkdir("$basedir/$dir",0777);
						$basedir .= "/$dir";
					}


		$remotefile = "http://" .	$control . $file;
		$localfile = 				$docRoot . $file;

		$remote = fopen($remotefile,'r');
		if(!$remote)    die("error: Can't open remote image: $remotefile");

		$local = fopen($localfile,'w');
		if(!$local)     die("error: Can't open local image: $localfile");

		while($buf = fread($remote,4096))
			fwrite($local,$buf);

		fclose($remote);
		fclose($local);

					exit;
				}
				break;
			}
		}
	}

