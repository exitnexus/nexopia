<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"stats"))
		die("Permission denied");

	$month = getREQval('month', 'int');
	$day = getREQval('day', 'int');
	$year = getREQval('year', 'int');

	if($month && $day && $year){
		if(isset($action) && $action == "<--")
			$day--;
		elseif(isset($action) && $action == "-->")
			$day++;

		$time = usermktime(0,0,0,$month,$day,$year);
	}else{
		$time = usermktime(0,0,0,userdate("n"),userdate("j"),userdate("Y"));
	}

	$mods->adminlog("stats history", "Checking stats history for date " . userdate("F j, Y", $time));


	$month= userdate("n", $time);
	$day  = userdate("j", $time);
	$year = userdate("Y", $time);

	$today = (userdate("n j Y", $time) == userdate("n j Y"));

	for($i=1;$i<=12;$i++)
		$months[$i] = date("F", mktime(0,0,0,$i,1,0));

	$statsdb->prepare_query("SELECT * FROM statshist WHERE time >= ? && time <= ?", $time, $time + 86400);

	$rows = array();
	while($line = $statsdb->fetchrow())
		$rows[$line['time']] = $line;

	ksort($rows);


	incHeader();

	echo "<table align=center>";
	echo "<form action=$_SERVER[PHP_SELF] method=get>";
	echo "<tr><td class=header colspan=5 align=center>";
	echo "<input type=submit class=body name=action value=\"<--\">";
	echo "<select class=body name=\"month\"><option value=0>Month" . make_select_list_key($months, $month) . "</select>";
	echo "<select class=body name=\"day\"><option value=0>Day" . make_select_list(range(1,31), $day) . "</select>";
	echo "<select class=body name=\"year\"><option value=0>Year" . make_select_list(array_reverse(range(2002,userdate("Y"))), $year) . "</select>";
	echo "<input type=submit class=body name=action value=\"Go\">";
	echo "<input type=submit class=body name=action value=\"-->\">";
	echo "</td></tr>";
	echo "</form>";

	if(count($rows)==0){
		echo "<tr><td class=body>No data for that date</td></tr>";
	}else{
		echo "<tr>";
		echo "<td class=header>Time</td>";
		echo "<td class=header>Hits</td>";
		echo "<td class=header>Total Hits</td>";
		echo "<td class=header>Users</td>";
		echo "<td class=header>Users Online</td>";
		echo "</tr>";

		$first = false;
		$last = false;
		foreach($rows as $line){
			if($last){
				echo "<tr>";
				echo "<td class=body>". userdate("F j, Y, g a", $last['time']). "</td>";
				echo "<td class=body align=right>" . number_format($line['hitstotal'] - $last['hitstotal']) . "</td>";
				echo "<td class=body align=right>" . number_format($last['hitstotal']) . "</td>";
				echo "<td class=body align=right>" . number_format($last['userstotal']) . "</td>";
				echo "<td class=body align=right>" . number_format($last['onlineusers']) . " + " . number_format($last['onlineguests']) . "</td>";
				echo "</tr>";
			}else{
				$first = $line;
			}
			$last = $line;
		}

		if($today){
			echo "<tr>";
			echo "<td class=body>". userdate("F j, Y, g a") . "</td>";
			echo "<td class=body align=right>" . number_format($siteStats['hitstotal'] - $last['hitstotal']) . " (" . number_format(($siteStats['hitstotal'] - $last['hitstotal'])*3600/(time()-$last['time'])) . ")</td>";
			echo "<td class=body align=right>" . number_format($siteStats['hitstotal']) . "</td>";
			echo "<td class=body align=right>" . number_format($siteStats['userstotal']) . "</td>";
			echo "<td class=body align=right>" . number_format($siteStats['online']) . " + " . number_format($siteStats['guests']) . "</td>";
			echo "</tr>";
			$last = $siteStats;
		}

		echo "<tr>";
		echo "<td class=header></td>";
		echo "<td class=header align=right>" . number_format($last['hitstotal'] - $first['hitstotal']) . "</td>";
		echo "<td class=header></td>";
		echo "<td class=header align=right>" . number_format($last['userstotal'] - $first['userstotal']) . "</td>";
		echo "<td class=header></td>";
		echo "</tr>";
	}

	echo "</table>";

	incFooter();

