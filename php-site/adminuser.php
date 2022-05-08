<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isAdmin($userData['userid'],"listusers"))
		die("Permission denied");

	$selectable=array(
						'userid'		=> 'Userid',
						'username'		=> 'Username',
						'jointime'		=> 'Join Time',
						'activetime'	=> 'Active Time',
//						'ip'			=> 'IP address',
//						'hostname'		=> 'Host Name',
						'activated'		=> 'Activated',
						'frozen'		=> 'Frozen',
						'premiumexpiry'	=> 'Plus',
						'hits'			=> 'Number of Hits',
						'age'			=> 'Age',
						'sex'			=> 'Sex',
						'loc'			=> 'Location',
						'email'			=> 'Email Address');

	$dbselectable=array('users.username'=> 'Username',
						'users.userid'	=> 'Userid',
						'jointime'		=> 'Join Time',
						'activetime'	=> 'Active Time',
						'ip'			=> 'IP address',
						'activated'		=> 'Activated',
						'frozen'		=> 'Frozen',
						'premiumexpiry'	=> 'Plus',
						'hits'			=> 'Number of Hits',
						'age'			=> 'Age',
						'sex'			=> 'Sex',
						'loc'			=> 'Location',
						'email'			=> 'Email Address');


	$searchtypes = array('userid' => "Userid", 'username' => 'Username', 'email' => 'Email', 'ip' => 'IP');

	if(empty($search))
		$search = "";
	if(empty($type))
		$type = "username";

	for($i=1;$i<=12;$i++)
		$months[$i] = date("F", mktime(0,0,0,$i,1,0));

	$locations = & new category("locs");

	switch($action){
		case "delete":
			if(!$mods->isAdmin($userData['userid'],"deleteusers"))
				break;

			if(isset($checkID) && is_array($checkID))
				foreach($checkID as $check){
					$mods->adminlog('delete user',"Delete user $check: $input");
					if(deleteAccount($check,$input))
						$msgs->addMsg("$check Account deleted");
					else
						$msgs->addMsg("Could not delete account $check");
				}
			break;
		case "banemail":
			if(!$mods->isAdmin($userData['userid'],"banusers"))
				break;
			if(isset($checkID) && is_array($checkID)){
				$db->prepare_query("INSERT INTO bannedusers (email, type) SELECT email, 'email' FROM users WHERE userid IN (?)", $checkID);
				foreach($checkID as $user){
					$mods->adminlog('ban email',"Ban email from user $user: $input");
					$msgs->addMsg("email banned for user $user");
				}
			}
			break;
		case "banip":
			if(!$mods->isAdmin($userData['userid'],"banusers"))
				break;
			if(isset($checkID) && is_array($checkID)){
				$db->prepare_query("INSERT INTO bannedusers (ip, type) SELECT DISTINCT ip, 'ip' FROM users WHERE ip != 0 && userid IN (?)", $checkID);

				foreach($checkID as $user){
					$mods->adminlog('ban ip',"Ban ip $banip from user $user: $input");
					$msgs->addMsg("IP banned");
				}
			}
			break;
		case "activate":
			if(!$mods->isAdmin($userData['userid'],"activateusers"))
				break;

			if(isset($checkID))
				foreach($checkID as $check){
					activateAccount($check);
					$mods->adminlog('activate user',"Activate user $check: $input");
					$msgs->addMsg("$check activated");
				}
			break;
		case "deactivate":
			if(!$mods->isAdmin($userData['userid'],"activateusers"))
				break;

			if(isset($checkID))
				foreach($checkID as $check){
					deactivateAccount($check);
					$mods->adminlog('deactivate user',"Deactivate user $check: $input");
					$msgs->addMsg("$check deactivated");
				}
			break;
		case "freeze":
			if(!$mods->isAdmin($userData['userid'],"deleteusers"))
				break;

			if(isset($checkID))
				foreach($checkID as $check){
					$mods->adminlog('freeze user',"Freeze user $check: $input");
					$msgs->addMsg("$check frozen");
				}
				$db->prepare_query("UPDATE users SET frozen='y' WHERE userid IN (?)", $checkID);

			break;
		case "unfreeze":
			if(!$mods->isAdmin($userData['userid'],"deleteusers"))
				break;

			if(isset($checkID))
				foreach($checkID as $check){
					$mods->adminlog('unfreeze user',"UnFreeze user $check: $input");
					$msgs->addMsg("$check unfrozen");
				}
				$db->prepare_query("UPDATE users SET frozen='n' WHERE userid IN (?)", $checkID);

			break;
	}

	if(!empty($search)){

		isValidSortd($sortd,'ASC');
		isValidSortt($selectable,$sortt);

		if(empty($page)) $page=0;

		$selects = array_keys($dbselectable);
		$select = array_keys($selectable);

		$users = array();
		$numpages = 0;

		$search = trim($search);

		$ip = "";

		switch($type){
			case "userid":
				$db->prepare_query("SELECT " . implode(',',$selects) . " FROM users WHERE userid = ?",$search);
				if($db->numrows()){
					$user = $db->fetchrow();
					$ip = $user['ip'];
					$users[$user['userid']] = $user;
				}
				$mods->adminlog('search userid',"Search userid: $search");
				break;
			case "username":
				$db->prepare_query("SELECT " . implode(',',$selects) . " FROM users WHERE username = ?",$search);
				if($db->numrows()){
					$user = $db->fetchrow();
					$ip = $user['ip'];
					$users[$user['userid']] = $user;
				}
				$mods->adminlog('search username',"Search username: $search");
				break;
			case "email":
				$db->prepare_query("SELECT " . implode(',',$selects) . " FROM users WHERE email = ?",$search);
				if($db->numrows()){
					$user = $db->fetchrow();
					$ip = $user['ip'];
					$users[$user['userid']] = $user;
				}
				$mods->adminlog('search email',"Search email: $search");
				break;
			case "ip":
				$ip = ip2long($search);
				$mods->adminlog('search ip',"Search ip: $search");
				break;
		}



		if($ip!=""){
			$db->prepare_query("SELECT SQL_CALC_FOUND_ROWS " . implode(',',$selects) . " FROM users WHERE ip = ? ORDER BY $sortt $sortd, username DESC LIMIT " . ($page*$config['linesPerPage']) . ", $config[linesPerPage]", $ip);

			while($line = $db->fetchrow())
				$users[$line['userid']] = $line;

			$db->query("SELECT FOUND_ROWS()");
			$numrows = $db->fetchfield();
			$numpages =  ceil($numrows / $config['linesPerPage']);
		}

		if(count($users)){
			$fastdb->prepare_query("SELECT userid, hits, activetime FROM useractivetime WHERE userid IN (?)", array_keys($users));

			while($line = $fastdb->fetchrow()){
				$users[$line['userid']]['hits'] = $line['hits'];
				$users[$line['userid']]['activetime'] = $line['activetime'];
			}
		}
	}

	incHeader();

	echo "<table align=center>";
	echo "<form action=$PHP_SELF>";
	echo "<tr><td class=header colspan=2 align=center>Search User</td></tr>";
	echo "<tr><td class=body><select name=type class=body>" . make_select_list_key($searchtypes, $type) . "</select><input class=body type=text name=search value='" . htmlentities($search) . "' size=40><input class=body type=submit value=Search></td></tr>";
	echo "</form>";
	echo "</table>";

	if(count($users)){
		echo "<table border=0 cellspacing=1 cellpadding=2 align=center><form action=$PHP_SELF name=users>";
		echo "<tr><td class=body colspan=" . (54 + count($selectable)) . " align=center>IP: " . long2ip($ip) . ", Hostname: " . gethostbyaddr(long2ip($ip)) . "</td></tr>";
		echo "<tr>";
		echo "<td class=header width=22>&nbsp;</td>";

		if($mods->isAdmin($userData['userid'],"editpictures"))
			echo "<td class=header>&nbsp;</td>";
		if($mods->isAdmin($userData['userid'],"editprofile"))
			echo "<td class=header>&nbsp;</td>";
		if($mods->isAdmin($userData['userid'],"editpreferences"))
			echo "<td class=header>&nbsp;</td>";
		echo "<td class=header>&nbsp;</td>";

		$varlist = array();

		$varlist['search']=$search;
		$varlist['type']=$type;
		$varlist['sortt']=$sortt;
		$varlist['sortd']=$sortd;

		foreach($selectable as $k => $n){
			makeSortTableHeader('',$n,$k,$varlist);
		}
		echo "</tr>";

		$classes = array('body2','body');
		$i=0;


		$hosts = array();
		foreach($users as $line){
			$i = !$i;
			echo "<tr>";
			echo "<td class=header><input type=checkbox name=checkID[] value=$line[userid]></td>";
			if($mods->isAdmin($userData['userid'],"editpictures"))
				echo "<td class=header><a class=header href=managepicture.php?uid=$line[userid]>Pics</a></td>";
			if($mods->isAdmin($userData['userid'],"editprofile"))
				echo "<td class=header><a class=header href=manageprofile.php?uid=$line[userid]>Profile</a></td>";
			if($mods->isAdmin($userData['userid'],"editpreferences"))
				echo "<td class=header><a class=header href=prefs.php?uid=$line[userid]>Prefs</a></td>";
			echo "<td class=header><a class=header href=adminuserips.php?uid=$line[userid]>IPs</a></td>";
			foreach($select as $n){
				echo "<td class=$classes[$i] nowrap>";
				switch($n){
					case 'userid':
					case 'hits':
					case 'email':
					case 'age':
					case 'sex':
						echo $line[$n];
						break;
					case 'username':
						echo "<a class=body href=profile.php?uid=$line[userid]>$line[username]</a>";
						break;
					case 'jointime':
					case 'activetime':
						if($line[$n])
							echo userdate("D M j, Y G:i:s",$line[$n]);
						else
							echo "Never";
						break;
					case 'premiumexpiry':
						if($line[$n] > time())
							echo userdate("M j, Y",$line[$n]);
						else
							echo "No";
						break;
					case 'loc':
						echo $locations->getCatName($line['loc']);
						break;
					case 'ip':
						echo long2ip($line['ip']);
						break;
					case 'hostname':
						if(!isset($hosts[$line['ip']]))
							$hosts[$line['ip']] = gethostbyaddr(long2ip($line['ip']));
						echo $hosts[$line['ip']];
						break;
					case 'activated':
					case 'frozen':
						if($line[$n]=='y')
							echo "Yes";
						else
							echo "No";
						break;
					default:
						echo "Error: $n, $line[$n]";
						break;
				}
				echo "</td>";
			}
			echo "</tr>";
		}
		echo "<tr><td class=header colspan=" . (count($select)+5) . ">";

		echo "<table width=100% cellspacing=0 cellpadding=0><tr><td class=header align=left>";
		if($mods->isAdmin($userData['userid'],"listusers")){
			foreach($varlist as $n => $v)
				echo "<input type=hidden name='$n' value='$v'>";
			echo "<input type=hidden name='sortt' value='$sortt'>";
			echo "<input type=hidden name='sortd' value='$sortd'>";
			echo "<select class=body name=action>";
				echo "<option value=''>Choose an Action";
				if($mods->isAdmin($userData['userid'],"deleteusers")){
					echo "<option value=delete>Delete User";
					echo "<option value=freeze>Freeze User";
					echo "<option value=unfreeze>UnFreeze User";
				}
				if($mods->isAdmin($userData['userid'],"activateusers")){
					echo "<option value=activate>Activate Account";
					echo "<option value=deactivate>Deactivate Account";
				}
				if($mods->isAdmin($userData['userid'],"banusers")){
					echo "<option value=banip>Ban Ip";
					echo "<option value=banemail>Ban Email";
				}
			echo "</select>";
			echo "<input class=body type=text name=input size=50>";
			echo "<input class=body type=submit value=Go onClick=\"if(document.users.action.selectedIndex!=0 && document.users.input.value==''){alert('You must specify a reason.'); return false;}\">";
		}

		echo "</td><td class=header align=right>";

		foreach($varlist as $n => $v)
			$list[] = "$n=$v";
		echo "Page: " . pageList("$PHP_SELF?" . implode("&",$list),$page,$numpages,'header');

		echo "</td></tr></table>";

		echo "</td></tr>";
		echo "</form></table>";
	}

	incFooter();

