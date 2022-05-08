<?

	$login=1;

	require_once("include/general.lib.php");


	if(!$mods->isAdmin($userData['userid'],"editinvoice"))
		die("You do not have permission to see this page");
	
	$supervisor = $mods->isAdmin($userData['userid'],"superviseinvoice");

	$user = getPOSTval('user');
	
	if(!$user){
		$user = getREQval('user', 'int');
		$k = getREQval('k');

		if(!checkKey($user, $k))
			$user = '';
	}
	
	$admin = getPOSTval('admin');

	if(!$admin){
		$admin = getREQval('admin', 'int');
		$k = getREQval('k');

		if(!checkKey($admin, $k))
			$admin = '';
	}

	$data = getREQval("data", "array");
	$start = ($data['startmonth'] == 0 && $data['startday'] == 0 && $data['startyear'] == 0 ? 0 : usermktime(0,0,0,$data['startmonth'],$data['startday'],$data['startyear']));
	$end   = ($data['endmonth'] == 0 && $data['endday'] == 0 && $data['endyear'] == 0 ? 0 : usermktime(23,59,59,$data['endmonth'],$data['endday'],$data['endyear']));
	
	$uid = getUserID($user);
	$adminid = getUserID($admin);
	if (!$supervisor) {
		$adminid = $userData['userid'];
	}
	
	$rows = array();
	$users = array();

	if($uid || ($supervisor && $adminid) || $start || $end){
		if ($uid) {
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


		if ($start) {
			$set[] = "time >= #"; 		$params[] = $start;
		}
		if ($end) {
			$set[] = "time <= #"; 		$params[] = $end;
		}
		if ($uid) {
			$set[] = "userid = #"; 		$params[] = $uid;
		}
		if ($adminid) {
			$set[] = "admin = #"; 		$params[] = $adminid;
		}
		
		$res = $db->prepare_array_query("SELECT * FROM pluslog WHERE " . implode(" && ", $set), $params);
		
		$uids = array();

		while($line = $res->fetchrow()){
			$rows[] = $line;
			$uids[$line['to']] = $line['to'];
			$uids[$line['from']] = $line['from'];
			$uids[$line['admin']] = $line['admin'];
		}
		unset($uids[0]);

		if($uids)
			$users = getUserInfo($uids);
	}

	incHeader();

	echo "<table align=center>";

	for($i=1;$i<=12;$i++)
		$months[$i] = date("F", mktime(0,0,0,$i,1,0));
	
	echo "<tr><td colspan=8 class=body><table align=\"center\"><form action=$_SERVER[PHP_SELF] method=post>";
	echo "<tr><td class=header align=right>User:</td>";
		echo "<td class=body><input class=body type=text name=user value=\"$user\" size=27></td></tr>";
	if ($supervisor) {
		echo "<tr><td class=header align=right>Admin: </td>";
		echo "<td class=body><input class=body type=text name=admin value=\"$admin\" size=27></td></tr>";
	}
	echo "<tr><td class=header valign=top>Start Date:</td><td class=body colspan=7>";
		echo "<select class=body name=data[startmonth]><option value=0>Month" . make_select_list_key($months, $data['startmonth']) . "</select>";
		echo "<select class=body name=data[startday]><option value=0>Day" . make_select_list(range(1,31), $data['startday']) . "</select>";
		echo "<select class=body name=data[startyear]><option value=0>Year" . make_select_list(range(userdate("Y")-1,userdate("Y")+1), $data['startyear']) . "</select>";
	echo "</td></tr>\n";
	echo "<tr><td class=header valign=top>End Date:</td><td class=body colspan=7>";
		echo "<select class=body name=data[endmonth]><option value=0>Month" . make_select_list_key($months, $data['endmonth']) . "</select>";
		echo "<select class=body name=data[endday]><option value=0>Day" . make_select_list(range(1,31), $data['endday']) . "</select>";
		echo "<select class=body name=data[endyear]><option value=0>Year" . make_select_list(range(userdate("Y")-1,userdate("Y")+1), $data['endyear']) . "</select>";
	echo "</td></tr>\n";
	echo "<tr><td class=body colspan=4 align=center><input class=body type=submit name=action value=Go></td></tr>";
	echo "</form></table></td></tr>";

	if($uid || $adminid || $start || $end){

		if($rows){
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

			$taken = array();
			$moved = false;

			foreach($rows as $row){
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
						echo "<a class=body href=/pluslog.php?user=$row[to]&k=" . makeKey($row['to']) . ">Moved To</a>";
						$moved = true;
					}
				}elseif($row['userid'] == $row['to']){
					if($row['from']){
						echo "<a class=body href=/pluslog.php?user=$row[from]&k=" . makeKey($row['from']) . ">Moved From</a>";
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
		}

		if ($uid) {
			$expiry = getPlusExpiry($uid);
	
			echo "<tr><td class=header colspan=8>";
			if($expiry > time())
				echo "Expires: " . userdate("D M j, Y G:i:s", $expiry);
			else
				echo "Expired";
	
			echo "</td></tr>";
		}

		echo "</table>";

		echo "<table align=center>";

		if ($uid) {
			echo "<form action=$_SERVER[PHP_SELF] method=post>";
			echo "<input type=hidden name=user value=$uid>";
				
			echo "<tr><td class=header colspan=2 align=center>Add Plus</td></tr>";
			echo "<tr><td class=body>Give <input class=body type=text name=duration size=3> months.</td><td class=body><input class=body type=submit name=action value='Give Plus'></td></tr>";
			echo "</form>";
		}
		echo "<tr><td class=body>&nbsp;</td></tr>";

		if($expiry > time()){
			echo "<form action=$_SERVER[PHP_SELF] method=post>";
			echo "<input type=hidden name=user value=$uid>";
			echo "<tr><td class=header colspan=2 align=center>Transfer Plus</td></tr>";
			echo "<tr><td class=body>To <input class=body type=text name=to size=10></td><td class=body><input class=body type=submit name=action value='Transfer Plus'></td></tr>";
			echo "</form>";
			echo "<tr><td class=body colspan=2>&nbsp;</td></tr>";

			echo "<form action=$_SERVER[PHP_SELF] method=post>";
			echo "<input type=hidden name=user value=$uid>";
			echo "<tr><td class=header colspan=2 align=center>Fix Plus</td></tr>";
			echo "<tr><td class=body><input class=body type=submit name=action value='Fix Plus'></td></tr>";
			echo "</form>";
			echo "<tr><td class=body colspan=2>&nbsp;</td></tr>";
		}
	}
	echo "</table>";
	incFooter();

