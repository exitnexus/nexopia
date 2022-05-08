<?

	$forceserver = true;
	$login=1;
	require_once("include/general.lib.php");

	if($userData['userid']!=5 && $userData['userid']!=1)
		die("error");


	$cat = 6571;
	$basedir = "/home/nexopia/standard";


	$opendir = "/";

//*
	if (!($dir = @opendir($basedir . $opendir))){
		echo "Could not open " . $basedir . $opendir . " for reading";
		return;
	}

	$listing = array();
	$totalsize=0;
	while($file = readdir($dir)) {
		if($file[0]!="."){
			addGalleryPic($basedir . $opendir . $file, $cat, "");
		}
	}
	closedir($dir);
//*/

	setFirstGalleryPic($userData['userid'],$cat);


