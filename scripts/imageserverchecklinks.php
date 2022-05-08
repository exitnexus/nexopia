#!/usr/local/php/bin/php
<?

    include("fileserver.php");

    $fs = new fileserver();

	$dirs = array(	"/users/",
					"/users/thumbs/",
					"/gallery/",
					"/gallery/thumbs/",
					"/skins/",
					"/uploads/thumbs/",
				);

	foreach($dirs as $dir){

		echo "$dir: ";

		$ret = $fs->checkLinks($fs->basedir . $dir);
		
		echo count($ret) . "\n";
	}
