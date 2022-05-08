<?	

	require_once("include/general.lib.php");
	set_time_limit(0);


	$page=0;
	$postsPerPage=25;
	$tid=1800;

	$time1 = gettime();

	for($i=0;$i<1000;$i++){
		$query = "SELECT id,author,authorid,time,nmsg FROM forumposts WHERE threadid='$tid' ORDER BY time ASC LIMIT ".$page*$postsPerPage.", $postsPerPage";
		$result = mysql_query($query);
		
		$postdata = array();
		$posterids = array();
		$posterdata = array();
		
		$lasttime=0;
		while($line = mysql_fetch_assoc($result)){
			$postdata[] = $line;
			$lasttime=$line['time'];
			
			if($line['authorid']!=0)
				$posterids[$line['authorid']] = "userid='$line[authorid]'";
		}
	
		if(count($posterids)>0){
			$query = "SELECT userid,activetime,numpics,dob,loc,posts,email,icq,msn,aim,yahoo,showemail,nsigniture,firstpic,forumrank FROM users WHERE " . implode(" || ", $posterids);
			$result = mysql_query($query) or trigger_error("$query, " . mysql_error(), E_USER_NOTICE);
		
			while($line = mysql_fetch_assoc($result))
				$posterdata[$line['userid']] = $line;
		}
	}

	$time2 = gettime();
	echo number_format(($time2 - $time1)/10000,4) . "<br>\n";



	$time1 = gettime();

	for($i=0;$i<1000;$i++){
		$query = "SELECT id,author,authorid,time,nmsg,  activetime,dob,loc,posts,email,icq,msn,aim,yahoo,showemail,nsigniture,firstpic,forumrank FROM forumposts LEFT JOIN users ON forumposts.authorid=users.userid WHERE threadid='$tid' ORDER BY time ASC LIMIT ".$page*$postsPerPage.", $postsPerPage";
		$result = mysql_query($query);
	
		$postdata = array();
		while($line = mysql_fetch_assoc($result))
			$postdata[] = $line;

	}

	$time2 = gettime();
	echo number_format(($time2 - $time1)/10000,4) . "<br>\n";
/*

	$output = "<?\n\$i=1;\n";
	for($i=0;$i<50000;$i++)
		$output .= "\$test = 'asdf';\n";

	$fp = fopen("test2.php","w");
	fwrite($fp, $output);
	fclose($fp);


	$time1 = gettime();
	
	include('test2.php');
	
	$time2 = gettime();
	echo number_format(($time2 - $time1)/10000,4) . "<br>\n";

	$output = "<?\n\$i=1;\n";
	for($i=0;$i<50000;$i++)
		$output .= "\$test = \"asdf\";\n";

	$fp = fopen("test2.php","w");
	fwrite($fp, $output);
	fclose($fp);


	$time1 = gettime();
	
	include('test2.php');
	
	$time2 = gettime();
	echo number_format(($time2 - $time1)/10000,4) . "<br>\n";


	$output = "<?\n\$i=1;\n";
	for($i=0;$i<50000;$i++)
		$output .= "\$test = \"asdf \$i\";\n";

	$fp = fopen("test2.php","w");
	fwrite($fp, $output);
	fclose($fp);


	$time1 = gettime();
	
	include('test2.php');
	
	$time2 = gettime();
	echo number_format(($time2 - $time1)/10000,4) . "<br>\n";

	$output = "<?\n\$i=1;\n";
	for($i=0;$i<50000;$i++)
		$output .= "\$test = 'asdf ' . \$i;\n";

	$fp = fopen("test2.php","w");
	fwrite($fp, $output);
	fclose($fp);


	$time1 = gettime();
	
	include('test2.php');
	
	$time2 = gettime();
	echo number_format(($time2 - $time1)/10000,4) . "<br>\n";

*/



/*
	$time1 = gettime();
	for($i=0;$i<1000000;$i++)
		$test = 'asdf';
	$time2 = gettime();
	echo number_format(($time2 - $time1)/10000,4) . "<br>\n";


	$time1 = gettime();
	for($i=0;$i<1000000;$i++)
		$test = "asdf";
	$time2 = gettime();
	echo number_format(($time2 - $time1)/10000,4) . "<br>\n";

	$time1 = gettime();
	for($i=0;$i<1000000;$i++)
		$test = "asdf $i";
	$time2 = gettime();
	echo number_format(($time2 - $time1)/10000,4) . "<br>\n";

	$time1 = gettime();
	for($i=0;$i<1000000;$i++)
		$test = 'asdf' . $i;
	$time2 = gettime();
	echo number_format(($time2 - $time1)/10000,4) . "<br>\n";
	
	$time1 = gettime();
	for($i=0;$i<1000000;$i++)
		$test = "asdf" . $i;
	$time2 = gettime();
	echo number_format(($time2 - $time1)/10000,4) . "<br>\n";
*/
