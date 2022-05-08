#!/usr/local/php/bin/php
<?

	include("fileserver.php");
	
	$fs = new fileserver();
	
	$failed = $fs->testDrives();
	
	if(count($failed)){
	
		$hostname = `hostname`;
	
		$subject = "drive failed on $hostname";
		$email = "";
	
		foreach($failed as $drive){
			echo "unmounting $drive\n";
			$email .= "unmounting $drive\n";
			`umount $drive`;
		}
	
	//straight copy from imageserverchecklinks.php
		$dirs = array(	"/users/",
						"/users/thumbs/",
						"/gallery/",
						"/gallery/thumbs/",
						"/skins/",
						"/uploads/thumbs/",
					);
	
		foreach($dirs as $dir){
			$ret = $fs->checkLinks($fs->basedir . $dir);
			
			echo "$dir: " . count($ret) . "\n";
			$email .= "$dir: " . count($ret) . "\n";
		}
		
		
		mail("timo@nexopia.com", $subject, $email);
	}