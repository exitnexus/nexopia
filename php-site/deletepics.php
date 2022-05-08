<?

	set_time_limit(0);

	$dir = "/home/nexopia/public_html/users/";

	$fp = fopen($dir . "pics.txt",'r');

	$contents = "";
	while($line = fread($fp,4096))
		$contents .= $line;

	$pics = explode(',',$contents);

	$maxid = $pics[count($pics)-1];

	echo "maxid: $maxid ";
	zipflush();

	$pics = array_flip($pics);

	for($i = 1; $i <= $maxid; $i++){
		if(!isset($pics[$i])){
			if(file_exists($dir . floor($i/1000) . "/$i.jpg"))
				unlink($dir . floor($i/1000) . "/$i.jpg");
			if(file_exists($dir . "thumbs/" . floor($i/1000) . "/$i.jpg"))
				unlink($dir . "thumbs/" . floor($i/1000) . "/$i.jpg");
		}
		if($i%1000 == 0){
			echo "$i ";
			zipflush();
			usleep(1);
		}
	}

