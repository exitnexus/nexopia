<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isAdmin($userData['userid'],"listmods"))
		die("Permission denied");

	$sortlist = array( 	'userid' => "",
						'username' => "username",
						'total' => "`right`+`wrong`",
						'right' => "right",
						'wrong' => "wrong",
						'strict' => "strict",
						'lenient' => "lenient",
						'level' => "level",
						'time' => "time",
						'creationtime' => "creationtime",
						'percent' => "IF(`right`+`wrong`=0,0,100.0*`wrong`/(`right` + `wrong`))",
						'new' => "'1'"
						);

	isValidSortt($sortlist,$sortt);
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

	$mods->db->prepare_query("SELECT " . makeSortSelect($sortlist) . " FROM mods WHERE type = #", $type);

	$rows = array();
	while($line = $mods->db->fetchrow())
		$rows[$line['userid']] = $line;

	$mods->db->prepare_query("SELECT userid, `right`+`wrong` as total, `right`, `wrong`, strict, lenient, level, IF(`right`+`wrong`=0,0,100.0*`wrong`/(`right` + `wrong`)) as percent FROM modhist WHERE type = # && dumptime BETWEEN # AND #", $type, $startdate, $startdate + 86399);

	while($line = $mods->db->fetchrow()){
		if(!isset($rows[$line['userid']]))
			continue;

		$rows[$line['userid']]['total'] -= $line['total'];
		$rows[$line['userid']]['right'] -= $line['right'];
		$rows[$line['userid']]['wrong'] -= $line['wrong'];
		$rows[$line['userid']]['strict'] -= $line['strict'];
		$rows[$line['userid']]['lenient'] -= $line['lenient'];
		$rows[$line['userid']]['level'] -= $line['level'];
		$rows[$line['userid']]['percent'] -= $line['percent'];
		$rows[$line['userid']]['new'] = 0;
	}

	switch($sortt){
		case 'userid':
		case 'total':
		case 'right':
		case 'wrong':
		case 'strict':
		case 'lenient':
		case 'level':
		case 'time':
		case 'creationtime':
		case 'percent':
			$sortcomp = SORT_NUMERIC;
			break;

		case "username":
		default:
			$sortcomp = SORT_CASESTR;
	}

	sortCols($rows, SORT_ASC, SORT_CASESTR, 'username', ($sortd == 'ASC' ? SORT_ASC : SORT_DESC), $sortcomp, $sortt);

	$mods->adminlog("list mod hist", "List $type mod history");


	for($i=1;$i<=12;$i++)
		$months[$i] = date("F", mktime(0,0,0,$i,1,0));

	incHeader();

	echo "<table align=center>";

	echo "<form action=$_SERVER[PHP_SELF]>";
	echo "<tr><td class=header colspan=12 align=center>";
		echo "<select class=body name='month' style=\"width:90px\"><option value=0>Month" . make_select_list_key($months,$month) . "</select>";
		echo "<select class=body name=day style=\"width:50px\"><option value=0>Day" . make_select_list(range(1,31),$day) . "</select>";
		echo "<select class=body name=year style=\"width:60px\"><option value=0>Year" . make_select_list(range(2005,date("Y")),$year) . "</select>";
		echo "<select class=body name=type>" . make_select_list_key($mods->modtypes,$type) . "</select>";
	echo "<input class=body type=submit value=Go></td></tr>";
	echo "</form>";


	$params = array('month' => $month, 'day' => $day, 'year' => $year, 'type' => $type);
	echo "<tr>";
		makeSortTableHeader($sortlist,"Username","username", $params);
		makeSortTableHeader($sortlist,"Level","level", $params);
		makeSortTableHeader($sortlist,"Total","total", $params);
		makeSortTableHeader($sortlist,"Right","right", $params);
		makeSortTableHeader($sortlist,"Wrong","wrong", $params);
		makeSortTableHeader($sortlist,"Strict","strict", $params);
		makeSortTableHeader($sortlist,"Lenient","lenient", $params);
		makeSortTableHeader($sortlist,"Percent","percent", $params);
		makeSortTableHeader($sortlist,"Last Moded","time", $params);
		makeSortTableHeader($sortlist,"Creation Time","creationtime", $params);
	echo "</tr>";

	$nummods = 0;
	$level = 0;
	$total = 0;
	$right = 0;
	$wrong = 0;
	$error = 0;
	$lenient = 0;
	$strict = 0;

	foreach($rows as $line){
		if($line['new'])
			continue;
		echo "<tr>";
		echo "<td class=body><a class=body href=profile.php?uid=$line[userid]>$line[username]</a></td>";
		echo "<td class=body>$line[level]</td>";
		echo "<td class=body align=right>$line[total]</td>";
		echo "<td class=body align=right>$line[right]</td>";
		echo "<td class=body align=right>$line[wrong]</td>";
		echo "<td class=body align=right>$line[strict]</td>";
		echo "<td class=body align=right>$line[lenient]</td>";
		echo "<td class=body align=right>" . number_format($line['percent'],2) . "%</td>";
		echo "<td class=body>" . ($line['time'] == 0 ? "Never" : userDate("M j, Y G:i", $line['time']) ) . "</td>";
		echo "<td class=body>" . ($line['creationtime'] == 0 ? "Unknown" : userDate("M j, Y G:i", $line['creationtime']) ) . "</td>";
		echo "</tr>";
		$nummods++;
		$level += $line['level'];
		$total += $line['total'];
		$right += $line['right'];
		$wrong += $line['wrong'];
		$lenient += $line['lenient'];
		$strict += $line['strict'];
		$error += $line['percent'];
	}

	echo "<tr>";
	echo "<td class=header>$nummods Mods</td>";
	echo "<td class=header>" . ($nummods > 0 ? number_format($level/$nummods,2) : "0.00") . "</td>";
	echo "<td class=header align=right>$total</td>";
	echo "<td class=header align=right>$right</td>";
	echo "<td class=header align=right>$wrong</td>";
	echo "<td class=header align=right>$strict</td>";
	echo "<td class=header align=right>$lenient</td>";
	echo "<td class=header align=right>" . ($nummods > 0 ? number_format($error/$nummods,2) : "0.00") . "%</td>";
	echo "</td><td class=header colspan=3 align=right></td></tr>";

	echo "</table>";

	incFooter();


