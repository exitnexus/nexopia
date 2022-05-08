<?

	$login=1;

	require_once("include/general.lib.php");


	if(!$mods->isAdmin($userData['userid'],"pluslog"))
		die("You do not have permission to see this page");
	
	$supervisor = $mods->isAdmin($userData['userid'],"superviseinvoice");

	$hideinvoices = getREQval("hideinvoices", 'bool');
	$type = getREQval("type", 'string', 'username');

	$user = getPOSTval('user');
	
	if(!$user){
		$user = getREQval('user', 'int');
		$k = getREQval('k');

		if(!checkKey($user, $k))
			$user = '';
	}

	$uid = 0;	
	if($user){
		if(!$supervisor && $type == 'admin')
			$uid = $userData['userid'];
		elseif($type == 'userid')
			$uid = intval($user);
		else
			$uid = getUserID($user);
	}

	$defaulttimeperiod = 2*365*86400; //past 2 years

	$data = getREQval("data", "array");
	if(!isset($data['startmonth']) || $data['startmonth'] == 0 || $data['startday'] == 0 || $data['startyear'] == 0) {
		$data['startmonth'] = userdate("n", time()-$defaulttimeperiod);
		$data['startday'] = userdate("j", time()-$defaulttimeperiod);
		$data['startyear'] = userdate("Y", time()-$defaulttimeperiod);
	}
	if(!isset($data['endmonth']) || $data['endmonth'] == 0 || $data['endday'] == 0 || $data['endyear'] == 0) {
		$data['endmonth'] = userdate("n", time());
		$data['endday'] = userdate("j", time());
		$data['endyear'] = userdate("Y", time());
	}

	$start = ($data['startmonth'] == 0 && $data['startday'] == 0 && $data['startyear'] == 0 ? time()-$defaulttimeperiod : usermktime(0,0,0,$data['startmonth'],$data['startday'],$data['startyear']));
	$end   = ($data['endmonth'] == 0 && $data['endday'] == 0 && $data['endyear'] == 0 ? time() : usermktime(23,59,59,$data['endmonth'],$data['endday'],$data['endyear']));



	$rows = array();
	$users = array();

	if($uid){
		if($type == 'userid' || $type == 'username'){
			switch($action){
				case "Give Plus":
					$duration = getPOSTval('duration', 'float');
					if(empty($duration))
						break;
	
					$msgs->addMsg(addPlus($uid, $duration, 0, 0));
					$mods->adminlog('add plus',"Add Plus to $uid for $duration months");
					break;

				case "Transfer Plus":
					$to = getPOSTval('to');
					$to = getUserID($to);
					if($to){
						transferPlus($uid, $to);
						$mods->adminlog('transfer plus',"Transfer Plus from $uid to $to");
					}
					break;

				case 'remove':
					$id = getREQval('id', 'int');
					$k = getREQval('k');
					if($id && checkKey($uid, $k)){
						removePlus($uid, $id);
						$mods->adminlog('remove plus',"Remove Plus from $uid");
					}
					break;

				case "Fix Plus":
					fixPlus($uid);
					$mods->adminlog('fix plus',"Fix Plus for $uid");
					break;
			}
		}


		if($hideinvoices){		$set[] = "(`to` = 0 || trackid = 0)"; }
		if($start){ 			$set[] = "time >= #"; 		$params[] = $start; }
		if($end){				$set[] = "time <= #"; 		$params[] = $end; }
		if($type == 'admin'){	$set[] = "admin = #"; 		$params[] = $uid; }
		else{					$set[] = "userid = #"; 		$params[] = $uid; }

		$res = $db->prepare_array_query("SELECT SQL_CALC_FOUND_ROWS * FROM pluslog WHERE " . implode(" && ", $set) . " ORDER BY `time` ASC LIMIT 200", $params);

		$uids = array();

		while($line = $res->fetchrow()){
			$rows[] = $line;
			$uids[$line['to']] = $line['to'];
			$uids[$line['from']] = $line['from'];
			$uids[$line['admin']] = $line['admin'];
		}
		unset($uids[0]);

		$totalrows = $res->totalrows();

		if($uids)
			$users = getUserInfo($uids);
	}

	incHeader();


	for($i=1;$i<=12;$i++)
		$months[$i] = gmdate("F", gmmktime(0,0,0,$i,1,0));

	echo "<form action=$_SERVER[PHP_SELF] method=post>";
	echo "<table align=center>";
	echo "<tr><td class=header colspan=2 align=center>Plus Log Search</td></tr>";
	echo "<tr><td class=body align=right>Search:</td>";
		echo "<td class=body>";
		echo "<select class=body name=type>" . make_select_list(array('username','userid','admin'), $type) . "</select>";
		echo "<input class=body type=text name=user value=\"$user\" size=15>";
	echo "</td></tr>";
	echo "<tr><td class=body valign=top align=\"right\">Start Date:</td><td class=body colspan=7>";
		echo "<select class=body name=data[startmonth]><option value=0>Month" . make_select_list_key($months, $data['startmonth']) . "</select>";
		echo "<select class=body name=data[startday]><option value=0>Day" . make_select_list(range(1,31), $data['startday']) . "</select>";
		echo "<select class=body name=data[startyear]><option value=0>Year" . make_select_list(range(2004,userdate("Y")+1), $data['startyear']) . "</select>";
	echo "</td></tr>\n";
	echo "<tr><td class=body valign=top align=\"right\">End Date:</td><td class=body colspan=7>";
		echo "<select class=body name=data[endmonth]><option value=0>Month" . make_select_list_key($months, $data['endmonth']) . "</select>";
		echo "<select class=body name=data[endday]><option value=0>Day" . make_select_list(range(1,31), $data['endday']) . "</select>";
		echo "<select class=body name=data[endyear]><option value=0>Year" . make_select_list(range(2004,userdate("Y")+1), $data['endyear']) . "</select>";
	echo "</td></tr>\n";
	echo "<tr><td class=body colspan=2>" . makeCheckBox('hideinvoices', 'Hide Invoices', $hideinvoices);
	echo " <input class=body type=submit name=action value=Search></td>";
	echo "</table>";
	echo "</form>";
	echo "<br>";

	if($uid){
		echo "<table align=center>";

		echo "<tr>";
		echo "<td class=header>ID</td>";
		echo "<td class=header>Time</td>";
		echo "<td class=header>Duration</td>";

		echo "<td class=header>From</td>";
		echo "<td class=header>To</td>";
		echo "<td class=header>Admin</td>";
		echo "<td class=header>Tracking</td>";

		echo "<td class=header>Remove</td>";
		echo "</tr>";

		if($rows){
			$taken = array();

			foreach($rows as $row){
				$moved = false;

				echo "<tr>";
				echo "<td class=body>$row[id]</td>";
				echo "<td class=body>" . userDate("D M j, Y G:i:s", $row['time']) . "</td>";
				echo "<td class=body align=right>" . number_format($row['duration']/(86400*31),2) . " months</td>";
				echo "<td class=body>" . ($row['from'] ? ( isset($users[$row['from']]) ? "<a class=body href=/profile.php?uid=$row[from]>" . $users[$row['from']]['username'] . "</a>" : "(deleted: $row[from])" ) : '' ) . "</td>";
				echo "<td class=body>" . ($row['to'] ? ( isset($users[$row['to']]) ? "<a class=body href=/profile.php?uid=$row[to]>" . $users[$row['to']]['username'] . "</a>" : "(deleted: $row[to])" ) : '' ) . "</td>";
				echo "<td class=body>" . ($row['admin'] ? ( isset($users[$row['admin']]) ? "<a class=body href=/profile.php?uid=$row[admin]>" . $users[$row['admin']]['username'] . "</a>" : "(deleted: $row[admin])" ) : '' ) . "</td>";
				echo "<td class=body>";
				if($row['trackid']){
					if($row['to'])
						echo "<a class=body href=/invoice.php?id=$row[trackid]>Invoice</a>";
					else{
						echo "Removed: $row[trackid]";
						$taken[$row['trackid']] = $row['trackid'];
					}
				}elseif($row['userid'] == $row['from']){
					if($row['to']){
						echo "<a class=body href=/adminpluslog.php?user=$row[to]&k=" . makeKey($row['to']) . ">Moved To</a>";
						$moved = true;
					}
				}elseif($row['userid'] == $row['to']){
					if($row['from']){
						echo "<a class=body href=/adminpluslog.php?user=$row[from]&k=" . makeKey($row['from']) . ">Moved From</a>";
					}else{
						echo "Given";
					}
				}
				echo "</td>";

				echo "<td class=body>";
				if(!$moved && !isset($taken[$row['id']]) && $row['duration'] > 0)
					echo "<a class=body href=\"javascript:confirmLink('$_SERVER[PHP_SELF]?action=remove&user=$uid&id=$row[id]&k=" . makeKey($uid) . "','remove this plus?')\">Remove</a>";
				echo "</td>";

				echo "</tr>";
			}
		}else{
			echo "<tr><td class=body colspan=8 align=center>None in that time period</td></tr>";
		}

		if(count($rows) < $totalrows)
			echo "<tr><td class=body colspan=8 align=center>Showing " . number_format(count($rows)) . " of " . number_format($totalrows) . "</td></tr>";


		if($type != 'admin'){
			$expiry = getPlusExpiry($uid);

			echo "<tr><td class=header colspan=8>";
			if($expiry > time())
				echo "Expires: " . userdate("D M j, Y G:i:s", $expiry);
			else
				echo "Expired";
	
			echo "</td></tr>";
		}

		echo "</table>";
		echo "<br>";

		if($uid && $type != 'admin'){
			echo "<table align=center>";

		//add plus
			echo "<form action=$_SERVER[PHP_SELF] method=post>";
			echo "<input type=hidden name=user value=$uid>";

			echo "<input type=hidden name=data[startmonth] value=$data[startmonth]>";
			echo "<input type=hidden name=data[startday] value=$data[startday]>";
			echo "<input type=hidden name=data[startyear] value=$data[startyear]>";

			echo "<input type=hidden name=data[endmonth] value=$data[endmonth]>";
			echo "<input type=hidden name=data[endday] value=$data[endday]>";
			echo "<input type=hidden name=data[endyear] value=$data[endyear]>";

			if($hideinvoices)
				echo "<input type=hidden name=hideinvoices value=On>";

			echo "<tr><td class=header colspan=2 align=center>Add Plus</td></tr>";
			echo "<tr><td class=body>Give <input class=body type=text name=duration size=3> months.</td><td class=body><input class=body type=submit name=action value='Give Plus'></td></tr>";
			echo "</form>";
			echo "<tr><td class=body>&nbsp;</td></tr>";

		//transfer plus
			if(isset($expiry) && $expiry > time()){
				echo "<form action=$_SERVER[PHP_SELF] method=post>";
				echo "<input type=hidden name=user value=$uid>";
	
				echo "<input type=hidden name=data[startmonth] value=$data[startmonth]>";
				echo "<input type=hidden name=data[startday] value=$data[startday]>";
				echo "<input type=hidden name=data[startyear] value=$data[startyear]>";
	
				echo "<input type=hidden name=data[endmonth] value=$data[endmonth]>";
				echo "<input type=hidden name=data[endday] value=$data[endday]>";
				echo "<input type=hidden name=data[endyear] value=$data[endyear]>";
	
				if($hideinvoices)
					echo "<input type=hidden name=hideinvoices value=On>";
	
				echo "<tr><td class=header colspan=2 align=center>Transfer Plus</td></tr>";
				echo "<tr><td class=body>To <input class=body type=text name=to size=10></td><td class=body><input class=body type=submit name=action value='Transfer Plus'></td></tr>";
				echo "</form>";
				echo "<tr><td class=body colspan=2>&nbsp;</td></tr>";
			}

		//fix plus
			echo "<form action=$_SERVER[PHP_SELF] method=post>";
			echo "<input type=hidden name=user value=$uid>";

			echo "<input type=hidden name=data[startmonth] value=$data[startmonth]>";
			echo "<input type=hidden name=data[startday] value=$data[startday]>";
			echo "<input type=hidden name=data[startyear] value=$data[startyear]>";

			echo "<input type=hidden name=data[endmonth] value=$data[endmonth]>";
			echo "<input type=hidden name=data[endday] value=$data[endday]>";
			echo "<input type=hidden name=data[endyear] value=$data[endyear]>";

			if($hideinvoices)
				echo "<input type=hidden name=hideinvoices value=On>";

			echo "<tr><td class=header colspan=2 align=center>Fix Plus</td></tr>";
			echo "<tr><td class=body><input class=body type=submit name=action value='Fix Plus'></td></tr>";
			echo "</form>";
			echo "<tr><td class=body colspan=2>&nbsp;</td></tr>";

			echo "</table>";
		}
	}

	incFooter();
