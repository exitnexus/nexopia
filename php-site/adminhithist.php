<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"stats"))
		die("Permission denied");

	if(isset($month) && isset($day) && isset($year)){
		if(isset($action) && $action == "<--")
			$day--;
		elseif(isset($action) && $action == "-->")
			$day++;

		$time=mktime(0,0,0,$month,$day,$year);
	}else{
		$time=mktime(0,0,0,date("n"),date("j"),date("Y"));
	}

	$mods->adminlog("hit history", "Checking hit history for date " . userdate("F j, Y", $time));


	$month= userdate("n",$time);
	$day  = userdate("j",$time);
	$year = userdate("Y",$time);

	for($i=1;$i<=12;$i++)
		$months[$i] = date("F", mktime(0,0,0,$i,1,0));

	$db->query("SELECT * FROM hithist WHERE time >= $time && time < $time + 86400 ORDER BY time ASC LIMIT 24");

	$rows = array();
	while($line = $db->fetchrow())
		$rows[] = $line;

	incHeader();

	echo "<center><form action=\"$PHP_SELF\" method=get>";
	echo "<input type=submit class=body name=action value=\"<--\">";
	echo "<select class=body name=\"month\"><option value=0>Month" . make_select_list_key($months,$month) . "</select>";
	echo "<select class=body name=\"day\"><option value=0>Day" . make_select_list(range(1,31),$day) . "</select>";
	echo "<select class=body name=\"year\"><option value=0>Year" . make_select_list(array_reverse(range(2002,date("Y"))),$year) . "</select>";
	echo "<input type=submit class=body name=action value=\"Go\">";
	echo "<input type=submit class=body name=action value=\"-->\">";
	echo "</form></center>";

	if(count($rows)==0){
		echo "No data for that date";
	}else{
		echo "<table width=100%>";
		echo "<tr><td class=header>Time</td><td class=header>Hits</td><td class=header>Total Hits</td></tr>";

		$total=0;
		$final=0;
		foreach($rows as $line){
			echo "<tr><td class=body>". userdate("F j, Y, g a",$line['time']). "</td><td class=body>$line[hits]</td><td class=body>$line[total]</td></tr>";
			$total+=$line['hits'];
			$final=$line['total'];
		}
		echo "<tr><td class=header>Days Total</td><td class=header>$total</td><td class=header>$final</td></tr>";

		echo "</table>";
	}

	incFooter();
