<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"contests"))
		die("Permission denied");

	switch($action){
		case "Add Contest":

			$month = getPOSTval('month', 'int');
			$day = getPOSTval('day', 'int');
			$year = getPOSTval('year', 'int');

			$end = my_gmmktime(0,0,0, $month, $day, $year);

			$content = getPOSTval('content');
			$final = getPOSTval('after');

			$contests->addContest($name, $end, getREQval('anon', 'bool'), $content, $final);
			break;

		case "Update Contest":

			$month = getPOSTval('month', 'int');
			$day = getPOSTval('day', 'int');
			$year = getPOSTval('year', 'int');

			$end = my_gmmktime(0,0,0, $month, $day, $year);

			$content = getPOSTval('content');
			$final = getPOSTval('after');

			$contests->updateContest($id, $name, $end, getREQval('anon', 'bool'), $content, $final);
			break;

		case "add":
			addContest(); //exit
		case "edit":
			addContest(getREQval('id', 'int'));//exit

		case "choose":
			if(!($id = getREQval('id', 'int')))
				break;

			$winners = $contests->chooseWinner($id, 20);

			$users = getUserInfo(array_keys($winners));

			$locations = new category( $configdb, "locs");

			incHeader();

			echo "<table>";
			echo "<tr>";
			echo "<td class=header></td>";
			echo "<td class=header>Username</td>";
			echo "<td class=header>Age</td>";
			echo "<td class=header>Sex</td>";
			echo "<td class=header>Loc</td>";
			echo "<td class=header>Contact</td>";
			echo "</tr>";

			$i=1;
			foreach($winners as $line){
				echo "<tr>";
				echo "<td class=body align=right>" . $i++ . " </td>";
				if(isset($users[$line['userid']])){
					echo "<td class=body><a class=body href=/profile.php?uid=$line[userid]>" . $users[$line['userid']]['username'] . "</a></td>";
					echo "<td class=body>" . $users[$line['userid']]['age'] . "</td>";
					echo "<td class=body>" . $users[$line['userid']]['sex'] . "</td>";
					echo "<td class=body>" . $locations->getCatName($users[$line['userid']]['loc']) . "</td>";
				}else{
					echo "<td class=body colspan=4>Anonymous</td>";
				}
				echo "<td class=body>" . nl2br($line['contact']) . "</td>";
				echo "</tr>";
			}
			echo "</table>";
			incFooter();
			exit;
	}

	$mods->adminlog('list contests',"list contests");

	$rows = $contests->getContests();


	incHeader();


	echo "<table><form action=$_SERVER[PHP_SELF] method=post>\n";

	echo "<tr>";
	echo "<td class=header>Name</td>";
	echo "<td class=header>End</td>";
	echo "<td class=header>Entries</td>";
	echo "<td class=header>Choose Winner</td>";
	echo "<td class=header>Edit</td>";
	echo "</tr>";

	foreach($rows as $row){
		echo "<tr>";
		echo "<td class=body><a class=body href=/contest.php?id=$row[id]>$row[name]</a></td>";
		echo "<td class=body>" . userdate("m/d/y H:i", $row["end"]) . "</td>";
		echo "<td class=body>$row[entries]</td>";
		echo "<td class=body><a class=body href=$_SERVER[PHP_SELF]?action=choose&id=$row[id]>Choose</a></td>";
		echo "<td class=body><a class=body href=$_SERVER[PHP_SELF]?action=edit&id=$row[id]>Edit</a></td>";
		echo "</tr>";
	}
	echo "<tr><td class=header colspan=5 align=right><a class=header href=$_SERVER[PHP_SELF]?action=add>Add Contest</a></td></tr>";
	echo "</form></table>\n";

	incFooter();


function addContest($id = 0, $data = array()){
	global $contests;

	for($i=1;$i<=12;$i++)
		$months[$i] = date("F", mktime(0,0,0,$i,1,0));

	$name = '';
	$content = '';
	$final = '';
	$day = 0;
	$month = 0;
	$year = 0;
	$anonymous = 'n';

	if($id){
		$res = $contests->db->prepare_query("SELECT name, content, final, end, anonymous FROM contests WHERE id = ?", $id);
		$line = $res->fetchrow();

		$name = $line['name'];
		$content = $line['content'];
		$final = $line['final'];
		$anonymous = $line['anonymous'];
		$day = gmdate('j', $line['end']);
		$month = gmdate('n', $line['end']);
		$year = gmdate('Y', $line['end']);
	}else{
		extract($data);
	}

	incHeader();

	echo "<table>";
	echo "<form action=$_SERVER[PHP_SELF] method=post>\n";
	echo "<tr><td class=header colspan=2 align=center>Add a Contest</td></tr>";
	echo "<tr><td class=body>Name:</td><td class=body><input class=body type=text size=30 name=name value='" . htmlentities($name) . "'></td></tr>";
	echo "<tr><td class=body>Draw Date:</td><td class=body>";
		echo "<select class=body name=month><option value=0>Month" . make_select_list_key($months, $month) . "</select>";
		echo "<select class=body name=day><option value=0>Day" . make_select_list(range(1,31), $day) . "</select>";
		echo "<select class=body name=year><option value=0>Year" . make_select_list(range(2005, date("Y")), $year) . "</select>";
		echo "</td></tr>";
	echo "<tr><td class=body colspan=2>" . makeCheckBox('anon', 'Allow Anonymous', ($anonymous == 'y')) . "</td></tr>";
	echo "<tr><td class=body>Contest HTML:</td><td class=body><textarea class=body name=content cols=80 rows=20>" . htmlentities($content) . "</textarea></td></tr>";
	echo "<tr><td class=body>Final HTML:</td><td class=body><textarea class=body name=after cols=80 rows=20>" . htmlentities($final) . "</textarea></td></tr>";
	echo "<tr><td class=body></td><td class=body>";

	if($id){
		echo "<input type=hidden name=id value=$id>";
		echo "<input class=body type=submit name=action value='Update Contest'>";
	}else{
		echo "<input class=body type=submit name=action value='Add Contest'>";
	}

	echo "</td><td></td></tr>";
	echo "</table></form>\n";

	incFooter();
	exit;
}


