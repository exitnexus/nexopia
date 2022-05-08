<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isAdmin($userData['userid'],"listmods"))
		die("Permission denied");

	$sortt = getREQval('sortt');
	$sortd = getREQval('sortd');

	isValidSortd($sortd,'ASC');

	if(!($type = getREQval('type','int')) || !isset($mods->modtypes[$type]))
		$type = MOD_PICS;

	$month = getREQval('month','int', gmdate("n"));
	$day = getREQval('day','int', gmdate("j") - 14);
	$year = getREQval('year','int', gmdate("Y"));


	$startdate = gmmktime(0, 0, 0, $month, $day, $year);

	$month = gmdate("n", $startdate);
	$day = gmdate("j", $startdate);
	$year = gmdate("Y", $startdate);

	$res = $mods->db->prepare_query("SELECT userid, `right`, `wrong`, strict, lenient, level, time, creationtime FROM mods WHERE type = #", $type);

	$rows = array();
	while($line = $res->fetchrow())
		$rows[$line['userid']][0] = $line;

	$res = $mods->db->prepare_query("SELECT userid, `right`, `wrong`, strict, lenient, level FROM modhist WHERE type = ? && dumptime BETWEEN # AND #", $type, $startdate, $startdate + 86399);

	while($line = $res->fetchrow())
		$rows[$line['userid']][1] = $line;


	$emptyrow = array(	'right' => 0,
						'wrong' => 0,
						'strict' => 0,
						'lenient' => 0,
						'level' => 0);

	$usernames = getUserName(array_keys($rows));

	$users = array();

	foreach($rows as $userid => $line){

		if(!isset($line[0])) //skip users that were mods, but aren't anymore
			continue;

		if(!isset($line[1]))
			$line[1] = $emptyrow;

		$user = array();

		$user['userid'] = $userid;
		$user['username'] = $usernames[$userid];
		$user['time'] = $line[0]['time'];
		$user['creationtime'] = $line[0]['creationtime'];

		$user['right'] = $line[0]['right'] - $line[1]['right'];
		$user['wrong'] = $line[0]['wrong'] - $line[1]['wrong'];
		$user['strict'] = $line[0]['strict'] - $line[1]['strict'];
		$user['lenient'] = $line[0]['lenient'] - $line[1]['lenient'];
		$user['total'] = $user['right'] + $user['wrong'];
		$user['percent'] = ($user['total'] ? $user['wrong']*100/$user['total'] : 0);

		$user['rightt'] = $line[0]['right'];
		$user['wrongt'] = $line[0]['wrong'];
		$user['strictt'] = $line[0]['strict'];
		$user['lenientt'] = $line[0]['lenient'];
		$user['totalt'] = $user['rightt'] + $user['wrongt'];
		$user['percentt'] = ($user['totalt'] ? $user['wrongt']*100/$user['totalt'] : 0);

		$user['level'] = $line[0]['level'];

		$users[$userid] = $user;
	}

	switch($sortt){
		case 'userid':
		case 'total':	case 'totalt':
		case 'right':	case 'rightt':
		case 'wrong':	case 'wrongt':
		case 'strict':	case 'strictt':
		case 'lenient':	case 'lenientt':
		case 'percent':	case 'percentt':
		case 'level':
		case 'time':
		case 'creationtime':
			$sortcomp = SORT_NUMERIC;
			break;

		case 'username':
		default:
			$sortt = 'username';
			$sortcomp = SORT_CASESTR;
	}

	sortCols($users, SORT_ASC, SORT_CASESTR, 'username', ($sortd == 'ASC' ? SORT_ASC : SORT_DESC), $sortcomp, $sortt);

	$mods->adminlog("list mod hist", "List $type mod history");


	for($i=1;$i<=12;$i++)
		$months[$i] = date("F", mktime(0,0,0,$i,1,0));

	incHeader();

	echo "<table align=center>";

	echo "<form action=$_SERVER[PHP_SELF]>";
	echo "<tr><td class=header colspan=16 align=center>";
		echo "<select class=body name='month' style=\"width:90px\"><option value=0>Month" . make_select_list_key($months,$month) . "</select>";
		echo "<select class=body name=day style=\"width:50px\"><option value=0>Day" . make_select_list(range(1,31),$day) . "</select>";
		echo "<select class=body name=year style=\"width:60px\"><option value=0>Year" . make_select_list(range(2005,date("Y")),$year) . "</select>";
		echo "<select class=body name=type>" . make_select_list_key($mods->modtypes,$type) . "</select>";
	echo "<input class=body type=submit value=Go></td></tr>";
	echo "</form>";


	$params = array('month' => $month, 'day' => $day, 'year' => $year, 'type' => $type);
	echo "<tr>";
		echo makeSortTableHeader("Username","username", $params);
		echo makeSortTableHeader("Level","level", $params);
		echo makeSortTableHeader("Total","total", $params);
		echo makeSortTableHeader("Total","totalt", $params);
		echo makeSortTableHeader("Right","right", $params);
		echo makeSortTableHeader("Right","rightt", $params);
		echo makeSortTableHeader("Wrong","wrong", $params);
		echo makeSortTableHeader("Wrong","wrongt", $params);
		echo makeSortTableHeader("Strict","strict", $params);
		echo makeSortTableHeader("Strict","strictt", $params);
		echo makeSortTableHeader("Lenient","lenient", $params);
		echo makeSortTableHeader("Lenient","lenientt", $params);
		echo makeSortTableHeader("Percent","percent", $params);
		echo makeSortTableHeader("Percent","percentt", $params);
		echo makeSortTableHeader("Last Moded","time", $params);
		echo makeSortTableHeader("Creation Time","creationtime", $params);
	echo "</tr>";

	$nummods = 0;
	$totals = array(	'total' => 0,
						'totalt' => 0,
						'right' => 0,
						'rightt' => 0,
						'wrong' => 0,
						'wrongt' => 0,
						'strict' => 0,
						'strictt' => 0,
						'lenient' => 0,
						'lenientt' => 0,
						'level' => 0);

	$isAdmin = $mods->isAdmin($userData['userid'],"editmods");

	$classes = array('body','body2');
	$i = 1;

	foreach($users as $line){
		$class = $classes[$i = !$i];
	
		echo "<tr>";
		echo "<td class=$class><a class=body href=/profile.php?uid=$line[userid]>$line[username]</a></td>";

		echo "<td class=$class>";
		if($isAdmin){
			$newlevel = $mods->suggestedModLevel($line['right'], $line['percent'], $line['level']);
			if($newlevel > $line['level'])
				echo "<b><a class=body href=/adminmods.php?type=$type&uid=$line[userid]&level=$newlevel&action=Update>$line[level] +++</a></b>";
			elseif($newlevel < $line['level'])
				echo "<b><a class=body href=/adminmods.php?type=$type&uid=$line[userid]&level=$newlevel&action=Update>$line[level] ---</a></b>";
			else
				echo "$line[level]";
		}else{
			echo "$line[level]";
		}
		echo "</td>";

		echo "<td class=$class align=right colspan=2>" . number_format($line['total']) . " / " .  number_format($line['totalt']) . "</td>";
		echo "<td class=$class align=right colspan=2>" . number_format($line['right']) . " / " .  number_format($line['rightt']) . "</td>";
		echo "<td class=$class align=right colspan=2>" . number_format($line['wrong']) . " / " .  number_format($line['wrongt']) . "</td>";
		echo "<td class=$class align=right colspan=2>" . number_format($line['strict']) . " / " .  number_format($line['strictt']) . "</td>";
		echo "<td class=$class align=right colspan=2>" . number_format($line['lenient']) . " / " .  number_format($line['lenientt']) . "</td>";
		echo "<td class=$class align=right colspan=2>" . number_format($line['percent'],2) . " / " . number_format($line['percentt'],2) . " %</td>";
		echo "<td class=$class>" . ($line['time'] == 0 ? "Never" : userDate("M j, Y G:i", $line['time']) ) . "</td>";
		echo "<td class=$class>" . ($line['creationtime'] == 0 ? "Unknown" : userDate("M j, Y G:i", $line['creationtime']) ) . "</td>";
		echo "</tr>";
		$nummods++;

		foreach($totals as $col => $val)
			$totals[$col] += $line[$col];
	}

	echo "<tr>";
	echo "<td class=header>$nummods Mods</td>";
	echo "<td class=header>" . ($nummods > 0 ? number_format($totals['level']/$nummods,2) : "0.00") . "</td>";
	echo "<td class=header align=right colspan=2>" . number_format($totals['total']) . " / " . number_format($totals['totalt']) . "</td>";
	echo "<td class=header align=right colspan=2>" . number_format($totals['right']) . " / " . number_format($totals['rightt']) . "</td>";
	echo "<td class=header align=right colspan=2>" . number_format($totals['wrong']) . " / " . number_format($totals['wrongt']) . "</td>";
	echo "<td class=header align=right colspan=2>" . number_format($totals['strict']) . " / " . number_format($totals['strictt']) . "</td>";
	echo "<td class=header align=right colspan=2>" . number_format($totals['lenient']) . " / " . number_format($totals['lenientt']) . "</td>";
	echo "<td class=header align=right colspan=2></td>";
	echo "<td class=header align=right colspan=2></td>";
	echo "</tr>";

	echo "</table>";

	incFooter();


