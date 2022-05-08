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

	$res = $masterdb->prepare_query("SELECT * FROM statshist WHERE time >= # && time <= #", $time, $time + 87000);

	$rows = array();
	while($line = $res->fetchrow())
		$rows[$line['time']] = $line;

	ksort($rows);

	incHeader();

	echo "<table align=center>";
	echo "<form action=$_SERVER[PHP_SELF] method=get>";
	echo "<tr><td class=header colspan=9 align=center>";
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
		echo "<td class=header align=center rowspan=2>Time</td>";
		echo "<td class=header align=center colspan=4>Hits</td>";
		echo "<td class=header align=center rowspan=2>Users</td>";
		echo "<td class=header align=center rowspan=2>Users Online</td>";
		echo "<td class=header align=center rowspan=2>Total Users</td>";
		echo "<td class=header align=center rowspan=2>Total Hits</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td class=header align=center>Anon</td>";
		echo "<td class=header align=center>Users</td>";
		echo "<td class=header align=center>Plus</td>";
		echo "<td class=header align=center>Total</td>";
		echo "</tr>";

		$classes = array('body', 'body2');
		$i = 0;

		$first = false;
		$last = false;
		$time = false;
		foreach($rows as $line){
			if(!$first){
				$first = $last = $line;
				$time = $lasttime = userdate("M j, Y, g a", $last['time']);
			}else{
				$time = userdate("M j, Y, g a", $line['time']);
			}

			if($time != $lasttime){
				echo "<tr>";
				echo "<td class=" . $classes[$i = !$i] . ">$time</td>";

				echo "<td class=" . $classes[$i] . " align=right>" . number_format($line['hitsanon'] - $last['hitsanon']) . "</td>";
				echo "<td class=" . $classes[$i] . " align=right>" . number_format($line['hitsuser'] - $last['hitsuser']) . "</td>";
				echo "<td class=" . $classes[$i] . " align=right>" . number_format($line['hitsplus'] - $last['hitsplus']) . "</td>";

				echo "<td class=" . $classes[$i] . " align=right>" . number_format($line['hitstotal'] - $last['hitstotal']) . "</td>";
				echo "<td class=" . $classes[$i] . " align=right>" . number_format($line['userstotal'] - $last['userstotal']) . "</td>";
				echo "<td class=" . $classes[$i] . " align=right>" . number_format($last['onlineusers']) . " + " . number_format($last['onlineguests']) . "</td>";
				echo "<td class=" . $classes[$i] . " align=right>" . number_format($last['userstotal']) . "</td>";
				echo "<td class=" . $classes[$i] . " align=right>" . number_format($last['hitstotal']) . "</td>";
				echo "</tr>";

				$last = $line;
				$lasttime = $time;
			}
		}

		if($today){
			echo "<tr>";
			echo "<td class=" . $classes[$i = !$i] . ">". userdate("M j, Y, g:i a") . "</td>";
			echo "<td class=" . $classes[$i] . " align=right colspan=4>" . number_format($siteStats['hitstotal'] - $last['hitstotal']) . " (" . number_format(($siteStats['hitstotal'] - $last['hitstotal'])*3600/(time()-$last['time'])) . ")</td>";
			echo "<td class=" . $classes[$i] . " align=right>" . number_format($siteStats['userstotal'] - $last['userstotal']) . "</td>";
			echo "<td class=" . $classes[$i] . " align=right>" . number_format($siteStats['onlineusers']) . " + " . number_format($siteStats['onlineguests']) . "</td>";
			echo "<td class=" . $classes[$i] . " align=right>" . number_format($siteStats['userstotal']) . "</td>";
			echo "<td class=" . $classes[$i] . " align=right>" . number_format($siteStats['hitstotal']) . "</td>";
			echo "</tr>";
			$last = array_merge($last, $siteStats);
		}

		echo "<tr>";
		echo "<td class=header></td>";
		echo "<td class=header align=right>" . number_format($last['hitsanon'] - $first['hitsanon']) . "</td>";
		echo "<td class=header align=right>" . number_format($last['hitsuser'] - $first['hitsuser']) . "</td>";
		echo "<td class=header align=right>" . number_format($last['hitsplus'] - $first['hitsplus']) . "</td>";
		echo "<td class=header align=right>" . number_format($last['hitstotal'] - $first['hitstotal']) . "</td>";
		echo "<td class=header align=right>" . number_format($last['userstotal'] - $first['userstotal']) . "</td>";
		echo "<td class=header colspan=3></td>";
		echo "</tr>";
	}

	echo "</table>";

	incFooter();

