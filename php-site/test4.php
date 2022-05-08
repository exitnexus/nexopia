<?

	if(substr($DOCUMENT_ROOT,-1)=="/")
		$docRoot=substr($DOCUMENT_ROOT,0,-1);
	else
		$docRoot=$DOCUMENT_ROOT;

	set_time_limit(600);

//	$opendir = "/htdocs/rankme/backup june 16 live, complete/log/v3/";
	$opendir = "/home/enternex/public_html/log/v2/";
//	$opendir = $docRoot . "/log/";

	$basedir="";
	

	if (!($dir = @opendir($basedir . $opendir))){
		echo "Could not open " . $basedir . $opendir . " for reading";
		return;
	}
	
	$totalsize=0;
	while($file = readdir($dir)) {
		if($file[0]!="."){
			if(!is_dir($basedir . $opendir . $file))
				if(substr($file,-2)!='gz')
					continue;
				$newfile = substr($file,0,-3);
			
				echo $basedir . $opendir . $file . "<br>\n";
				flush();

				$fp = fopen($basedir . $opendir . $file,'r');
				$str = fread($fp, filesize($basedir . $opendir . $file));
				fclose($fp);

				$str = gzinflate(substr($str,10));

				$gz = fopen($basedir . $opendir . "../" . $newfile, "w");
				fwrite($gz, $str);
				fclose($gz);

				
				
			
			
//				$crc = crc32($basedir . $opendir . $file);
//				$listing[] = $basedir . $opendir . $file . ", " . sprintf("%u", $crc) . " <br>\n";

		}
	}
	
//	sort($listing);
	
//	foreach($listing as $output)
//		echo $output;
