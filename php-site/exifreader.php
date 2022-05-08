<?

	$devutil = true;

	require_once('include/general.lib.php');
	require_once('include/JPEG.php');

	$filename = getREQval('filename');

	if($filename){
		$jpeg = new JPEG($filename);

		echo "ImageDescription: " . $jpeg->getExifField("ImageDescription") . "<br>\n";

		echo "<pre>";
		print_r($jpeg->getRawInfo());
		echo "</pre>";
	}else{
		$filename = "";
	}

	echo "<form action=$_SERVER[PHP_SELF]><input type=text name=filename value='$filename'><input type=submit value=Go></form>";
