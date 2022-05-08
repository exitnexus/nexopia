<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"stats"))
		die("Permission denied");


	$start = getPOSTval('start','int');
	$end = getPOSTval('end','int');

	if(!$start && !$end){
		$end = $usersdb->nextAuto("users");
		$start = $end - 30000;
	}

	if($end - $start > 50000)
		$end = $start + 50000;

	$res = $configdb->query("SELECT id, name, parent, 0 as users FROM locs ORDER BY name");

	$locs = array();
	$parents = array();

	while($line = $res->fetchrow()){
		$locs[$line['id']] = $line;
		$locs[$line['id']]['total'] = 0;
		$parents[$line['parent']][] = $line['id'];
	}

	$res = $usersdb->prepare_query("SELECT loc, count(*) as count FROM users WHERE userid >= # && userid <= # GROUP BY loc ORDER BY count DESC", $start, $end);

	while($line = $res->fetchrow())
		$locs[$line['loc']]['users'] = $line['count'];



	$total = 0;
	foreach($parents[0] as $child)
		$total += recurTotal($locs, $parents, $child);

	incHeader();

	echo "<table align=center>";
	echo "<form action=$_SERVER[PHP_SELF] method=post>";
	echo "<tr><td class=header align=center colspan=2>Locations of users between</td></tr>";
	echo "<tr><td class=body>Start userid:</td><td class=body><input class=body type=text name=start value=$start size=10></td></tr>";
	echo "<tr><td class=body>End userid:</td><td class=body><input class=body type=text name=end value=$end size=10></td></tr>";
	echo "<tr><td class=body colspan=2>If the split is bigger than 50,000, it will be shortened.</td></tr>";
	echo "<tr><td class=body colspan=2 align=center><input class=body type=submit name=action value=Go></td></tr>";
	echo "</form>";
	echo "</table>";

	echo "<table align=center>";
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

	incFooter();


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
