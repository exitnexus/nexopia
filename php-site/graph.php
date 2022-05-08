<?

	$login = 1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"stats"))
		die("Permission denied");


	include("include/htmlgraph.php");

	switch($action){
		case "newusersday":		graphHistStats('userstotal', "New Users", usermktime(0,0,0,1,1,2006), usermktime(0,0,0,12,31,2007)+1000, 1);
		case "newusersweek":	graphHistStats('userstotal', "New Users", usermktime(0,0,0,1,1,2006), usermktime(0,0,0,12,31,2007)+1000, 7);
		case "newusersmonth":	graphHistStats('userstotal', "New Users", usermktime(0,0,0,1,1,2006), usermktime(0,0,0,12,31,2007)+1000, 30);

		case "hitsday":			graphHistStats('hitstotal', "Hits", usermktime(0,0,0,1,1,2006), usermktime(0,0,0,12,31,2007)+1000, 1);
		case "hitsweek":		graphHistStats('hitstotal', "Hits", usermktime(0,0,0,1,1,2006), usermktime(0,0,0,12,31,2007)+1000, 7);
		case "hitsmonth":		graphHistStats('hitstotal', "Hits", usermktime(0,0,0,1,1,2006), usermktime(0,0,0,12,31,2007)+1000, 30);

		case "activity":		graphActivityStats();
	}

	$options = array(
		'newusersday'   => "New Users per Day",
		'newusersweek'  => "New Users per Week",
		'newusersmonth' => "New Users per Month",

		'hitsday'       => "Hit per Day",
		'hitsweek'      => "Hits per Week",
		'hitsmonth'     => "Hits per Month",

		'activity'      => "Active users",
	);

	incHeader();

	foreach($options as $k => $v)
		echo "<a class=body href=$_SERVER[PHP_SELF]?action=$k>$v</a><br>"; 

	incFooter();
	exit;



function graphHistStats($stat, $title, $start, $end, $period){
	global $masterdb;

	switch($period){
		case 1: $timeformat = "n/j"; $timedesc = "Day";   break;
		case 7: $timeformat = "W";   $timedesc = "Week";  break;
		case 30: $timeformat = "n";  $timedesc = "Month"; break;
	}

	$res = $masterdb->unbuffered_query($masterdb->prepare("SELECT time, $stat FROM statshist WHERE time >= # && time <= # ORDER BY time", $start, $end));

	$rows = array();

	$time = false;
	$old = false;
	while($line = $res->fetchrow()){
		$newtime = userdate($timeformat, $line['time']);
		if($time){
			if($newtime != $time){
				$year = userdate("Y", $line['time']);
				$rows[$year][] = ($line[$stat] - $old[$stat] > 1000000000 ? 0 : $line[$stat] - $old[$stat]);

				$time = $newtime;
				$old = $line;
			}
		
		}else{
			$old = $line;
			$time = $newtime;
		}
	}

	$yvals = array();
	for($i = 1; $i <= 12; $i++)
		$yvals[] = array(gmdate("M", gmmktime(0,0,0,$i,1,0)), (gmdate("t", gmmktime(0,0,0,$i,1,0))/$period));


	$graph = new htmlgraph(750, 500, 'bar');
	$graph->setTitle("$title per $timedesc");
	$graph->setYName("$title");
	$graph->setXName("Date");

	foreach($rows as $yr => & $row)
		$graph->addRow($row, "$yr");

	$graph->setYVals($yvals);
	echo $graph->draw();

	exit;
}

function graphActivityStats(){
	global $masterdb;

	$res = $masterdb->unbuffered_query($masterdb->prepare("SELECT * FROM statsactiveaccountshist WHERE time > # ORDER BY time", usermktime(0,0,0,12,1,2006)));

	$data = array();
	$yvals = array();

	while($line = $res->fetchrow()){
		$date = userdate("M y", $line['time']);
		unset($line['time']);

		if(!isset($yvals[$date]))
			$yvals[$date] = array($date, 0);

		$yvals[$date][1]++;

		foreach($line as $k => $v)
			$data[$k][] = $v;
	}

	$graph = new htmlgraph(750, 500, 'layered');
	$graph->setTitle("Active accounts by time period");
	$graph->setYName("Active accounts");
	$graph->setXName("Date");
	$graph->setSpacing(0);

	$data = array_reverse($data);

	foreach($data as $period => $row)
		$graph->addRow($row, "$period");

	$graph->setYVals($yvals);
	echo $graph->draw();

	exit;
}

