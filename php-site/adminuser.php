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
						'state'			=> 'State',
						'premiumexpiry'	=> 'Plus',
						'hits'			=> 'Hits',
						'age'			=> 'Age',
						'sex'			=> 'Sex',
						'loc'			=> 'Location',
						'email'			=> 'Email Address');


	$pagelen = 50; //this is per server, so 4 servers means it can return up to 4*50=200 results. Page 2 doesn't necessarily follow from page 1 in sorted order

	$searchtypes = array('username' => 'Username',
	                     'userid' => "Userid",
						 'email' => 'Email',
						 'ip' => 'IP'
						 );

	$freezetimes = array(
			3600 => "1 hour",
			3*3600 => "3 hours",
			12*3600 => "12 hours",
			86400 => "1 day",
			2*86400 => "2 days",
			3*86400 => "3 days",
			5*86400 => "5 days",
			7*86400 => "1 week",
			14*86400 => "2 weeks",
			30*86400 => "1 month",
			60*86400 => "2 months",
			0 => "Indefinitely",
		);

	if(!($type = getREQval('type')) || !isset($searchtypes[$type]))
		$type = key($searchtypes);

	$search	= getPOSTval('search');

	if(!$search){
		$search	= getREQval('search');

		if($search && !checkKey($search, getREQval('k')))
			$search = "";
	}

	$sortt 	= getREQval('sortt');
	$sortd 	= getREQval('sortd');

	$reason = getPOSTval('reason');
	$input 	= getPOSTval('input');

	$checkID = getPOSTval('checkID', 'array');
	$freezetime = getPOSTval('freezetime', 'int', -1);

	for($i=1;$i<=12;$i++)
		$months[$i] = date("F", mktime(0,0,0,$i,1,0));

	$locations = new category( $configdb, "locs");

	if(count($checkID)){
		switch($action){
			case "delete":
				if(!$mods->isAdmin($userData['userid'],"deleteusers"))
					break;

				foreach($checkID as $check){
					if($useraccounts->delete($check, $abuselog->reasons[$reason] . ": $input")){
						$mods->adminlog('delete user',"Delete user $check: " . $abuselog->reasons[$reason] . ": $input");
						$abuselog->addAbuse($check, ABUSE_ACTION_DELETE_ACCOUNT, $reason, $input, "");
						$msgs->addMsg("$check Account deleted");
						$cache->remove("userprefs-$check");
					}else
						$msgs->addMsg("Could not delete account $check");
				}
				break;

			case "banemail":
				if(!$mods->isAdmin($userData['userid'],"banusers"))
					break;

				$res = $masterdb->prepare_query("SELECT userid, email FROM useremails WHERE userid IN (#)", $checkID);

				while($line = $res->fetchrow())
					$db->prepare_query("INSERT IGNORE INTO bannedusers SET banned = ?, userid = #, date = #, modid = #", $line['email'], $line['userid'], time(), $userData['userid']);


				foreach($checkID as $user){
					$abuselog->addAbuse($user, ABUSE_ACTION_EMAIL_BAN, $reason, $input, "");
					$mods->adminlog('ban email',"Ban email from user $user: $input");
					$msgs->addMsg("email banned for user $user");
				}
				break;

			case "banip":
				if(!$mods->isAdmin($userData['userid'],"banusers"))
					break;

				$res = $usersdb->prepare_query("SELECT userid, ip FROM users WHERE ip != 0 &&  userid IN (%)", $checkID);

				while($line = $res->fetchrow())
					$db->prepare_query("INSERT IGNORE INTO bannedusers SET banned = ?, userid = #, date = #, modid = #", $line['ip'], $line['userid'], time(), $userData['userid']);


				foreach($checkID as $user){
					$abuselog->addAbuse($user, ABUSE_ACTION_IP_BAN, $reason, $input, "");
					$mods->adminlog('ban ip',"Ban ip from user $user: $input");
					$msgs->addMsg("IP banned");
				}
				break;

			case "activate":
				if(!$mods->isAdmin($userData['userid'],"activateusers"))
					break;

				foreach($checkID as $check){
					if($useraccounts->activate($check)){
						$mods->adminlog('activate user',"Activate user $check: $input");
						$msgs->addMsg("$check activated");
					}
				}
				break;

			case "freeze":
				if(!$mods->isAdmin($userData['userid'],"deleteusers"))
					break;

				foreach($checkID as $check){
					if($useraccounts->freeze($check, $freezetime)){
						$abuselog->addAbuse($check, ABUSE_ACTION_FREEZE_ACCOUNT, $reason, $input, "");
						$mods->adminlog('freeze user',"Freeze user $check: $input");
						$msgs->addMsg("$check frozen");
						$cache->remove("userprefs-$check");
						$cache->remove("userinfo-$check");
					}else{
						$msgs->addMsg("$check already frozen");
					}
				}

				break;

			case "unfreeze":
				if(!$mods->isAdmin($userData['userid'],"deleteusers"))
					break;

				foreach($checkID as $check){

					if($useraccounts->unfreeze($check)){
						$abuselog->addAbuse($check, ABUSE_ACTION_UNFREEZE_ACCOUNT, $reason, $input, "");
						$mods->adminlog('unfreeze user',"UnFreeze user $check: $input");
						$msgs->addMsg("$check unfrozen");
					}else{
						$msgs->addMsg("$check already unfrozen");
					}
				}


				break;
		}
	}

	if(!empty($search)){

		isValidSortd($sortd, 'DESC');
		
		$sortselectable = $selectable;
		unset($sortselectable['username'],
		      $sortselectable['hits'],
		      $sortselectable['email']
		      );
	//can't sort by username, hits or email as they're fetched afterwards
		isValidSortt($sortselectable, $sortt, 'activetime');

		$page = getREQval('page', 'int');

		$users = array();
		$numpages = 0;

		$searchid = trim($search);
		$firstuid = 0;

		$ip = "";

		switch($type){
			case "username": //fall through to a userid search
				$searchid = getUserID($searchid);

			case "userid":
				$res = $usersdb->prepare_query("SELECT * FROM users WHERE userid = %", $searchid);
				$user = $res->fetchrow();
				if($user){
					$ip = $user['ip'];
					$users[$user['userid']] = $user;
				}
				$firstuid = $user['userid'];
				$mods->adminlog('search userid',"Search user: $search");
				break;

			case "email":
				$res = $masterdb->prepare_query('SELECT userid FROM useremails WHERE email = ?', $searchid);
				$row = $res->fetchrow();
				$userid = $row['userid'];

				$res = $usersdb->prepare_query("SELECT * FROM users WHERE userid = %", $userid);
				$user = $res->fetchrow();
				if($user){
					$ip = $user['ip'];
					$users[$user['userid']] = $user;
				}
				$firstuid = $user['userid'];
				$mods->adminlog('search email',"Search email: $search");
				break;

			case "ip":
				$ip = ip2long($searchid);
				$mods->adminlog('search ip',"Search ip: $search");
				break;
		}



		if($ip!=""){
			$res = $usersdb->prepare_query("SELECT SQL_CALC_FOUND_ROWS * FROM users WHERE ip = # ORDER BY $sortt $sortd LIMIT #,#", $ip, ($page*$pagelen), $pagelen);

			while($line = $res->fetchrow()){
				$users[$line['userid']] = $line;
				$users[$line['userid']]['hits'] = 0;
			}

			$numrows = $res->totalrows();
			$numpages =  ceil($numrows / $config['linesPerPage']);
		}

		if(count($users)){
			$res = $masterdb->prepare_query("SELECT userid, username FROM usernames WHERE userid in (#) && live = 'y'", array_keys($users));
			while($line = $res->fetchrow())
				$users[$line['userid']]['username'] = $line['username'];

			$res = $usersdb->prepare_query("SELECT userid, hits, activetime FROM useractivetime WHERE userid IN (%)", array_keys($users));

			while($line = $res->fetchrow()){
				$users[$line['userid']]['hits'] = $line['hits'];
				$users[$line['userid']]['activetime'] = $line['activetime'];
			}

			$res = $masterdb->prepare_query("SELECT userid, email FROM useremails WHERE userid in (#) && active = 'y'", array_keys($users));
			while($line = $res->fetchrow())
				$users[$line['userid']]['email'] = $line['email'];

			sortCols($users, SORT_ASC, SORT_CASESTR, 'username', ($sortd == 'ASC' ? SORT_ASC : SORT_DESC), SORT_STRING, $sortt);
			
		//put the searched user at the top again
			if($firstuid){
				$user = $users[$firstuid];
				unset($users[$firstuid]);
				array_unshift($users, $user);
			}
		}
	}

	incHeader();

	echo "<table align=center>";
	echo "<form action=$_SERVER[PHP_SELF] method=post>";
	echo "<tr><td class=header colspan=2 align=center>Search User</td></tr>";
	echo "<tr><td class=body><select name=type class=body>" . make_select_list_key($searchtypes, $type) . "</select><input class=body type=text name=search value='" . htmlentities($search) . "' size=40><input class=body type=submit value=Search></td></tr>";
	echo "</form>";
	echo "</table>";

	if(isset($users) && count($users)){
		echo "<table border=0 cellspacing=1 cellpadding=2 align=center><form action=$_SERVER[PHP_SELF] name=users method=post>";
		echo "<tr><td class=body colspan=" . (6 + count($selectable)) . " align=center>IP: " . long2ip($ip) . ", Hostname: " . gethostbyaddr(long2ip($ip)) . "</td></tr>";
		echo "<tr>";
		echo "<td class=header width=22>&nbsp;</td>";

		if($mods->isAdmin($userData['userid'],"editpictures"))
			echo "<td class=header>&nbsp;</td>";
		if($mods->isAdmin($userData['userid'],"editprofile"))
			echo "<td class=header>&nbsp;</td>";
		if($mods->isAdmin($userData['userid'],"editpreferences"))
			echo "<td class=header>&nbsp;</td>";
		if($mods->isAdmin($userData['userid'],"loginlog"))
			echo "<td class=header>&nbsp;</td>";
		echo "<td class=header>&nbsp;</td>";
		echo "<td class=header>&nbsp;</td>";

		$varlist = array();

		$varlist['search']=$search;
		$varlist['type']=$type;
		$varlist['k']=makeKey($search);

		foreach($selectable as $k => $n)
			echo makeSortTableHeader($n,$k,$varlist);

	//added after the headers are created, as they are added automatically by makeSortTableHeader
		$varlist['sortd']=$sortd;
		$varlist['sortt']=$sortt;

		echo "</tr>";

		$classes = array('body2','body');
		$i=0;

		if(!isset($checkID))
			$checkID = array();
		if(!isset($reason))
			$reason = 0;

		$time = time();

		$hosts = array();
		foreach($users as $line){
			$i = !$i;
			echo "<tr>";
			echo "<td class=header><input type=checkbox name=checkID[] value=$line[userid]" . (in_array($line['userid'], $checkID) ? " checked" : "" ) . "></td>";
			if($mods->isAdmin($userData['userid'],"editpictures"))
				echo "<td class=header><a class=header href=/managepicture.php?uid=$line[userid]>Pics</a></td>";
			if($mods->isAdmin($userData['userid'],"editprofile"))
				echo "<td class=header><a class=header href=/manageprofile.php?uid=$line[userid]>Profile</a></td>";
			if($mods->isAdmin($userData['userid'],"editpreferences"))
				echo "<td class=header><a class=header href=/prefs.php?uid=$line[userid]>Prefs</a></td>";
			if($mods->isAdmin($userData['userid'],"loginlog"))
				echo "<td class=header><a class=header href=/adminloginlog.php?col=user&val=$line[userid]&k=" . makeKey($line['userid']) . ">Logins</a></td>";
			echo "<td class=header><a class=header href=/adminuserips.php?uid=$line[userid]&type=userid&k=" . makeKey($line['userid']) . ">IPs</a></td>";
			echo "<td class=header><a class=header href=/adminabuselog.php?uid=$line[username]>Abuse</a></td>";

			$select = array_keys($selectable);
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
						echo "<a class=body href=/profile.php?uid=$line[userid]>$line[username]</a>";
						break;
					case 'jointime':
						echo userdate("M j, y, G:i", $line[$n]);
						break;
					case 'activetime':
						if($line[$n]){
							if($line[$n] >= $time - $config['maxAwayTime'])
								echo "<b>";

							echo userdate("M j, y, G:i",$line[$n]);

							if($line[$n] >= $time - $config['maxAwayTime'])
								echo "</b>";
						}else{
							echo "Never";
						}

						break;
					case 'premiumexpiry':
						echo ($line[$n] > $time ? userdate("M j, y",$line[$n]) : "No");
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
					case 'state':
						switch($line[$n]){
							case 'new':
								echo "New";
								break;
							case 'active':
								echo 'Active';
								break;
							case 'frozen':
								if($line['frozentime']){
									if($line['frozentime'] > time())
										echo "Frozen: " . userdate("M j, y", $line['frozentime']);
									else
										echo "Unfrozen";
								}else{
									echo "Frozen: Perm";
								}
								break;
						}
						break;
					default:
						echo "Error: $n, $line[$n]";
						break;
				}
				echo "</td>";
			}
			echo "</tr>";
		}
		echo "<tr><td class=header colspan=" . (count($selectable)+7) . ">";

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
					echo "<option value=unfreeze>UnFreeze User";
					echo "<option value=freeze>Freeze User";
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

			echo "<select class=body name=reason><option value=0>Reason" . make_select_list_key($abuselog->reasons, $reason) . "</select>";
			echo "<select class=body name=freezetime><option value=0>Freeze Length" . make_select_list_key($freezetimes, $freezetime) . "</select>";

			echo "<input class=body type=text name=input size=50>";
			echo "<input class=body type=submit value=Go onClick=\"if(document.users.reason.selectedIndex==0 || document.users.input.value==''){alert('You must specify a reason.'); return false;}\">";
		}

		echo "</td><td class=header align=right>";

		foreach($varlist as $n => $v)
			$list[] = "$n=$v";
		echo "Page: " . pageList("$_SERVER[PHP_SELF]?" . implode("&",$list),$page,$numpages,'header');

		echo "</td></tr></table>";

		echo "</td></tr>";
		echo "</form></table>";
	}

	incFooter();

