<?

	$forceserver = true;
	$enableCompression = true;
	$login=1;
	$errorLogging = false;
	require_once("include/general.lib.php");

$debuginfousers[] = 997372;
	if(!in_array($userData['userid'], $debuginfousers))
		die("error");

	echo str_repeat(" ",400). "\n";
	set_time_limit(0);
//	ignore_user_abort(true);


//	$res = $masterdb->query("SELECT 2147490171 as val");
//	$val = $res->fetchrow();

/*	$usersdb->query("DELETE picspending FROM picspending, pics WHERE picspending.userid = pics.userid AND picspending.id = pics.id");

	$res = $usersdb->unbuffered_query($usersdb->prepare("SELECT * FROM picspending"));
	while ($res)
	{
		$ids = array();
		for ($i = 0; $i < 100; $i++)
		{
			$row = $res->fetchrow();
			if (!$row)
			{
				$res = false;
				break;
			}
			if (!isset($ids[ $row['userid'] ]))
				$ids[ $row['userid'] ] = array();
			$ids[ $row['userid'] ][] = $row['id'];
		}
		if ($ids)
			$mods->newSplitItem(MOD_PICS, $ids, true);
	}

	var_dump($val);

	echo "<br>";

	settype($val['val'], 'int');
	var_dump($val); */


//memcache testing

/*	$num = 2000;
	echo "$num runs<br><br>";*/

/*
//php memcache
	$phpmemcache->set("test", 1, 60);

	$time = gettime();
	for($i=0; $i < $num; $i++)
		$phpmemcache->get("test");
//		$phpmemcache->set("test", 1, 60);
	$time2 = gettime();

	$phpmemcache->delete("test");

	echo "time: " . ($time2 - $time)/10 . " ms<br>";
	echo "each: " . (($time2 - $time)/10)/$num . " ms<br>";
	echo "<br>";



echo "<b>memcache test</b><br>";

	$key = "test2";

	$cache->put($key, 1, 60);

	$time = gettime();
	for($i=0; $i < $num; $i++)
		$cache->get($key);
//		$cache->set("test2", 1, 60);
	$time2 = gettime();

	$cache->remove($key);

	echo "time: " . number_format(($time2 - $time)/10,3) . " ms<br>";
	echo "each: " . number_format((($time2 - $time)/10)/$num,3) . " ms<br>";
	echo "rate: " . number_format((1000/((($time2 - $time)/10)/$num)),3) . " ps<br>";
	echo "<br>";

/*

echo "<b>pure pecl</b><br>";
	$peclmemcache->set("test3", 1, false, 60);

	$time = gettime();
	for($i=0; $i < $num; $i++)
		$peclmemcache->get("test3");
//		$peclmemcache->set("test3", 1, false, 60);
	$time2 = gettime();

	$peclmemcache->delete("test3");

	echo "time: " . number_format(($time2 - $time)/10,3) . " ms<br>";
	echo "each: " . number_format((($time2 - $time)/10)/$num,3) . " ms<br>";
	echo "rate: " . number_format((1000/((($time2 - $time)/10)/$num)),3) . " ps<br>";
	echo "<br>";
*/

//$uid = 175;
$uid = 1772099;
$ua = $cache->get("useractive-$uid");
var_dump($ua, userdate("l F j, Y, g:i a", $ua));
$ui = $cache->get("userinfo-$uid");
var_dump($ui, userdate("l F j, Y, g:i a", $ui['useractive']));

var_dump($ua > $ui['activetime'], ($ua > time() - $config['friendAwayTime'] ? 'y' : 'n'));

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
