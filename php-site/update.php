<?

	$forceserver = true;
	$enableCompression = true;
	$login=1;
	$errorLogging = false;
	require_once("include/general.lib.php");

	if(!in_array($userData['userid'], $debuginfousers))
		die("error");

	echo str_repeat(" ",400). "\n";
	set_time_limit(0);
//	ignore_user_abort(true);


	$res = $masterdb->query("SELECT 2147490171 as val");
	$val = $res->fetchrow();

	var_dump($val);
	
	echo "<br>";
	
	settype($val['val'], 'int');
	var_dump($val);
	
	exit;








/*
	$res = $db->query("SELECT * FROM config ORDER BY name");
	
	$str = "\$config = array(\n";
	
	while($line = $res->fetchrow()){
		$str .= "\t'$line[name]' => '" . addslashes($line['value']) . "',";
		if($line['comments'])
			$str .= " //$line[comments]";
		$str .= "\n";
	}
	$str .= "\t);";
	
	echo "<pre>";
	echo $str;
	echo "</pre>";
	

	
	exit;

/*
	$res = $msgsdb->query("SELECT id, msgtextid FROM msgs");

	while($line = $res->fetchrow())
		$msgsdb->prepare_query("INSERT INTO msgtext SELECT #, date, html, msg FROM msgtextold WHERE id = #", $line['id'], $line['msgtextid']);
/* /
	$msgsdb->query("INSERT INTO msgtext SELECT msgs.id, msgtextold.date, msgtextold.html, msgtextold.msg FROM msgs, msgtextold WHERE msgs.msgtextid = msgtextold.id");
//*/



/*
	$db->prepare_query("SELECT id FROM articles WHERE moded = 'n'");

	while($line = $db->fetchrow())
		$mods->newItem(MOD_ARTICLE, $line['id']);
*/


/*

	$picsdb->query("SELECT id FROM pics");

	$pics = array();
	while($line = $picsdb->fetchrow())
		$pics[] = $line['id'];

	sort($pics);

	echo implode(',', $pics);

	$fp = fopen("/home/nexopia/public_html/users/pics.txt", 'w');
	fwrite($fp, implode(',', $pics);
	fclose($fp);



/*
<?
	$filename = "pics.txt";

	$file = file_get_contents($filename);
	$pics = explode(',', $file);

	$last = end($pics);

	$pics = array_combine($pics, $pics);

	for($i=1; $i < $last; $i++){
		if($i % 1000 == 0)
			echo ($i/1000) . " ";

		if(isset($pics[$i]))
			continue;

		if(file_exists("/home/nexopia/public_html/users/" . floor($i/1000) . "/$i.jpg"))
				unlink("/home/nexopia/public_html/users/" . floor($i/1000) . "/$i.jpg");

		if(file_exists("/home/nexopia/public_html/users/thumbs/" . floor($i/1000) . "/$i.jpg"))
				unlink("/home/nexopia/public_html/users/thumbs/" . floor($i/1000) . "/$i.jpg");
	}
*/



/*
	$filesdb->prepare_query("SELECT file FROM fileupdates WHERE time >= # && action='delete'", time() - 86400*90);

	$commands = "";

	$files = array();
	while($line = $filesdb->fetchrow()){
		$files[] = $line['file'];
		$commands .= "rm -f $docRoot$line[file]\n";
	}

	echo "<pre>$commands</pre>";
*/





//DELETE forumposts FROM forumposts LEFT JOIN forumthreads ON forumthreads.id = forumposts.threadid WHERE forumposts.threadid IS NULL


	echo "\n<br>\n<br>Update Complete<br>\n";
	$endTime = gettime();
	$dtime = number_format(($endTime - $times['start'])/10000,4);
	echo "Run-time $dtime seconds<br>\n";

	outputQueries();
