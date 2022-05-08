<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"stats"))
		die("Permission denied");


	$selectable=array(
						'userStats' => "User Stats",
						'forumstats' => "Forum Stats",
						'skinstats' => "Skin Stats",
						'usersbyagesex' => "Users By Age/Sex",
						'userInterestsRecur' => "User Interests",
						'usersByLocRecur' => "Users By Location Recursive",
						'activeusersbyage' => "Active Users by Age",
						'activeUsersByLocRecur' => "Active Users by Location",
						);

	if($mods->isAdmin($userData['userid'], "viewinvoice")){
		$selectable['plususersbyage'] = "Plus Users by Age";
		$selectable['plusUsersByLocRecur'] = "Plus Users by Location";
		
		$selectable['plusBuyHistory'] = "Plus Buying History - slow";
		$selectable['plusHabits'] = "Plus Habits - slow";
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

function getlocinfo(){
	global $configdb;

	$locs = array();
	$res = $configdb->query("SELECT id, parent, name FROM locs");
	while ($line = $res->fetchrow())
	{
		$locs[$line['id']] = $line;
	}
	return $locs;
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
		if(!isset($locs[$line['id']])){
			$line['name'] = $info[$line['id']]['name'];
			$line['parent'] = $info[$line['id']]['parent'];
			$locs[$line['id']] = $line;
			$locs[$line['id']]['total'] = 0;
			$parents[$line['parent']][] = $line['id'];
		}else{
			$locs[$line['id']]['users'] += $line['users'];
		}
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

	$skinlist = array();
	
	foreach($skins as $skinname => $v)
		$skinlist[$skinname] = array('total' => 0, 'active' => 0);

//total
	$res = $usersdb->query("SELECT skin, count(*) as count FROM users GROUP BY skin");

	while($line = $res->fetchrow()){
		$skin = $line['skin'];

		if(!isset($skinlist[$skin]))
			$skinlist[$skin] = array('total' => 0, 'active' => 0);
		
		$skinlist[$skin]['total'] += $line['count'];
	}

//active
	$res = $usersdb->prepare_query("SELECT skin, count(*) as count FROM users WHERE activetime > # GROUP BY skin", time() - 86400*7);

	while($line = $res->fetchrow()){
		$skin = $line['skin'];

		if(!isset($skinlist[$skin]))
			$skinlist[$skin] = array('total' => 0, 'active' => 0);
		
		$skinlist[$skin]['active'] += $line['count'];
	}


	sortCols($skinlist, SORT_DESC, SORT_NUMERIC, 'total', SORT_DESC, SORT_NUMERIC, 'active');

	echo "<tr><td class=body colspan=2>";
	
	echo "<table>";

	echo "<tr>";
	echo "<td class=header>Name</td>";
	echo "<td class=header>Total</td>";
	echo "<td class=header>Active</td>";
	echo "</tr>";

	foreach($skinlist as $skinname => $skin) {
		echo "<tr><td class=body>";
		if($skinname == "")
			echo "default";
		elseif(isset($skins[$skinname]))
			echo $skins[$skinname];
		else
			echo "unknown: $skinname";
		echo "</td>";
		echo "<td class=body>$skin[total]</td>";
		echo "<td class=body>$skin[active]</td>";
		echo "</tr>";
	}
	
	echo "</table>";
	echo "</td></tr>";
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
	
	$res = $usersdb->prepare_query("SELECT loc, count(*) as count FROM users WHERE premiumexpiry > # GROUP BY loc ORDER BY count DESC", time());

	while($line = $res->fetchrow())
		$locs[$line['loc']]['plususers'] += $line['count'];


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
	$total = array("Male" => 0, "Female" => 0);
	$totalactive = array("Male" => 0, "Female" => 0);

	for($i = 14; $i <= 63; $i++)
		$rows[$i] = array(	"Male" => array('users' => 0, 'activeusers' => 0),
							"Female" => array('users' => 0, 'activeusers' => 0));

//total activated accounts
	$res = $usersdb->query("SELECT age, sex, count(*) as count FROM users WHERE state = 'active' GROUP BY age, sex ORDER BY age ASC");

	while($line = $res->fetchrow()){
		$rows[$line['age']][$line['sex']]['users'] += $line['count'];
		$total[$line['sex']] += $line['count'];
	}

//total accounts active in the past week
	$res = $usersdb->prepare_query("SELECT age, sex, count(*) as count FROM users WHERE activetime > # GROUP BY age, sex", time() - 7*86400);

	while($line = $res->fetchrow()){
		$rows[$line['age']][$line['sex']]['activeusers'] += $line['count'];
		$totalactive[$line['sex']] += $line['count'];
	}

	ksort($rows);

	echo "<tr><td class=header align=center colspan=2>Active Users By Age</td></tr>";
	echo "<tr><td class=body colspan=2>";

	echo "<table>";
	echo "<tr>";
	echo "<td class=header rowspan=2>Age</td>";
	echo "<td class=header colspan=6 align=center>Active Users</td>";
	echo "<td class=header colspan=6 align=center>Total Users</td>";
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

		echo "<td class=body align=right>" . number_format($row['Male']['activeusers']) . "</td><td class=body align=right>" . number_format(100*$row['Male']['activeusers']/($totalactive['Male']+$totalactive['Female']), 2) . "%</td>";
		echo "<td class=body align=right>" . number_format($row['Female']['activeusers']) . "</td><td class=body align=right>" . number_format(100*$row['Female']['activeusers']/($totalactive['Male']+$totalactive['Female']), 2) . "%</td>";
		echo "<td class=body align=right>" . number_format(($row['Male']['activeusers']+$row['Female']['activeusers'])) . "</td><td class=body align=right>" . number_format(100*($row['Male']['activeusers']+$row['Female']['activeusers'])/($totalactive['Male']+$totalactive['Female']), 2) . "%</td>";

		echo "<td class=body align=right>" . number_format($row['Male']['users']) . "</td><td class=body align=right>" . number_format(100*$row['Male']['users']/($total['Male']+$total['Female']), 2) . "%</td>";
		echo "<td class=body align=right>" . number_format($row['Female']['users']) . "</td><td class=body align=right>" . number_format(100*$row['Female']['users']/($total['Male']+$total['Female']), 2) . "%</td>";
		echo "<td class=body align=right>" . number_format(($row['Male']['users']+$row['Female']['users'])) . "</td><td class=body align=right>" . number_format(100*($row['Male']['users']+$row['Female']['users'])/($total['Male']+$total['Female']), 2) . "%</td>";

		echo "<td class=body align=right>" . number_format(100*$row['Male']['activeusers']/$row['Male']['users'], 2) . "%</td>";
		echo "<td class=body align=right>" . number_format(100*$row['Female']['activeusers']/$row['Female']['users'], 2) . "%</td>";
		echo "<td class=body align=right>" . number_format(100*($row['Male']['activeusers']+$row['Female']['activeusers'])/($row['Male']['users']+$row['Female']['users']), 2) . "%</td>";

		echo "</tr>";
	}

	echo "<tr>";
	echo "<td class=header></td>";

	echo "<td class=header align=right>" . number_format($totalactive['Male']) . "</td><td class=header align=right>" . number_format(100*$totalactive['Male']/($totalactive['Male']+$totalactive['Female']), 2) . "%</td>";
	echo "<td class=header align=right>" . number_format($totalactive['Female']) . "</td><td class=header align=right>" . number_format(100*$totalactive['Female']/($totalactive['Male']+$totalactive['Female']), 2) . "%</td>";
	echo "<td class=header align=right>" . number_format(($totalactive['Male']+$totalactive['Female'])) . "</td><td class=header align=right>100%</td>";

	echo "<td class=header align=right>" . number_format($total['Male']) . "</td><td class=header align=right>" . number_format(100*$total['Male']/($total['Male']+$total['Female']), 2) . "%</td>";
	echo "<td class=header align=right>" . number_format($total['Female']) . "</td><td class=header align=right>" . number_format(100*$total['Female']/($total['Male']+$total['Female']), 2) . "%</td>";
	echo "<td class=header align=right>" . number_format(($total['Male']+$total['Female'])) . "</td><td class=header align=right>100%</td>";

	echo "<td class=header align=right>" . number_format(100*$totalactive['Male']/$total['Male'], 2) . "%</td>";
	echo "<td class=header align=right>" . number_format(100*$totalactive['Female']/$total['Female'], 2) . "%</td>";
	echo "<td class=header align=right>" . number_format(100*($totalactive['Male']+$totalactive['Female'])/($total['Male']+$total['Female']), 2) . "%</td>";

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

	function recurTotalActive(& $locs, &$parents, $id){
		$total = $locs[$id]['activeusers'];

		if(isset($parents[$id]))
			foreach($parents[$id] as $child)
				$total += recurTotalActive($locs, $parents, $child);

		$locs[$id]['totalactive'] = $total;

		return $total;
	}

	function recurOutput(&$locs, &$parents, $id, $level, $total, $threshhold = 0){
		echo "<tr>";
		echo "<td class=body>" . str_repeat("- ", $level) . $locs[$id]['name'] . "</td>";

		echo "<td class=body align=right>" . number_format($locs[$id]['activeusers']) . "</td><td class=body align=right>" . number_format(100*$locs[$id]['activeusers']/$total, 2) . "%</td>";
		echo "<td class=body align=right>" . number_format($locs[$id]['totalactive']) . "</td><td class=body align=right>" . number_format(100*$locs[$id]['totalactive']/$total, 2) . "%</td>";

		echo "<td class=body align=right>" . number_format($locs[$id]['users']) . "</td><td class=body align=right>" . number_format(100*$locs[$id]['users']/$total, 2) . "%</td>";
		echo "<td class=body align=right>" . number_format($locs[$id]['total']) . "</td><td class=body align=right>" . number_format(100*$locs[$id]['total']/$total, 2) . "%</td>";

		echo "<td class=body align=right>" . number_format(100*$locs[$id]['activeusers']/$locs[$id]['users'], 2) . "%</td>";
		echo "<td class=body align=right>" . number_format(100*$locs[$id]['totalactive']/$locs[$id]['total'], 2) . "%</td>";

		echo "</tr>";

		if(isset($parents[$id]))
			foreach($parents[$id] as $child)
				if(!$threshhold || $locs[$child]['total'] >= $threshhold)
					recurOutput($locs, $parents, $child, $level+1, $total, $threshhold);
	}

//get total users by location
	$info = getlocinfo();
	$res = $usersdb->query("SELECT id, users FROM locstats");

	$locs = array();
	$parents = array();

	while($line = $res->fetchrow()){
		if(!isset($locs[$line['id']])){
			$line['name'] = $info[$line['id']]['name'];
			$line['parent'] = $info[$line['id']]['parent'];
			$locs[$line['id']] = $line;
			$locs[$line['id']]['activeusers'] = 0;
			$locs[$line['id']]['total'] = 0;
			$locs[$line['id']]['totalactive'] = 0;
			$parents[$line['parent']][] = $line['id'];
		}else{
			$locs[$line['id']]['users'] += $line['users'];
		}
	}

//get active users by location
	$res = $usersdb->prepare_query("SELECT loc, count(*) as count FROM users WHERE activetime > # GROUP BY loc ORDER BY count DESC", time() - 86400*7);

	while($line = $res->fetchrow())
		$locs[$line['loc']]['activeusers'] += $line['count'];


	$total = 0;
	foreach($parents[0] as $child)
		$total += recurTotal($locs, $parents, $child);

	$plustotal = 0;
	foreach($parents[0] as $child)
		$activetotal += recurTotalActive($locs, $parents, $child);

	echo "<tr><td class=header align=center colspan=2>Users Active in the past Week By Location</td></tr>";
	echo "<tr><td class=body colspan=2>";

	echo "<table>";
	echo "<tr>";
	echo "<td class=header rowspan=2>Location</td>";
	echo "<td class=header colspan=4 align=center>Active Users</td>";
	echo "<td class=header colspan=4 align=center>Total Users</td>";
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
	echo "<td class=body align=right>" . number_format($activetotal) . "</td><td class=body align=right>100.00%</td>";

	echo "<td class=body align=right>0</td><td class=body align=right>0.00%</td>";
	echo "<td class=body align=right>" . number_format($total) . "</td><td class=body align=right>100.00%</td>";

	echo "<td class=body align=right></td>";
	echo "<td class=body align=right>" . number_format(100*$activetotal/$total,2) . "%</td>";
	echo "</tr>";

	foreach($parents[0] as $child)
		recurOutput($locs, $parents, $child, 1, $total, 0);
	echo "</table>";

	echo "</td></tr>";
}

function userInterestsRecur(){
	global $masterdb, $configdb;

	$interests = new category( $configdb, "interests");
	$cats = $interests->makebranch();


	$res = $masterdb->query("SELECT * FROM masterintereststats");

	$interestcounts = array();
	while($line = $res->fetchrow())
		$interestcounts[$line['id']] = $line['users'];


	echo "<tr><td class=header align=center colspan=2>Users Interests</td></tr>";
	echo "<tr><td class=body colspan=2>";

	echo "<table>";
	echo "<tr>";
	echo "<td class=header>Interest</td>";
	echo "<td class=header>Users</td>";
	echo "</tr>";

	$classes = array('body','body2');
	$i = 0;

	foreach($cats as $cat){
		$class  = ($cat['depth'] == 1 ? 'header' : $classes[$i = !$i]);
		$indent = ($cat['depth'] == 1 ? '' : '&nbsp; &nbsp; &nbsp;');
		echo "<tr><td class=$class>$indent$cat[name]</td><td class=$class align=right>" . number_format($interestcounts[$cat['id']]) . "</td></tr>";
	}
	echo "</table>";
	echo "</td></tr>";
}

function plusBuyHistory(){
	global $db;

	$stats = array();

	$month = array(
		'numplususers' => 0, //total number that had plus at the beginning of the month
		'outstanding' => 0,  //average amount of outstanding plus

		'new' => 0,          //accounts that haven't bought plus before ever
		'repeatearly' => 0,  //accounts that haven't run out
		'repeatweek' => 0,   //accounts that ran out less than a week ago
		'repeatmonth' => 0,  //accounts that ran out less than a month ago
		'repeatyear' => 0,   //accounts that ran out less than a year ago
		'repeatold' => 0,    //accounts that ran out over a year ago
		
		'expire' => 0,

		'buyweek' => 0,
		'buyone' => 0,
		'buytwo' => 0,
		'buythree' => 0,
		'buysix' => 0,
		'buyyear' => 0,
		'odd' => 0,
		);

	function analyze(&$users, &$stats, $curmonth, $time, $curdate){
		$plususers = 0;
		$outstanding = 0;
		$expired = 0;

		static $lastmonth = 0;

		foreach($users as $expiry){
			if($expiry > $time){
				$plususers++;
				$outstanding += ($expiry - $time)/86400;
			}elseif($expiry > $lastmonth){
				$expired++;
			}
		}

		$curmonth['numplususers'] = $plususers;
		$curmonth['outstanding'] = $outstanding;
		$curmonth['expire'] = $expired;

		$stats[$curdate] = $curmonth;

		$lastmonth = $time;
	}


	$users = array();

	$res = $db->unbuffered_query("SELECT userid, time, duration FROM pluslog ORDER BY time");

	$curdate = "";
	$curtime = 0;
	$curmonth = array();

	while($line = $res->fetchrow()){

	//analyze previous month
		if($curdate != gmdate("F Y", $line['time'])){
			if($curtime)
				analyze($users, $stats, $curmonth, $curtime, $curdate);

		//prep for next month
			$curmonth = $month;
			$curdate = gmdate("F Y", $line['time']);
		}

		$odd = false;

		switch($line['duration']){
			case 86400*7:     $curmonth['buyweek']++;  break; //one week
			case 86400*31:    $curmonth['buyone']++;   break; //one month
			case 86400*31*2:  $curmonth['buytwo']++;   break; //two months
			case 86400*31*3:  $curmonth['buythree']++; break; //three months
			case 86400*31*6:  $curmonth['buysix']++;   break; //six months
			case 86400*31*12: $curmonth['buyyear']++;  break; //year
			default:          $curmonth['odd']++; $odd = true;  break; //some odd amount, probably transfer
		}

	//users habits
		if(!isset($users[$line['userid']])){
			$users[$line['userid']] = 0;
			$curmonth['new']++;
		}elseif(!$odd){
			if($users[$line['userid']] < $line['time']){ //expired

				if($users[$line['userid']]+86400*7 > $line['time'])
					$curmonth['repeatweek']++; //bought within a week of expiry
				elseif($users[$line['userid']]+86400*31 > $line['time'])
					$curmonth['repeatmonth']++; //bought within a month of expiry
				elseif($users[$line['userid']]+86400*365 > $line['time'])
					$curmonth['repeatyear']++; //bought within a month of expiry
				else
					$curmonth['repeatold']++;    //bought after a long time

			}else{
			    $curmonth['repeatearly']++;  //bought before expiry
			}
		}

		$users[$line['userid']] = max($users[$line['userid']], $line['time']) + $line['duration'];
		$curtime = $line['time'];
	}
	analyze($users, $stats, $curmonth, $curtime, $curdate);


	echo "<tr><td class=header align=center colspan=2>Plus History</td></tr>";
	echo "<tr><td class=body colspan=2>";

	echo "<table width=100% align=center>";

	echo "<tr>";
	echo "<td class=header rowspan=2 align=center nowrap>Month</td>";
	echo "<td class=header colspan=3 align=center nowrap>Num Plus Users</td>";
	echo "<td class=header colspan=2 align=center nowrap>Outstanding Plus (months)</td>";
	echo "<td class=header colspan=7 align=center nowrap>Habits - New/Repeat/Expire</td>";
	echo "<td class=header colspan=7 align=center nowrap>Buys</td>";
	echo "<td class=header colspan=3 align=center nowrap>Total Buys</td>";
	echo "</tr>";


	echo "<tr>";

	echo "<td class=header align=center nowrap>Total</td>";
	echo "<td class=header align=center nowrap colspan=2>Growth</td>";

	echo "<td class=header align=center nowrap>Total</td>";
	echo "<td class=header align=center nowrap>Average</td>";

	echo "<td class=header align=center nowrap>New</td>";
	echo "<td class=header align=center nowrap>Early</td>";
	echo "<td class=header align=center nowrap>Week</td>";
	echo "<td class=header align=center nowrap>Month</td>";
	echo "<td class=header align=center nowrap>Year</td>";
	echo "<td class=header align=center nowrap>Old</td>";
	echo "<td class=header align=center nowrap>Expire</td>";

	echo "<td class=header align=center nowrap>Week</td>";
	echo "<td class=header align=center nowrap>Month</td>";
	echo "<td class=header align=center nowrap>2 Months</td>";
	echo "<td class=header align=center nowrap>3 Months</td>";
	echo "<td class=header align=center nowrap>6 Months</td>";
	echo "<td class=header align=center nowrap>Year</td>";
	echo "<td class=header align=center nowrap>Odd</td>";

	echo "<td class=header align=center nowrap>Buys</td>";
	echo "<td class=header align=center nowrap>Months</td>";
	echo "<td class=header align=center nowrap>Average</td>";

	echo "</tr>";

	$classes = array('body','body2');
	$i = 0;

	$num = 0;

	foreach($stats as $date => $month){
		echo "<tr>";
		echo "<td class=header align=right nowrap>$date</td>";

		echo "<td class=" . $classes[$i] . " align=right nowrap>" . number_format($month['numplususers']) . "</td>";
		echo "<td class=" . $classes[$i] . " align=right nowrap>" . number_format($month['numplususers'] - $num) . "</td>"; 
		echo "<td class=" . $classes[$i] . " align=right nowrap>" . ($num ? number_format(100*($month['numplususers'] - $num)/$num, 2) . " %" : '') . "</td>";
		echo "<td class=" . $classes[$i] . " align=right nowrap>" . number_format($month['outstanding']/31) . "</td>";
		echo "<td class=" . $classes[$i] . " align=right nowrap>" . number_format(($month['outstanding']/31)/$month['numplususers'], 2) . "</td>";

		echo "<td class=" . $classes[$i] . " align=right nowrap>" . number_format($month['new']) . "</td>";
		echo "<td class=" . $classes[$i] . " align=right nowrap>" . number_format($month['repeatearly']) . "</td>";
		echo "<td class=" . $classes[$i] . " align=right nowrap>" . number_format($month['repeatweek']) . "</td>";
		echo "<td class=" . $classes[$i] . " align=right nowrap>" . number_format($month['repeatmonth']) . "</td>";
		echo "<td class=" . $classes[$i] . " align=right nowrap>" . number_format($month['repeatyear']) . "</td>";
		echo "<td class=" . $classes[$i] . " align=right nowrap>" . number_format($month['repeatold']) . "</td>";
		echo "<td class=" . $classes[$i] . " align=right nowrap>" . number_format($month['expire']) . "</td>";

		echo "<td class=" . $classes[$i] . " align=right nowrap>" . number_format($month['buyweek']) . "</td>";
		echo "<td class=" . $classes[$i] . " align=right nowrap>" . number_format($month['buyone']) . "</td>";
		echo "<td class=" . $classes[$i] . " align=right nowrap>" . number_format($month['buytwo']) . "</td>";
		echo "<td class=" . $classes[$i] . " align=right nowrap>" . number_format($month['buythree']) . "</td>";
		echo "<td class=" . $classes[$i] . " align=right nowrap>" . number_format($month['buysix']) . "</td>";
		echo "<td class=" . $classes[$i] . " align=right nowrap>" . number_format($month['buyyear']) . "</td>";
		echo "<td class=" . $classes[$i] . " align=right nowrap>" . number_format($month['odd']) . "</td>";

		echo "<td class=" . $classes[$i] . " align=right nowrap>" . number_format($month['buyone'] + $month['buytwo'] + $month['buythree'] + $month['buysix'] + $month['buyyear']) . "</td>";
		echo "<td class=" . $classes[$i] . " align=right nowrap>" . number_format($month['buyone'] + $month['buytwo']*2 + $month['buythree']*3 + $month['buysix']*6 + $month['buyyear']*12) . "</td>";
		echo "<td class=" . $classes[$i] . " align=right nowrap>" . number_format(($month['buyone'] + $month['buytwo']*2 + $month['buythree']*3 + $month['buysix']*6 + $month['buyyear']*12)/($month['buyone'] + $month['buytwo'] + $month['buythree'] + $month['buysix'] + $month['buyyear']), 2) . "</td>";


		echo "</tr>";

		$num = $month['numplususers'];

		$i = !$i;
	}

	echo "<tr>";
	echo "<td class=header rowspan=2 align=center nowrap>Month</td>";
	echo "<td class=header colspan=3 align=center nowrap>Num Plus Users</td>";
	echo "<td class=header colspan=2 align=center nowrap>Outstanding Plus (months)</td>";
	echo "<td class=header colspan=7 align=center nowrap>Habits - New/Repeat/Expire</td>";
	echo "<td class=header colspan=7 align=center nowrap>Buys</td>";
	echo "<td class=header colspan=3 align=center nowrap>Total Buys</td>";
	echo "</tr>";


	echo "<tr>";

	echo "<td class=header align=center nowrap>Total</td>";
	echo "<td class=header align=center nowrap colspan=2>Growth</td>";

	echo "<td class=header align=center nowrap>Total</td>";
	echo "<td class=header align=center nowrap>Average</td>";

	echo "<td class=header align=center nowrap>New</td>";
	echo "<td class=header align=center nowrap>Early</td>";
	echo "<td class=header align=center nowrap>Week</td>";
	echo "<td class=header align=center nowrap>Month</td>";
	echo "<td class=header align=center nowrap>Year</td>";
	echo "<td class=header align=center nowrap>Old</td>";
	echo "<td class=header align=center nowrap>Expire</td>";

	echo "<td class=header align=center nowrap>Week</td>";
	echo "<td class=header align=center nowrap>Month</td>";
	echo "<td class=header align=center nowrap>2 Months</td>";
	echo "<td class=header align=center nowrap>3 Months</td>";
	echo "<td class=header align=center nowrap>6 Months</td>";
	echo "<td class=header align=center nowrap>Year</td>";
	echo "<td class=header align=center nowrap>Odd</td>";

	echo "<td class=header align=center nowrap>Buys</td>";
	echo "<td class=header align=center nowrap>Months</td>";
	echo "<td class=header align=center nowrap>Average</td>";

	echo "</tr>";

	echo "</table>";
	echo "</td></tr>";
}


function plusHabits(){
	global $db;

	function analyzegroup(&$stats, $users, $curtime){
		global $usersdb;
	
		$res = $usersdb->prepare_query("SELECT userid, activetime FROM useractivetime WHERE userid IN (%)", array_keys($users));
		
		$activetimes = array();
		while($line = $res->fetchrow())
			$activetimes[$line['userid']] = $line['activetime'];
	
		foreach($users as $user)
			analyze($stats, $curtime, $user, (isset($activetimes[$user[0]['userid']]) ? $activetimes[$user[0]['userid']] : 0));
	}
		
	function analyze(&$stats, $curtime, $log, $lastactive){
		$stats['userscounted']++;
	
		$weird = false;
	
		$expiry = 0;
		$lapsed = false;
		$lapsedweek = false;
		$lapsedmonth = false;
	
		foreach($log as $line){
			if($expiry && $expiry < $line['time'])
				$lapsed = true;
			if($expiry && $expiry+86400*7 < $line['time'])
				$lapsedweek = true;
			if($expiry && $expiry+86400*31 < $line['time'])
				$lapsedmonth = true;
			if($expiry < $line['time'])
				$expiry = $line['time'];
			$expiry += $line['duration'];
	
			switch($line['duration']){
				case 86400*31:    //one month
				case 86400*31*2:  //two months
				case 86400*31*3:  //three months
				case 86400*31*6:  //six months
				case 86400*31*12: //year
					break;
				default: //weird
					$weird = true; 
					break;
			}
		}
	
	//only process logs that are simple
		if($weird){
			$stats['oddcases']++;
			return;
		}
	
	//count number of buys
		foreach($log as $line){
			switch($line['duration']){
				case 86400*31:    $stats['totalone']++;   break; //one month
				case 86400*31*2:  $stats['totaltwo']++;   break; //two months
				case 86400*31*3:  $stats['totalthree']++; break; //three months
				case 86400*31*6:  $stats['totalsix']++;   break; //six months
				case 86400*31*12: $stats['totalyear']++;  break; //year
			}
		}
	
	
	//check first
		$first = reset($log);
		switch($first['duration']){
			case 86400*31:    $stats['startone']++;   break; //one month
			case 86400*31*2:  $stats['starttwo']++;   break; //two months
			case 86400*31*3:  $stats['startthree']++; break; //three months
			case 86400*31*6:  $stats['startsix']++;   break; //six months
			case 86400*31*12: $stats['startyear']++;  break; //year
		}
	
	//did a trial period?
		if($first['duration'] == 86400*31 && count($log) > 1){
			$more = false;
			foreach($log as $line)
				if($line['duration'] > 86400*31)
					$more = true;
	
			if($more)
				$stats['trymore']++;
			else
				$stats['manyonce']++;
		}
	
	//check current plus
		$last = end($log);
		if($expiry > $curtime){ //hasn't expired
			switch($last['duration']){
				case 86400*31:    $stats['curone']++;   break; //one month
				case 86400*31*2:  $stats['curtwo']++;   break; //two months
				case 86400*31*3:  $stats['curthree']++; break; //three months
				case 86400*31*6:  $stats['cursix']++;   break; //six months
				case 86400*31*12: $stats['curyear']++;  break; //year
			}
			if(!$lastactive || $lastactive < $curtime - 86400*7)
				$stats['curhaveinactive']++;
		}else{
			$stats['curnone']++;
			if(!$lastactive || $lastactive < $curtime - 86400*7)
				$stats['curnoneinactive']++;
		}
	
	//bought multiple?
		if(count($log) == 1){ //bought once
			if($expiry > $curtime) //hasn't expired yet
				$stats['buyonce']++;
			else // let it expire
				$stats['tryonce']++;
		}else{ //bought multiple times
			$stats['repeat']++;
			if($lapsed)
				$stats['repeatexp']++;
			if($lapsedweek)
				$stats['repeatexpweek']++;
			if($lapsedmonth)
				$stats['repeatexpmonth']++;
		}
	}


	$stats = array( 
		'userscounted' => 0, //total number of people to analyze
		'oddcases' => 0, //people who got random amounts given in some way, transfered, etc. These aren't analyzed further

		'curone' => 0,    //people who bought 1 month last time
		'curtwo' => 0,    //people who bought 2 months last time
		'curthree' => 0,  //people who bought 3 months last time
		'cursix' => 0,    //people who bought 6 months last time
		'curyear' => 0,   //people who bought a year last time
		'curnone' => 0,   //people who have run out

		'curhaveinactive' => 0, //people who have it, but are inactive
		'curnoneinactive' => 0, //people who had it, but don't anymore and are inactive

		'startone' => 0,  //people who bought 1 month first time
		'starttwo' => 0,  //people who bought 2 months first time
		'startthree' => 0,//people who bought 3 months first time
		'startsix' => 0,  //people who bought 6 months first time
		'startyear' => 0, //people who bought a year first time

		'totalone' => 0,  //number of single months bought
		'totaltwo' => 0,  //number of two months bought
		'totalthree' => 0,//number of three months bought
		'totalsix' => 0,  //number of six months bought
		'totalyear' => 0, //number of years bought

		'buyonce' => 0,   //people who have bought once and still have it
		'tryonce' => 0,   //people who bought, let it expire, and still don't have it
		'repeat' => 0,    //people who bought more than once
		'repeatexp' => 0, //people who bought, let it expire, and bought again.
		'repeatexpweek' => 0, //people who bought, let it expire for more than a week, and bought again.
		'repeatexpmonth' => 0, //people who bought, let it expire for more than a month, and bought again.

		'manyonce' => 0,  //buy one month at a time, more than once
		'trymore' => 0,   //buy one month, maybe more than once, then buy more than a month at once
		);

	$curtime = time();

	$res = $db->prepare_query("SELECT userid, time, duration FROM pluslog ORDER BY userid, time");

	$users = array();
	$user = array();
	$curuserid = 0;
	
	while($line = $res->fetchrow()){
		if($curuserid && $curuserid != $line['userid']){
			$users[$curuserid] = $user;
		
			if(count($users) == 1000){
				analyzegroup($stats, $users, $curtime);
				$users = array();
			}

			$user = array();
		}
		
		$curuserid = $line['userid'];
		$user[] = $line;
	}
	$users[$curuserid] = $user;
	analyzegroup($stats, $users, $curtime);


	
	echo "<tr><td class=header align=center colspan=2>Plus Habits</td></tr>";

	$classes = array('body', 'body2');
	$i = 0;

	echo "<tr><td class=" . $classes[$i = !$i] . " align=right>" . number_format($stats['userscounted']) . "</td><td class=$classes[$i]>Total number of accounts to analyze</td></tr>";
	echo "<tr><td class=" . $classes[$i = !$i] . " align=right>" . number_format($stats['oddcases']) . "</td><td class=$classes[$i]>People who got random amounts given in some way, transfered, etc. These aren't analyzed further</td></tr>";
	echo "<tr><td class=" . $classes[$i = !$i] . " align=right>" . number_format($stats['userscounted'] - $stats['oddcases']) . "</td><td class=$classes[$i]>number of accounts analyzed</td></tr>";
	echo "<tr><td class=" . $classes[$i = !$i] . " align=right>" . number_format($stats['curone']) . "</td><td class=$classes[$i]>people who bought 1 month last time and still have it</td></tr>";
	echo "<tr><td class=" . $classes[$i = !$i] . " align=right>" . number_format($stats['curtwo']) . "</td><td class=$classes[$i]>people who bought 2 months last time and still have it</td></tr>";
	echo "<tr><td class=" . $classes[$i = !$i] . " align=right>" . number_format($stats['curthree']) . "</td><td class=$classes[$i]>people who bought 3 months last time and still have it</td></tr>";
	echo "<tr><td class=" . $classes[$i = !$i] . " align=right>" . number_format($stats['cursix']) . "</td><td class=$classes[$i]>people who bought 6 months last time and still have it</td></tr>";
	echo "<tr><td class=" . $classes[$i = !$i] . " align=right>" . number_format($stats['curyear']) . "</td><td class=$classes[$i]>people who bought 1 year last time and still have it</td></tr>";
	echo "<tr><td class=" . $classes[$i = !$i] . " align=right>" . number_format($stats['curhaveinactive']) . "</td><td class=$classes[$i]>people who still have plus but haven't been on in over a week (or deleted before using up all plus)</td></tr>";
	echo "<tr><td class=" . $classes[$i = !$i] . " align=right>" . number_format($stats['curnone']) . "</td><td class=$classes[$i]>people who bought but no longer have it</td></tr>";
	echo "<tr><td class=" . $classes[$i = !$i] . " align=right>" . number_format($stats['curnoneinactive']) . "</td><td class=$classes[$i]>people who bought but no longer have it, and haven't been on in over a week (or are deleted). Some of these may have bought with a new account</td></tr>";
	echo "<tr><td class=" . $classes[$i = !$i] . " align=right>" . number_format($stats['curnone'] - $stats['curnoneinactive']) . "</td><td class=$classes[$i]>people who bought but no longer have it and are still active</td></tr>";
	echo "<tr><td class=" . $classes[$i = !$i] . " align=right>" . number_format($stats['startone']) . "</td><td class=$classes[$i]>people who bought 1 month first time</td></tr>";
	echo "<tr><td class=" . $classes[$i = !$i] . " align=right>" . number_format($stats['starttwo']) . "</td><td class=$classes[$i]>people who bought 2 months first time</td></tr>";
	echo "<tr><td class=" . $classes[$i = !$i] . " align=right>" . number_format($stats['startthree']) . "</td><td class=$classes[$i]>people who bought 3 months first time</td></tr>";
	echo "<tr><td class=" . $classes[$i = !$i] . " align=right>" . number_format($stats['startsix']) . "</td><td class=$classes[$i]>people who bought 6 months first time</td></tr>";
	echo "<tr><td class=" . $classes[$i = !$i] . " align=right>" . number_format($stats['startyear']) . "</td><td class=$classes[$i]>people who bought 1 year first time</td></tr>";
	echo "<tr><td class=" . $classes[$i = !$i] . " align=right>" . number_format($stats['totalone']) . "</td><td class=$classes[$i]>total number of single months bought</td></tr>";
	echo "<tr><td class=" . $classes[$i = !$i] . " align=right>" . number_format($stats['totaltwo']) . "</td><td class=$classes[$i]>total number of two months bought</td></tr>";
	echo "<tr><td class=" . $classes[$i = !$i] . " align=right>" . number_format($stats['totalthree']) . "</td><td class=$classes[$i]>total number of three months bought</td></tr>";
	echo "<tr><td class=" . $classes[$i = !$i] . " align=right>" . number_format($stats['totalsix']) . "</td><td class=$classes[$i]>total number of six months bought</td></tr>";
	echo "<tr><td class=" . $classes[$i = !$i] . " align=right>" . number_format($stats['totalyear']) . "</td><td class=$classes[$i]>total number of years bought</td></tr>";
	echo "<tr><td class=" . $classes[$i = !$i] . " align=right>" . number_format($stats['buyonce']) . "</td><td class=$classes[$i]>people who bought only once and still have it</td></tr>";
	echo "<tr><td class=" . $classes[$i = !$i] . " align=right>" . number_format($stats['tryonce']) . "</td><td class=$classes[$i]>people who bought only once, let it expire, and still don't have it</td></tr>";
	echo "<tr><td class=" . $classes[$i = !$i] . " align=right>" . number_format($stats['repeat']) . "</td><td class=$classes[$i]>people who bought more than once</td></tr>";
	echo "<tr><td class=" . $classes[$i = !$i] . " align=right>" . number_format($stats['repeatexp']) . "</td><td class=$classes[$i]>people who bought, let it expire, and bought again.</td></tr>";
	echo "<tr><td class=" . $classes[$i = !$i] . " align=right>" . number_format($stats['repeatexpweek']) . "</td><td class=$classes[$i]>people who bought, let it expire for more than a week, and bought again.</td></tr>";
	echo "<tr><td class=" . $classes[$i = !$i] . " align=right>" . number_format($stats['repeatexpmonth']) . "</td><td class=$classes[$i]>people who bought, let it expire for more than a month, and bought again.</td></tr>";
	echo "<tr><td class=" . $classes[$i = !$i] . " align=right>" . number_format($stats['manyonce']) . "</td><td class=$classes[$i]>bought more than one month, but only one month at a time</td></tr>";
	echo "<tr><td class=" . $classes[$i = !$i] . " align=right>" . number_format($stats['trymore']) . "</td><td class=$classes[$i]>buy one month, maybe more than once, then buy more than a month (ie tried a month, then bought more)</td></tr>";

}
