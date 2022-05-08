<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"stats"))
		die("Permission denied");

	$type = getREQval('type', 'string', 'day');


	switch($type){
		case "month":

			$month = getREQval('month', 'int');
			$year = getREQval('year', 'int');
		
			if($month && $year){
				if(isset($action) && $action == "<--")
					$month--;
				elseif(isset($action) && $action == "-->")
					$month++;
		
				$time = usermktime(0,0,0,$month,1,$year);
				$endtime = usermktime(0,0,0,$month+1,1,$year)+1000;
			}else{
				$time = usermktime(0,0,0,userdate("n"),1,userdate("Y"));
				$endtime = usermktime(0,0,0,userdate("n")+1,1,userdate("Y"))+1000;
			}
		
			$mods->adminlog("stats history", "Checking stats history for month " . userdate("F j, Y", $time));
		
		
			$month= userdate("n", $time);
			$year = userdate("Y", $time);

			for($i=1;$i<=12;$i++)
				$months[$i] = gmdate("F", gmmktime(0,0,0,$i,1,0));
		
			$timeformat = "D M j, Y";
		
			$res = $masterdb->prepare_query("SELECT * FROM statshist WHERE time >= # && time <= #", $time, $endtime);
		
			$rows = array();
			
			$time = false;
//			$last = false;
			while($line = $res->fetchrow()){
				if($time){
					$new = userdate($timeformat, $line['time']);
				
					if($new != $time){
						$rows[$line['time']] = $line;
						$time = $new;
					}
				}else{
					$rows[$line['time']] = $line;
					$time = userdate($timeformat, $line['time']);
				}
//				$last = $line;
			}
//			if($last && $last != end($rows))
//				$rows[] = $last;

			ksort($rows);
		
			incHeader();
		
			echo "<table align=center>";
			echo "<tr><td class=header colspan=8>";
			echo "<a class=header href=$_SERVER[PHP_SELF]?type=day>Daily Hits</a> | ";
			echo "<a class=header href=$_SERVER[PHP_SELF]?type=month>Monthly Hits</a> | ";
			echo "<a class=header href=$_SERVER[PHP_SELF]?type=activity>Monthly Activity</a>";
			echo "</td></tr>";
			
			echo "<form action=$_SERVER[PHP_SELF] method=get>";
			echo "<tr><td class=header colspan=8 align=center>";
			echo "<input type=hidden name=type value=month>";
			echo "<input type=submit class=body name=action value=\"<--\">";
			echo "<select class=body name=\"month\"><option value=0>Month" . make_select_list_key($months, $month) . "</select>";
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
						$time = $lasttime = userdate($timeformat, $last['time']);
					}else{
						$time = userdate($timeformat, $line['time']);
					}
		
					if($time != $lasttime){
						echo "<tr>";
						echo "<td class=" . $classes[$i = !$i] . ">$lasttime</td>";
		
						echo "<td class=" . $classes[$i] . " align=right>" . number_format($line['hitsanon'] - $last['hitsanon']) . "</td>";
						echo "<td class=" . $classes[$i] . " align=right>" . number_format($line['hitsuser'] - $last['hitsuser']) . "</td>";
						echo "<td class=" . $classes[$i] . " align=right>" . number_format($line['hitsplus'] - $last['hitsplus']) . "</td>";
		
						echo "<td class=" . $classes[$i] . " align=right>" . number_format($line['hitstotal'] - $last['hitstotal']) . "</td>";
						echo "<td class=" . $classes[$i] . " align=right>" . number_format($line['userstotal'] - $last['userstotal']) . "</td>";
						echo "<td class=" . $classes[$i] . " align=right>" . number_format($last['userstotal']) . "</td>";
						echo "<td class=" . $classes[$i] . " align=right>" . number_format($last['hitstotal']) . "</td>";
						echo "</tr>";
		
						$last = $line;
						$lasttime = $time;
					}
				}
				
				echo "<tr>";
				echo "<td class=header></td>";
				echo "<td class=header align=right>" . number_format($last['hitsanon'] - $first['hitsanon']) . "</td>";
				echo "<td class=header align=right>" . number_format($last['hitsuser'] - $first['hitsuser']) . "</td>";
				echo "<td class=header align=right>" . number_format($last['hitsplus'] - $first['hitsplus']) . "</td>";
				echo "<td class=header align=right>" . number_format($last['hitstotal'] - $first['hitstotal']) . "</td>";
				echo "<td class=header align=right>" . number_format($last['userstotal'] - $first['userstotal']) . "</td>";
				echo "<td class=header colspan=2></td>";
				echo "</tr>";
			}
		
			echo "</table>";
		
			incFooter();
			
			break;


		case "activity":

			$month = getREQval('month', 'int');
			$year = getREQval('year', 'int');

			if($month && $year){
				if(isset($action) && $action == "<--")
					$month--;
				elseif(isset($action) && $action == "-->")
					$month++;

				$time = usermktime(0,0,0,$month,1,$year);
				$endtime = usermktime(0,0,0,$month+1,1,$year)+1000;
			}else{
				$time = usermktime(0,0,0,userdate("n"),1,userdate("Y"));
				$endtime = usermktime(0,0,0,userdate("n")+1,1,userdate("Y"))+1000;
			}
		
			$mods->adminlog("stats history", "Checking activity stats history for month " . userdate("F j, Y", $time));
		
		
			$month= userdate("n", $time);
			$year = userdate("Y", $time);

			for($i=1;$i<=12;$i++)
				$months[$i] = gmdate("F", gmmktime(0,0,0,$i,1,0));
		
			$timeformat = "D M j, Y";
		
			$res = $masterdb->prepare_query("SELECT * FROM statsactiveaccountshist WHERE time >= # && time <= # ORDER BY time", $time+86400, $endtime+86400);
		
			$rows = array();

			while($line = $res->fetchrow())
				$rows[] = $line;

			incHeader();

			echo "<table align=center>";
			echo "<tr><td class=header colspan=12>";
			echo "<a class=header href=$_SERVER[PHP_SELF]?type=day>Daily Hits</a> | ";
			echo "<a class=header href=$_SERVER[PHP_SELF]?type=month>Monthly Hits</a> | ";
			echo "<a class=header href=$_SERVER[PHP_SELF]?type=activity>Monthly Activity</a>";
			echo "</td></tr>";

			echo "<form action=$_SERVER[PHP_SELF] method=get>";
			echo "<tr><td class=header colspan=12 align=center>";
			echo "<input type=hidden name=type value=activity>";
			echo "<input type=submit class=body name=action value=\"<--\">";
			echo "<select class=body name=\"month\"><option value=0>Month" . make_select_list_key($months, $month) . "</select>";
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
//				echo "<td class=header align=center rowspan=2>Total Hits</td>";
				echo "<td class=header align=center colspan=9>Active Users in the past:</td>";
				echo "<td class=header align=center rowspan=2>Activated</td>";
				echo "<td class=header align=center rowspan=2>Total Users</td>";
				echo "</tr>";

				echo "<tr>";
				echo "<td class=header align=center>Day</td>";
				echo "<td class=header align=center>3 Days</td>";
				echo "<td class=header align=center>Week</td>";
				echo "<td class=header align=center>2 Weeks</td>";
				echo "<td class=header align=center>Month</td>";
				echo "<td class=header align=center>2 Months</td>";
				echo "<td class=header align=center>3 Months</td>";
				echo "<td class=header align=center>6 Months</td>";
				echo "<td class=header align=center>Year</td>";
				echo "</tr>";

				$classes = array('body', 'body2');
				$i = 0;

				$first = false;
				$last = false;
				$time = false;
				foreach($rows as $line){
					$time = userdate($timeformat, $line['time']-86400);

					echo "<tr>";
					echo "<td class=" . $classes[$i = !$i] . ">$time</td>";

					//echo "<td class=" . $classes[$i] . " align=right>" . number_format($line['hits']) . "</td>";

					echo "<td class=" . $classes[$i] . " align=right>" . number_format($line['day']) . "</td>";
					echo "<td class=" . $classes[$i] . " align=right>" . number_format($line['3days']) . "</td>";
					echo "<td class=" . $classes[$i] . " align=right>" . number_format($line['week']) . "</td>";
					echo "<td class=" . $classes[$i] . " align=right>" . number_format($line['2weeks']) . "</td>";
					echo "<td class=" . $classes[$i] . " align=right>" . number_format($line['month']) . "</td>";
					echo "<td class=" . $classes[$i] . " align=right>" . number_format($line['2months']) . "</td>";
					echo "<td class=" . $classes[$i] . " align=right>" . number_format($line['3months']) . "</td>";
					echo "<td class=" . $classes[$i] . " align=right>" . number_format($line['6months']) . "</td>";
					echo "<td class=" . $classes[$i] . " align=right>" . number_format($line['year']) . "</td>";
					echo "<td class=" . $classes[$i] . " align=right>" . number_format($line['activated']) . "</td>";
					echo "<td class=" . $classes[$i] . " align=right>" . number_format($line['total']) . "</td>";

					echo "</tr>";
				}
			}

			echo "</table>";

			incFooter();

			break;



		case "day":
		default:	

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
				$months[$i] = gmdate("F", gmmktime(0,0,0,$i,1,0));
		
			$timeformat = "M j, Y, g a";
		
			$res = $masterdb->prepare_query("SELECT * FROM statshist WHERE time >= # && time <= #", $time, $time + 87000);
		
			$rows = array();
			while($line = $res->fetchrow())
				$rows[$line['time']] = $line;
		
			ksort($rows);
		
			incHeader();

			echo "<table align=center>";
			echo "<tr><td class=header colspan=9>";
			echo "<a class=header href=$_SERVER[PHP_SELF]?type=day>Daily Hits</a> | ";
			echo "<a class=header href=$_SERVER[PHP_SELF]?type=month>Monthly Hits</a> | ";
			echo "<a class=header href=$_SERVER[PHP_SELF]?type=activity>Monthly Activity</a>";
			echo "</td></tr>";
			
			echo "<form action=$_SERVER[PHP_SELF] method=get>";
			echo "<tr><td class=header colspan=9 align=center>";
			echo "<input type=hidden name=type value=day>";
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
						$time = $lasttime = userdate($timeformat, $last['time']);
					}else{
						$time = userdate($timeformat, $line['time']);
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
			
			break;
	}

