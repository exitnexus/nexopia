<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"stats"))
		die("Permission denied");


	$start = usermktime(0,0,0,2,1,2005);
	$end = time();


	$res = $masterdb->prepare_query("SELECT * FROM statshist WHERE time >= # && time <= #", $start, $end);

	$rows = array();
	while($line = $res->fetchrow())
		$rows[$line['time']] = $line;

	ksort($rows);


	incHeader();

	echo "<table align=center>";

	echo "<tr>";
	echo "<td class=header align=center>Time</td>";
	echo "<td class=header align=center>Total Users</td>";
	echo "<td class=header align=center>Total Hits</td>";
	echo "</tr>";


	$lastrow = false;
	
	$yesterdate = '';
	$date = '';
	foreach($rows as $line){
		$date = userdate("M j, Y", $line['time']);

		if($yesterdate != $date){
			echo "<tr>";
			echo "<td class=body>$date</td>";
			
			echo "<td class=body align=right>" . number_format($line['userstotal']) . "</td>";
			echo "<td class=body align=right>" . number_format($line['hitstotal']) . "</td>";

			echo "</tr>";
		}

		$yesterdate = $date;
	}


	echo "</table>";

	incFooter();

