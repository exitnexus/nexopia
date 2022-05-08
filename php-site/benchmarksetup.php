<?

	$basedir = "/htdocs/files/";
	$groupsize = 1000;
	$urlprefix = "http://10.0.4.253/files/";

	$num = 50000;

	$numsimplefiles = 100;
	$simpleminsize = 1;
	$simplemaxsize = 3;
	$simplmultiplyer = 3;

	$thumbprefix = "thumbs/";
	$thumbmultiplyer = 2;
	$thumbsizemult = 0.25;

	$picminsize = 4;
	$picmaxsize = 8;

/////////////////////////

	set_time_limit(0);

	$randbyte = "";

	for($i = 0; $i < 1000; $i++)
		$randbyte .= rand(0,9);


	for($i = 1; $i <= $numsimplefiles; $i++){
		$size = rand($simpleminsize, $simplemaxsize);

		$fp = fopen($basedir . "$i.txt", 'w');
		fwrite($fp, str_repeat($randbyte, $size));
		fclose($fp);
	}


	mkdir($basedir . $thumbprefix);

	$f = fopen($basedir . "filelist", 'w');

	for($i = 0; $i < $num; ++$i){

		if($i % 1000 == 0){
			mkdir($basedir . floor($i/1000) . "/");
			mkdir($basedir . $thumbprefix . floor($i/1000) . "/");
			echo floor($i/1000) . " "; zipflush();
		}

		$size = rand($picminsize, $picmaxsize);

		$fp = fopen($basedir . floor($i/1000) . "/$i.txt", 'w');
		fwrite($fp, str_repeat($randbyte, $size));
		fclose($fp);

		$fp = fopen($basedir . $thumbprefix . floor($i/1000) . "/$i.txt", 'w');
		fwrite($fp, str_repeat($randbyte, floor($size*$thumbsizemult)));
		fclose($fp);

		fwrite($f, $urlprefix . floor($i/1000) . "/$i.txt\n");

		for($j=0; $j < $thumbmultiplyer; $j++)
			fwrite($f, $urlprefix . $thumbprefix . floor($i/1000) . "/$i.txt\n");

		for($j=0; $j < $simplmultiplyer; $j++)
			fwrite($f, $urlprefix . rand(1, $numsimplefiles) . ".txt\n");
	}

	fclose($f);

function zipflush(){ }
