<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"stats"))
		die("Permission denied");


	$selectable=array(
						'activityStats' => "Activity Stats",
						'userStats' => "User Stats",
//						'hitstoday' => "Hits Today",
						'forumstats' => "Forum Stats",
						'skinstats' => "Skin Stats",
						'usersbyagesex' => "Users By Age/Sex",
//						'usersperloc' => "Users By location",
						'usersByLocRecur' => "Users By Location Recursive",
//						'hitsperweek' => "Hits per week",
//						'usersperweek' => "New Users per week",
						'activeusersbyage' => "Active Users by Age",
						'activeUsersByLocRecur' => "Active Users by Location",

						);

	if($mods->isAdmin($userData['userid'], "viewinvoice")){
		$selectable['plususersbyage'] = "Plus Users by Age";
		$selectable['plusUsersByLocRecur'] = "Plus Users by Location";
	}

	$selects = array();


	$select = getPOSTval('select', 'array', array('hitStats','userStats'));

	foreach($select as $v)
		if(isset($selectable[$v]))
			$selects[] = $v;

	$mods->adminlog("view stats","View Site Stats: " . implode(",", $selects));

	incHeader(750);

	echo "<table width=100%>";

	echo "<form action=$_SERVER[PHP_SELF] method=post>";

	echo "<tr><td class=header colspan=2 align=center>";
	echo "<select class=body name=select[]>";// size=5 multiple=multiple>";
	foreach($selectable as $k => $v){
		echo "<option value='$k'";
		if(in_array($k,$select))
			echo " selected";
		echo ">$v";
	}
	echo "</select>";
	echo "<input class=body type=submit value=Go>";
	echo "</td></tr>";
	echo "</form>\n";



	foreach($selects as $func)
		$func();


echo "<tr><td colspan=2 class=header align=center>General</td></tr>";
	echo "<tr><td class=body>Uptime</td><td class=body>" . exec("uptime") . "</td></tr>";

	echo "</table>\n";


	incFooter();


function hitStats(){
	global $siteStats;

	echo "<tr><td colspan=2 align=center class=header>Distribution of hits</td></tr>";
	echo "<tr><td class=body>Total hits</td><td class=body>" . number_format($siteStats['hitstotal']) ."</td></tr>";
	echo "<tr><td class=body>Anonymous hits</td><td class=body>" . number_format($siteStats['hitsanon']) ."</td></tr>";
	echo "<tr><td class=body>Logged In Users</td><td class=body>" .number_format($siteStats['hitsuser']) ."</td></tr>";
	echo "<tr><td class=body>Male</td><td class=body>" . number_format($siteStats['hitsMale']) . "</td></tr>";
	echo "<tr><td class=body>Female</td><td class=body>" . number_format($siteStats['hitsFemale']) ."</td></tr>";
}

function activityStats(){
	global $usersdb;

	$time = time();

	$thisdb = & $usersdb;

	echo "<tr><td colspan=2 align=center class=header>Activity stats</td></tr>";

	$res = $thisdb->prepare_query("SELECT count(*) AS count FROM useractivetime WHERE activetime >= ?", ($time - 3600));
	$ips = 0; while ($ipsrow = $res->fetchrow()) $ips += $ipsrow['count'];
	echo "<tr><td class=body>Accounts active in the past hour</td><td class=body>" . number_format($ips) . "</td></tr>";

	$res = $thisdb->prepare_query("SELECT count(*) AS count FROM users WHERE activetime >= ?", ($time - 86400));
	$ips = 0; while ($ipsrow = $res->fetchrow()) $ips += $ipsrow['count'];
	echo "<tr><td class=body>Accounts active in the past day</td><td class=body>" . number_format($ips) . "</td></tr>";

	$res = $thisdb->prepare_query("SELECT count(*) AS count FROM users WHERE activetime >= ?", ($time - 86400*3));
	$ips = 0; while ($ipsrow = $res->fetchrow()) $ips += $ipsrow['count'];
	echo "<tr><td class=body>Accounts active in the past 3 days</td><td class=body>" . number_format($ips) . "</td></tr>";

	$res = $thisdb->prepare_query("SELECT count(*) AS count FROM users WHERE activetime >= ?", ($time - 86400*7));
	$ips = 0; while ($ipsrow = $res->fetchrow()) $ips += $ipsrow['count'];
	echo "<tr><td class=body>Accounts active in the past week</td><td class=body>" . number_format($ips) . "</td></tr>";

	$res = $thisdb->prepare_query("SELECT count(*) AS count FROM users WHERE activetime >= ?", ($time - 86400*14));
	$ips = 0; while ($ipsrow = $res->fetchrow()) $ips += $ipsrow['count'];
	echo "<tr><td class=body>Accounts active in the past 2 weeks</td><td class=body>" . number_format($ips) . "</td></tr>";

	$res = $thisdb->prepare_query("SELECT count(*) AS count FROM users WHERE activetime >= ?", ($time - 86400*30));
	$ips = 0; while ($ipsrow = $res->fetchrow()) $ips += $ipsrow['count'];
	echo "<tr><td class=body>Accounts active in the past month</td><td class=body>" . number_format($ips) . "</td></tr>";

	$res = $thisdb->prepare_query("SELECT count(*) AS count FROM users WHERE activetime >= ?", ($time - 86400*90));
	$ips = 0; while ($ipsrow = $res->fetchrow()) $ips += $ipsrow['count'];
	echo "<tr><td class=body>Accounts active in the past 3 months</td><td class=body>" . number_format($ips) . "</td></tr>";

	$res = $thisdb->prepare_query("SELECT count(*) AS count FROM users WHERE activetime >= ?", ($time - 86400*180));
	$ips = 0; while ($ipsrow = $res->fetchrow()) $ips += $ipsrow['count'];
	echo "<tr><td class=body>Accounts active in the past 6 months</td><td class=body>" . number_format($ips) . "</td></tr>";

	$res = $thisdb->prepare_query("SELECT count(*) AS count FROM users WHERE activetime >= ?", ($time - 86400*365));
	$ips = 0; while ($ipsrow = $res->fetchrow()) $ips += $ipsrow['count'];
	echo "<tr><td class=body>Accounts active in the past year</td><td class=body>" . number_format($ips) . "</td></tr>";

	$res = $thisdb->query("SELECT count(*) AS count FROM users WHERE state = 'active'");
	$ips = 0; while ($ipsrow = $res->fetchrow()) $ips += $ipsrow['count'];
	echo "<tr><td class=body>Activated Accounts total</td><td class=body>" . number_format($ips) . "</td></tr>";

	$res = $thisdb->query("SELECT count(*) AS count FROM users");
	$ips = 0; while ($ipsrow = $res->fetchrow()) $ips += $ipsrow['count'];
	echo "<tr><td class=body>Accounts total</td><td class=body>" . number_format($ips) . "</td></tr>";
}

function userStats(){
	global $usersdb, $masterdb;

	$res = $usersdb->unbuffered_query("SELECT age, sex, total, active FROM agesexgroups WHERE age < 40");

	$total = array("Male" => 0, "Female" => 0);
	$num = array("Male" => 0, "Female" => 0);
	$active = array("Male" => 0, "Female" => 0);

	while($line = $res->fetchrow()){
		$total[$line['sex']] += $line['total']*$line['age'];
		$num[$line['sex']] += $line['total'];
		$active[$line['sex']] += $line['active'];
	}

echo "<tr><td colspan=2 align=center class=header>User Stats</td></tr>";
	echo "<tr><td class=body>Number of Male</td><td class=body>". number_format($num['Male']) . " - " . number_format(100*$num['Male']/($num['Male']+$num['Female']), 2) . "%</td></tr>";
	echo "<tr><td class=body>Number of Female</td><td class=body>". number_format($num['Female']) . " - " . number_format(100*$num['Female']/($num['Male']+$num['Female']), 2) . "%</td></tr>";
	echo "<tr><td class=body>Active Male</td><td class=body>". number_format($active['Male']) . " - " . number_format(100*$active['Male']/$num['Male'], 2) . "%</td></tr>";
	echo "<tr><td class=body>Active Female</td><td class=body>". number_format($active['Female']) . " - " . number_format(100*$active['Female']/$num['Female'], 2) . "%</td></tr>";

	echo "<tr><td class=body>Average Age</td><td class=body>" . number_format(($total['Male']+$total['Female'])/($num['Male']+$num['Female']),2) . "</td></tr>";
	echo "<tr><td class=body>Average Age Male</td><td class=body>" . number_format($total['Male']/$num['Male'],2) . "</td></tr>";
	echo "<tr><td class=body>Average Age Female</td><td class=body>" . number_format($total['Female']/$num['Female'],2) . "</td></tr>";

	$res = $usersdb->unbuffered_query("SELECT age, count(*) as count FROM usersearch WHERE active = 2 && age < 40 GROUP BY age");

	$total=0;
	$num=0;
	while($line = $res->fetchrow()){
		$total+=$line['age']*$line['count'];
		$num+=$line['count'];
	}

	echo "<tr><td class=body>Average Age Online</td><td class=body>" . number_format($total/$num,2) . "</td></tr>";

	$res = $masterdb->query("SELECT MAX(onlineusers) FROM statshist");
	$num = $res->fetchfield();

	echo "<tr><td class=body>Max Users Online at a time</td><td class=body>" . number_format($num) ."</td></tr>";

/*
	$next = $usersdb->nextAuto("users");

	$res = $usersdb->prepare_query("SELECT count(*) FROM users WHERE jointime >= # && userid >= #", (time()-86400), round($next*0.98)); //only check the last 5% of users
	$numnew = $res->fetchfield();

	echo "<tr><td class=body>New accounts in the past day</td><td class=body>" . number_format($numnew) . "</td></tr>";
*/
}

function forumstats(){
	global $forums, $usersdb;

	echo "<tr><td class=header align=center colspan=2>Forum Stats</td></tr>";

	$res = $forums->db->prepare_query("SELECT count(*) as total, count(DISTINCT authorid) as users FROM forumposts WHERE time >= #", time() - 86400);
	$line = $res->fetchrow();

	echo "<tr><td class=body>Number of posts today:</td><td class=body>$line[total]</td></tr>";

	$res = $forums->db->prepare_query("SELECT count(*) FROM forumthreads WHERE time >= ?", time() - 86400);
	$num = $res->fetchfield();

	echo "<tr><td class=body>Threads with new posts today:</td><td class=body>$num</td></tr>";

	$res = $forums->db->prepare_query("SELECT count(DISTINCT userid) FROM forumread WHERE readtime >= ?", time() - 86400);
	$num = $res->fetchfield();

	echo "<tr><td class=body>Users reading the forums today:</td><td class=body>$num</td></tr>";

	echo "<tr><td class=body>Users posting in the forums today:</td><td class=body>$line[users]</td></tr>";


	echo "<tr><td class=header align=center colspan=2>User Posting Stats</td></tr>";

	$vals = array(1,10,20,50,100,200,500,1000,2000,5000,10000);

	foreach($vals as $val){
		$res = $usersdb->prepare_query("SELECT count(*) AS count FROM users WHERE posts >= ?", $val);
		$num = 0;
		while ($nums = $res->fetchrow())
			$num += $nums['count'];

		echo "<tr><td class=body>Users with at least $val posts:</td><td class=body>$num</td></tr>";
	}
}

function usersbyagesex(){
	global $usersdb;

	$res = $usersdb->query("SELECT age,sex,total FROM agesexgroups ORDER BY age ASC");

	echo "<tr><td class=header align=center colspan=2>Number of Users by Age and Sex</td></tr>";
	echo "<tr><td class=body colspan=2>";
	echo "<table>";
	echo "<tr><td class=header>Age</td><td class=header>Male</td><td class=header>Male</td><td class=header>Female</td><td class=header>Female</td><td class=header>Total</td><td class=header>Total</td></tr>";

	$total = 0;
	$rows = array();
	while($line = $res->fetchrow()){
		if (!isset($rows[$line['age']][$line['sex']]))
			$rows[$line['age']][$line['sex']] = $line['total'];
		else
			$rows[$line['age']][$line['sex']] += $line['total'];
		$total += $line['total'];
	}

	foreach($rows as $age => $line){
		echo "<tr>";
		echo "<td class=body>$age</td>";
		echo "<td class=body>$line[Male]</td><td class=body>" . number_format(($line['Male']/$total)*100,2) . "%</td>";
		echo "<td class=body>$line[Female]</td><td class=body>" . number_format(($line['Female']/$total)*100,2) . "%</td>";
		echo "<td class=body>" . ($line['Male'] + $line['Female']) . "</td><td class=body>" . number_format((($line['Male'] + $line['Female'])/$total)*100,2) . "%</td>";
		echo "</tr>";
	}

	echo "</table>";
	echo "</td></tr>";
}

function getlocinfo()
{
	global $configdb;

	$locs = array();
	$res = $configdb->query("SELECT id, parent, name FROM locs");
	while ($line = $res->fetchrow())
	{
		$locs[$line['id']] = $line;
	}
	return $locs;
}

function usersperloc(){
	global $usersdb;

	$names = getlocnames();
	$res = $usersdb->query("SELECT id, users FROM locstats ORDER BY users DESC LIMIT 50");

	echo "<tr><td class=header align=center colspan=2>Number of Users by Location</td></tr>";

	while($line = $res->fetchrow())
		echo "<tr><td class=body>{$names[$line['id']]}</td><td class=body>$line[users]</td></tr>";
}

function usersByLocRecur(){
	global $usersdb, $configdb;

	function recurTotal(& $locs, &$parents, $id){
		$total = $locs[$id]['users'];

		if(isset($parents[$id]))
			foreach($parents[$id] as $child)
				$total += recurTotal($locs, $parents, $child);

		$locs[$id]['total'] = $total;

		return $total;
	}

	function recurOutput(&$locs, &$parents, $id, $level, $total, $threshhold = 0){
		echo "<tr>";
		echo "<td class=body>" . str_repeat("- ", $level) . $locs[$id]['name'] . "</td>";
		echo "<td class=body align=right>" . number_format($locs[$id]['users']) . "</td><td class=body align=right>" . number_format(100*$locs[$id]['users']/$total, 2) . "%</td>";
		echo "<td class=body align=right>" . number_format($locs[$id]['total']) . "</td><td class=body align=right>" . number_format(100*$locs[$id]['total']/$total, 2) . "%</td>";
		echo "</tr>";

		if(isset($parents[$id]))
			foreach($parents[$id] as $child)
				if(!$threshhold || $locs[$child]['total'] >= $threshhold)
					recurOutput($locs, $parents, $child, $level+1, $total, $threshhold);
	}

	$info = getlocinfo();
	$res = $usersdb->query("SELECT id, users FROM locstats");

	$locs = array();
	$parents = array();

	while($line = $res->fetchrow()){
		if (!isset($locs[$line['id']]))
		{
			$line['name'] = $info[$line['id']]['name'];
			$line['parent'] = $info[$line['id']]['parent'];
			$locs[$line['id']] = $line;
			$locs[$line['id']]['total'] = 0;
			$parents[$line['parent']][] = $line['id'];
		} else
			$locs[$line['id']]['users'] += $line['users'];
	}

	$total = 0;
	foreach($parents[0] as $child)
		$total += recurTotal($locs, $parents, $child);

	echo "<tr><td class=header align=center colspan=2>Users By Location</td></tr>";
	echo "<tr><td class=body colspan=2>";

	echo "<table>";
	echo "<tr>";
	echo "<td class=header>Location</td>";
	echo "<td class=header>Users</td><td class=header>Users</td>";
	echo "<td class=header>Total</td><td class=header>Total</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class=body>Location</td>";
	echo "<td class=body align=right>0</td><td class=body align=right>0.00%</td>";
	echo "<td class=body align=right>" . number_format($total) . "</td><td class=body align=right>100.00%</td>";
	echo "</tr>";

	foreach($parents[0] as $child)
		recurOutput($locs, $parents, $child, 1, $total, 0);
	echo "</table>";

	echo "</td></tr>";
}

function skinstats(){
	global $usersdb, $skins;

	$res = $usersdb->query("SELECT skin, count(*) as count FROM users GROUP BY skin ORDER BY count DESC");
	$skinlist = array();
	while ($line = $res->fetchrow())
	{
		$skin = $line['skin'];
		if (isset($skinlist[$skin]))
			$skinlist[$skin] += $line['count'];
		else
			$skinlist[$skin] = $line['count'];
	}

	foreach ($skinlist as $skin => $count) {
		echo "<tr><td class=body>";
		if($skin == "")
			echo "default";
		elseif(isset($skins[$skin]))
			echo $skins[$skin]['name'];
		else
			echo "unknown: $skin";
		echo "</td><td class=body>$count</td>";
		echo "</tr>";
	}

}

function plususersbyage(){
	global $usersdb;

	$rows = array();

	$total = array('Male' => 0, 'Female' => 0);
	$totalplus = array('Male' => 0, 'Female' => 0);

	for($i = 14; $i <= 63; $i++)
		$rows[$i] = array(	"Male" => array('users' => 0, 'plususers' => 0),
							"Female" => array('users' => 0, 'plususers' => 0));

	$res = $usersdb->query("SELECT age, sex, count(*) as count FROM users WHERE state = 'active' GROUP BY age, sex ORDER BY age ASC");

	while($line = $res->fetchrow()){
		if (!isset($rows[$line['age']][$line['sex']]['users']))
			$rows[$line['age']][$line['sex']]['users'] = $line['count'];
		else
			$rows[$line['age']][$line['sex']]['users'] += $line['count'];
		$total[$line['sex']] += $line['count'];
	}

	$res = $usersdb->prepare_query("SELECT age, sex, count(*) as count FROM users WHERE premiumexpiry > # GROUP BY age, sex", time());

	while($line = $res->fetchrow()){
		if (!isset($rows[$line['age']][$line['sex']]['plususers']))
			$rows[$line['age']][$line['sex']]['plususers'] = $line['count'];
		else
			$rows[$line['age']][$line['sex']]['plususers'] += $line['count'];
		$totalplus[$line['sex']] += $line['count'];
	}

	ksort($rows);

	echo "<tr><td class=header align=center colspan=2>Users By Location</td></tr>";
	echo "<tr><td class=body colspan=2>";

	echo "<table>";
	echo "<tr>";
	echo "<td class=header rowspan=2>Age</td>";
	echo "<td class=header colspan=6 align=center>Plus Users</td>";
	echo "<td class=header colspan=6 align=center>Users</td>";
	echo "<td class=header colspan=3 align=center>Penetration</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td class=header>Male</td><td class=header>Male</td>";
	echo "<td class=header>Female</td><td class=header>Female</td>";
	echo "<td class=header>Total</td><td class=header>Total</td>";

	echo "<td class=header>Male</td><td class=header>Male</td>";
	echo "<td class=header>Female</td><td class=header>Female</td>";
	echo "<td class=header>Total</td><td class=header>Total</td>";

	echo "<td class=header>Male</td>";
	echo "<td class=header>Female</td>";
	echo "<td class=header>Total</td>";
	echo "</tr>";


	foreach($rows as $age => $row){
		echo "<tr>";
		echo "<td class=body>$age</td>";

		echo "<td class=body align=right>" . number_format($row['Male']['plususers']) . "</td><td class=body align=right>" . number_format(100*$row['Male']['plususers']/($totalplus['Male']+$totalplus['Female']), 2) . "%</td>";
		echo "<td class=body align=right>" . number_format($row['Female']['plususers']) . "</td><td class=body align=right>" . number_format(100*$row['Female']['plususers']/($totalplus['Male']+$totalplus['Female']), 2) . "%</td>";
		echo "<td class=body align=right>" . number_format(($row['Male']['plususers']+$row['Female']['plususers'])) . "</td><td class=body align=right>" . number_format(100*($row['Male']['plususers']+$row['Female']['plususers'])/($totalplus['Male']+$totalplus['Female']), 2) . "%</td>";

		echo "<td class=body align=right>" . number_format($row['Male']['users']) . "</td><td class=body align=right>" . number_format(100*$row['Male']['users']/($total['Male']+$total['Female']), 2) . "%</td>";
		echo "<td class=body align=right>" . number_format($row['Female']['users']) . "</td><td class=body align=right>" . number_format(100*$row['Female']['users']/($total['Male']+$total['Female']), 2) . "%</td>";
		echo "<td class=body align=right>" . number_format(($row['Male']['users']+$row['Female']['users'])) . "</td><td class=body align=right>" . number_format(100*($row['Male']['users']+$row['Female']['users'])/($total['Male']+$total['Female']), 2) . "%</td>";

		echo "<td class=body align=right>" . number_format(100*$row['Male']['plususers']/$row['Male']['users'], 2) . "%</td>";
		echo "<td class=body align=right>" . number_format(100*$row['Female']['plususers']/$row['Female']['users'], 2) . "%</td>";
		echo "<td class=body align=right>" . number_format(100*($row['Male']['plususers']+$row['Female']['plususers'])/($row['Male']['users']+$row['Female']['users']), 2) . "%</td>";

		echo "</tr>";
	}

	echo "<tr>";
	echo "<td class=header></td>";

	echo "<td class=header align=right>" . number_format($totalplus['Male']) . "</td><td class=header align=right>" . number_format(100*$totalplus['Male']/($totalplus['Male']+$totalplus['Female']), 2) . "%</td>";
	echo "<td class=header align=right>" . number_format($totalplus['Female']) . "</td><td class=header align=right>" . number_format(100*$totalplus['Female']/($totalplus['Male']+$totalplus['Female']), 2) . "%</td>";
	echo "<td class=header align=right>" . number_format(($totalplus['Male']+$totalplus['Female'])) . "</td><td class=header align=right>100%</td>";

	echo "<td class=header align=right>" . number_format($total['Male']) . "</td><td class=header align=right>" . number_format(100*$total['Male']/($total['Male']+$total['Female']), 2) . "%</td>";
	echo "<td class=header align=right>" . number_format($total['Female']) . "</td><td class=header align=right>" . number_format(100*$total['Female']/($total['Male']+$total['Female']), 2) . "%</td>";
	echo "<td class=header align=right>" . number_format(($total['Male']+$total['Female'])) . "</td><td class=header align=right>100%</td>";

	echo "<td class=header align=right>" . number_format(100*$totalplus['Male']/$total['Male'], 2) . "%</td>";
	echo "<td class=header align=right>" . number_format(100*$totalplus['Female']/$total['Female'], 2) . "%</td>";
	echo "<td class=header align=right>" . number_format(100*($totalplus['Male']+$totalplus['Female'])/($total['Male']+$total['Female']), 2) . "%</td>";

	echo "</tr>";

	echo "</table>";

	echo "</td></tr>";
}

function plusUsersByLocRecur(){
	global $usersdb, $configdb;

	function recurTotal(& $locs, &$parents, $id){
		$total = $locs[$id]['users'];

		if(isset($parents[$id]))
			foreach($parents[$id] as $child)
				$total += recurTotal($locs, $parents, $child);

		$locs[$id]['total'] = $total;

		return $total;
	}

	function recurTotalPlus(& $locs, &$parents, $id){
		$total = $locs[$id]['plususers'];

		if(isset($parents[$id]))
			foreach($parents[$id] as $child)
				$total += recurTotalPlus($locs, $parents, $child);

		$locs[$id]['totalplus'] = $total;

		return $total;
	}

	function recurOutput(&$locs, &$parents, $id, $level, $total, $threshhold = 0){
		echo "<tr>";
		echo "<td class=body>" . str_repeat("- ", $level) . $locs[$id]['name'] . "</td>";

		echo "<td class=body align=right>" . number_format($locs[$id]['plususers']) . "</td><td class=body align=right>" . number_format(100*$locs[$id]['plususers']/$total, 2) . "%</td>";
		echo "<td class=body align=right>" . number_format($locs[$id]['totalplus']) . "</td><td class=body align=right>" . number_format(100*$locs[$id]['totalplus']/$total, 2) . "%</td>";

		echo "<td class=body align=right>" . number_format($locs[$id]['users']) . "</td><td class=body align=right>" . number_format(100*$locs[$id]['users']/$total, 2) . "%</td>";
		echo "<td class=body align=right>" . number_format($locs[$id]['total']) . "</td><td class=body align=right>" . number_format(100*$locs[$id]['total']/$total, 2) . "%</td>";

		echo "<td class=body align=right>" . number_format(100*$locs[$id]['plususers']/$locs[$id]['users'], 2) . "%</td>";
		echo "<td class=body align=right>" . number_format(100*$locs[$id]['totalplus']/$locs[$id]['total'], 2) . "%</td>";

		echo "</tr>";

		if(isset($parents[$id]))
			foreach($parents[$id] as $child)
				if(!$threshhold || $locs[$child]['total'] >= $threshhold)
					recurOutput($locs, $parents, $child, $level+1, $total, $threshhold);
	}

	$info = getlocinfo();
	$res = $usersdb->query("SELECT id, users, 0 as plususers FROM locstats");

	$locs = array();
	$parents = array();

	while($line = $res->fetchrow()){
		if (!isset($locs[$line['id']]))
		{
			$line['name'] = $info[$line['id']]['name'];
			$line['parent'] = $info[$line['id']]['parent'];
			$locs[$line['id']] = $line;
			$locs[$line['id']]['total'] = 0;
			$locs[$line['id']]['totalplus'] = 0;
			$parents[$line['parent']][] = $line['id'];
		} else
			$locs[$line['id']]['users'] += $line['users'];
	}
	
	$res = $usersdb->prepare_query("SELECT loc, count(*) as count FROM users WHERE premiumexpiry > ? GROUP BY loc ORDER BY count DESC", time());

	while($line = $res->fetchrow())
		$locs[$line['loc']]['plususers'] = $line['count'];


	$total = 0;
	foreach($parents[0] as $child)
		$total += recurTotal($locs, $parents, $child);

	$plustotal = 0;
	foreach($parents[0] as $child)
		$plustotal += recurTotalPlus($locs, $parents, $child);

	echo "<tr><td class=header align=center colspan=2>Users By Location</td></tr>";
	echo "<tr><td class=body colspan=2>";

	echo "<table>";
	echo "<tr>";
	echo "<td class=header rowspan=2>Location</td>";
	echo "<td class=header colspan=4 align=center>Plus Users</td>";
	echo "<td class=header colspan=4 align=center>Users</td>";
	echo "<td class=header colspan=2 align=center>Penetration</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td class=header>Users</td><td class=header>Users</td>";
	echo "<td class=header>Total</td><td class=header>Total</td>";
	echo "<td class=header>Users</td><td class=header>Users</td>";
	echo "<td class=header>Total</td><td class=header>Total</td>";
	echo "<td class=header>Users</td><td class=header>Total</td>";
	echo "</tr>";


	echo "<tr>";
	echo "<td class=body>Location</td>";

	echo "<td class=body align=right>0</td><td class=body align=right>0.00%</td>";
	echo "<td class=body align=right>" . number_format($plustotal) . "</td><td class=body align=right>100.00%</td>";

	echo "<td class=body align=right>0</td><td class=body align=right>0.00%</td>";
	echo "<td class=body align=right>" . number_format($total) . "</td><td class=body align=right>100.00%</td>";

	echo "<td class=body align=right></td>";
	echo "<td class=body align=right>" . number_format(100*$plustotal/$total,2) . "%</td>";
	echo "</tr>";

	foreach($parents[0] as $child)
		recurOutput($locs, $parents, $child, 1, $total, 0);
	echo "</table>";

	echo "</td></tr>";
}






function activeusersbyage(){
	global $usersdb;

	$rows = array();
	$total = array();
	$totalplus = array();

	for($i = 14; $i <= 63; $i++)
		$rows[$i] = array(	"Male" => array('users' => 0, 'plususers' => 0),
							"Female" => array('users' => 0, 'plususers' => 0));

	$res = $usersdb->query("SELECT age, sex, count(*) as count FROM users WHERE state = 'active' GROUP BY age, sex ORDER BY age ASC");

	while($line = $res->fetchrow()){
		$rows[$line['age']][$line['sex']]['users'] = $line['count'];
		$total[$line['sex']] += $line['count'];
	}

	$res = $usersdb->prepare_query("SELECT age, sex, count(*) as count FROM users WHERE activetime > # GROUP BY age, sex", time() - 7*86400);

	while($line = $res->fetchrow()){
		$rows[$line['age']][$line['sex']]['plususers'] = $line['count'];
		$totalplus[$line['sex']] += $line['count'];
	}

	ksort($rows);

	echo "<tr><td class=header align=center colspan=2>Users By Location</td></tr>";
	echo "<tr><td class=body colspan=2>";

	echo "<table>";
	echo "<tr>";
	echo "<td class=header rowspan=2>Age</td>";
	echo "<td class=header colspan=6 align=center>Active Users</td>";
	echo "<td class=header colspan=6 align=center>Users</td>";
	echo "<td class=header colspan=3 align=center>Penetration</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td class=header>Male</td><td class=header>Male</td>";
	echo "<td class=header>Female</td><td class=header>Female</td>";
	echo "<td class=header>Total</td><td class=header>Total</td>";

	echo "<td class=header>Male</td><td class=header>Male</td>";
	echo "<td class=header>Female</td><td class=header>Female</td>";
	echo "<td class=header>Total</td><td class=header>Total</td>";

	echo "<td class=header>Male</td>";
	echo "<td class=header>Female</td>";
	echo "<td class=header>Total</td>";
	echo "</tr>";


	foreach($rows as $age => $row){
		echo "<tr>";
		echo "<td class=body>$age</td>";

		echo "<td class=body align=right>" . number_format($row['Male']['plususers']) . "</td><td class=body align=right>" . number_format(100*$row['Male']['plususers']/($totalplus['Male']+$totalplus['Female']), 2) . "%</td>";
		echo "<td class=body align=right>" . number_format($row['Female']['plususers']) . "</td><td class=body align=right>" . number_format(100*$row['Female']['plususers']/($totalplus['Male']+$totalplus['Female']), 2) . "%</td>";
		echo "<td class=body align=right>" . number_format(($row['Male']['plususers']+$row['Female']['plususers'])) . "</td><td class=body align=right>" . number_format(100*($row['Male']['plususers']+$row['Female']['plususers'])/($totalplus['Male']+$totalplus['Female']), 2) . "%</td>";

		echo "<td class=body align=right>" . number_format($row['Male']['users']) . "</td><td class=body align=right>" . number_format(100*$row['Male']['users']/($total['Male']+$total['Female']), 2) . "%</td>";
		echo "<td class=body align=right>" . number_format($row['Female']['users']) . "</td><td class=body align=right>" . number_format(100*$row['Female']['users']/($total['Male']+$total['Female']), 2) . "%</td>";
		echo "<td class=body align=right>" . number_format(($row['Male']['users']+$row['Female']['users'])) . "</td><td class=body align=right>" . number_format(100*($row['Male']['users']+$row['Female']['users'])/($total['Male']+$total['Female']), 2) . "%</td>";

		echo "<td class=body align=right>" . number_format(100*$row['Male']['plususers']/$row['Male']['users'], 2) . "%</td>";
		echo "<td class=body align=right>" . number_format(100*$row['Female']['plususers']/$row['Female']['users'], 2) . "%</td>";
		echo "<td class=body align=right>" . number_format(100*($row['Male']['plususers']+$row['Female']['plususers'])/($row['Male']['users']+$row['Female']['users']), 2) . "%</td>";

		echo "</tr>";
	}

	echo "<tr>";
	echo "<td class=header></td>";

	echo "<td class=header align=right>" . number_format($totalplus['Male']) . "</td><td class=header align=right>" . number_format(100*$totalplus['Male']/($totalplus['Male']+$totalplus['Female']), 2) . "%</td>";
	echo "<td class=header align=right>" . number_format($totalplus['Female']) . "</td><td class=header align=right>" . number_format(100*$totalplus['Female']/($totalplus['Male']+$totalplus['Female']), 2) . "%</td>";
	echo "<td class=header align=right>" . number_format(($totalplus['Male']+$totalplus['Female'])) . "</td><td class=header align=right>100%</td>";

	echo "<td class=header align=right>" . number_format($total['Male']) . "</td><td class=header align=right>" . number_format(100*$total['Male']/($total['Male']+$total['Female']), 2) . "%</td>";
	echo "<td class=header align=right>" . number_format($total['Female']) . "</td><td class=header align=right>" . number_format(100*$total['Female']/($total['Male']+$total['Female']), 2) . "%</td>";
	echo "<td class=header align=right>" . number_format(($total['Male']+$total['Female'])) . "</td><td class=header align=right>100%</td>";

	echo "<td class=header align=right>" . number_format(100*$totalplus['Male']/$total['Male'], 2) . "%</td>";
	echo "<td class=header align=right>" . number_format(100*$totalplus['Female']/$total['Female'], 2) . "%</td>";
	echo "<td class=header align=right>" . number_format(100*($totalplus['Male']+$totalplus['Female'])/($total['Male']+$total['Female']), 2) . "%</td>";

	echo "</tr>";

	echo "</table>";

	echo "</td></tr>";
}

function activeUsersByLocRecur(){
	global $usersdb, $configdb;

	function recurTotal(& $locs, &$parents, $id){
		$total = $locs[$id]['users'];

		if(isset($parents[$id]))
			foreach($parents[$id] as $child)
				$total += recurTotal($locs, $parents, $child);

		$locs[$id]['total'] = $total;

		return $total;
	}

	function recurTotalPlus(& $locs, &$parents, $id){
		$total = $locs[$id]['plususers'];

		if(isset($parents[$id]))
			foreach($parents[$id] as $child)
				$total += recurTotalPlus($locs, $parents, $child);

		$locs[$id]['totalplus'] = $total;

		return $total;
	}

	function recurOutput(&$locs, &$parents, $id, $level, $total, $threshhold = 0){
		echo "<tr>";
		echo "<td class=body>" . str_repeat("- ", $level) . $locs[$id]['name'] . "</td>";

		echo "<td class=body align=right>" . number_format($locs[$id]['plususers']) . "</td><td class=body align=right>" . number_format(100*$locs[$id]['plususers']/$total, 2) . "%</td>";
		echo "<td class=body align=right>" . number_format($locs[$id]['totalplus']) . "</td><td class=body align=right>" . number_format(100*$locs[$id]['totalplus']/$total, 2) . "%</td>";

		echo "<td class=body align=right>" . number_format($locs[$id]['users']) . "</td><td class=body align=right>" . number_format(100*$locs[$id]['users']/$total, 2) . "%</td>";
		echo "<td class=body align=right>" . number_format($locs[$id]['total']) . "</td><td class=body align=right>" . number_format(100*$locs[$id]['total']/$total, 2) . "%</td>";

		echo "<td class=body align=right>" . number_format(100*$locs[$id]['plususers']/$locs[$id]['users'], 2) . "%</td>";
		echo "<td class=body align=right>" . number_format(100*$locs[$id]['totalplus']/$locs[$id]['total'], 2) . "%</td>";

		echo "</tr>";

		if(isset($parents[$id]))
			foreach($parents[$id] as $child)
				if(!$threshhold || $locs[$child]['total'] >= $threshhold)
					recurOutput($locs, $parents, $child, $level+1, $total, $threshhold);
	}


	$info = getlocinfo();
	$res = $usersdb->query("SELECT id, users, 0 as plususers FROM locstats");

	$locs = array();
	$parents = array();

	while($line = $res->fetchrow()){
		if (!isset($locs[$line['id']]))
		{
			$line['name'] = $info[$line['id']]['name'];
			$line['parent'] = $info[$line['id']]['parent'];
			$locs[$line['id']] = $line;
			$locs[$line['id']]['total'] = 0;
			$locs[$line['id']]['totalplus'] = 0;
			$parents[$line['parent']][] = $line['id'];
		} else
			$locs[$line['id']]['users'] += $line['users'];
	}

	$res = $usersdb->prepare_query("SELECT loc, count(*) as count FROM users WHERE activetime > # GROUP BY loc ORDER BY count DESC", time() - 86400*7);

	while($line = $res->fetchrow())
		$locs[$line['loc']]['plususers'] = $line['count'];


	$total = 0;
	foreach($parents[0] as $child)
		$total += recurTotal($locs, $parents, $child);

	$plustotal = 0;
	foreach($parents[0] as $child)
		$plustotal += recurTotalPlus($locs, $parents, $child);

	echo "<tr><td class=header align=center colspan=2>Users By Location</td></tr>";
	echo "<tr><td class=body colspan=2>";

	echo "<table>";
	echo "<tr>";
	echo "<td class=header rowspan=2>Location</td>";
	echo "<td class=header colspan=4 align=center>Active Users</td>";
	echo "<td class=header colspan=4 align=center>Users</td>";
	echo "<td class=header colspan=2 align=center>Penetration</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td class=header>Users</td><td class=header>Users</td>";
	echo "<td class=header>Total</td><td class=header>Total</td>";
	echo "<td class=header>Users</td><td class=header>Users</td>";
	echo "<td class=header>Total</td><td class=header>Total</td>";
	echo "<td class=header>Users</td><td class=header>Total</td>";
	echo "</tr>";


	echo "<tr>";
	echo "<td class=body>Location</td>";

	echo "<td class=body align=right>0</td><td class=body align=right>0.00%</td>";
	echo "<td class=body align=right>" . number_format($plustotal) . "</td><td class=body align=right>100.00%</td>";

	echo "<td class=body align=right>0</td><td class=body align=right>0.00%</td>";
	echo "<td class=body align=right>" . number_format($total) . "</td><td class=body align=right>100.00%</td>";

	echo "<td class=body align=right></td>";
	echo "<td class=body align=right>" . number_format(100*$plustotal/$total,2) . "%</td>";
	echo "</tr>";

	foreach($parents[0] as $child)
		recurOutput($locs, $parents, $child, 1, $total, 0);
	echo "</table>";

	echo "</td></tr>";
}
