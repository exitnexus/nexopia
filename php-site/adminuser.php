<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isAdmin($userData['userid'],"listusers"))
		die("Permission denied");

	$selectable = array(
						'userid'		=> 'Userid',
						'username'		=> 'Username',
						'jointime'		=> 'Join Time',
						'activetime'	=> 'Active Time',
						'diffip'		=> 'Diff IP',
						'state'			=> 'State',
						'premiumexpiry'	=> 'Plus',
						'flagged'		=> 'Flag',
						'hits'			=> 'Hits',
						'age'			=> 'Age',
						'sex'			=> 'Sex',
						'loc'			=> 'Location',
						'email'			=> 'Email Address');


	$pagelen = 50;

	$searchtypes = array('username' => 'Username',
	                     'userid' => "Userid",
						 'email' => 'Email',
						 'ip' => 'IP'
						 );

	$ipsearchtypes = array(	'recent' => "Most Recent IP",
							'all' => "All IPs",
							);
	
	if(!$mods->isAdmin($userData['userid'],"showemail"))
		unset($searchtypes['email']);

	if(!$mods->isAdmin($userData['userid'],"showip"))
		unset($searchtypes['ip']);


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

	$iptype = getREQval('iptype','string','recent');
	if(!isset($ipsearchtypes[$iptype]))
		$iptype = 'recent';
	
	if($iptype == 'recent')
		unset($selectable['diffip']);

	$sortt 	= getREQval('sortt');
	$sortd 	= getREQval('sortd');

	$reason = getPOSTval('reason');
	$input 	= getPOSTval('input');

	$checkID = getPOSTval('checkID', 'array');
	$freezetime = getPOSTval('freezetime', 'int', -1);

	for($i=1;$i<=12;$i++)
		$months[$i] = gmdate("F", gmmktime(0,0,0,$i,1,0));

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

				$res = $usersdb->prepare_query("SELECT userid, ip FROM useractivetime WHERE ip != 0 &&  userid IN (%)", $checkID);

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
				if(!$mods->isAdmin($userData['userid'],"freezeusers"))
					break;

				foreach($checkID as $check){
					if($useraccounts->freeze($check, $freezetime)){
						$abuselog->addAbuse($check, ABUSE_ACTION_FREEZE_ACCOUNT, $reason, $input . ($freezetime ? ' [ unfreeze ' . userdate("M j, y", time() + $freezetime) . ' ]' : ' [ Indefinite ]'), "");
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
				
				
			case "flag":
				if(!$mods->isAdmin($userData['userid'],"editinvoice"))
					break;

				foreach($checkID as $check){
					$res = $shoppingcart->db->prepare_query("INSERT IGNORE INTO flaggedaccounts SET userid = #", $check);

					if($res->affectedrows()){
						$abuselog->addAbuse($check, ABUSE_ACTION_NOTE, ABUSE_REASON_CREDIT, "Flag: $input", "");
						$mods->adminlog('flag user',"Flag user $check: $input");
						$msgs->addMsg("$check flagged");
					}else{
						$msgs->addMsg("$check already flagged");
					}
				}

				break;

			case "unflag":
				if(!$mods->isAdmin($userData['userid'],"editinvoice"))
					break;
				
				foreach($checkID as $check){
					$res = $shoppingcart->db->prepare_query("DELETE FROM flaggedaccounts WHERE userid = #", $check);

					if($res->affectedrows()){
						$abuselog->addAbuse($check, ABUSE_ACTION_NOTE, ABUSE_REASON_CREDIT, "Unflag: $input", "");
						$mods->adminlog('unflag user',"UnFlag user $check: $input");
						$msgs->addMsg("$check unflagged");
					}else{
						$msgs->addMsg("$check already unflagged");
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
		      $sortselectable['email'],
		      $sortselectable['flagged']
		      );
	//can't sort by username, hits or email as they're fetched afterwards
		isValidSortt($sortselectable, $sortt, 'activetime');

		$page = getREQval('page', 'int');

		$users = array();
		$userids = array();
		$numpages = 0;

		$searchid = trim($search);
		$firstuid = 0;

		$ip = "";

	//get a userid/ip pair of the first person
		switch($type){
			case "username": //fall through to a userid search
				$searchid = getUserID($searchid);

			case "userid":
				$res = $usersdb->prepare_query("SELECT userid, ip FROM useractivetime WHERE userid = %", $searchid);
				$user = $res->fetchrow();
				if($user){
					$ip = $user['ip'];
					$userids[$user['userid']] = $user['userid'];
				}
				else
				{
					$userids[$searchid] = $searchid;
				}
				$firstuid = $user['userid'];
				$mods->adminlog('search userid',"Search user: $search");
				break;

			case "email":
				$res = $masterdb->prepare_query('SELECT userid FROM useremails WHERE email = ?', $searchid);
				$row = $res->fetchrow();
				$userid = $row['userid'];

				$userids[$userid] = $userid;

				$res = $usersdb->prepare_query("SELECT ip FROM useractivetime WHERE userid = %", $userid);
				$user = $res->fetchrow();
				if($user)
					$ip = $user['ip'];

				$firstuid = $userid;
				$mods->adminlog('search email',"Search email: $search");
				break;

			case "ip":
				$ip = ip2long($searchid);
				$mods->adminlog('search ip',"Search ip: $search");
				break;
		}

	//get all the userids that have touched that ip recently/ever
		if($ip != ""){
			$table = ($iptype == 'all' ? 'userhitlog' : 'useractivetime');
			
			$res = $usersdb->prepare_query("SELECT userid FROM $table WHERE ip = #", $ip);
		
			while($line = $res->fetchrow())
				$userids[$line['userid']] = $line['userid'];
		}

	//get the user data for the user(s) in question
		if(count($userids)){

		//get info for accounts that still exist
			$res = $usersdb->prepare_query("SELECT userid, frozentime, jointime, state, premiumexpiry, age, sex, loc, abuses FROM users WHERE userid IN (%)", $userids);

			while($line = $res->fetchrow()){
				$users[$line['userid']] = $line;
				$users[$line['userid']]['hits'] = 0;
				$users[$line['userid']]['exists'] = true;
			}

			if(count($users)){
				$res = $masterdb->prepare_query("SELECT userid, username FROM usernames WHERE userid in (#) && live = 'y'", array_keys($users));
				while($line = $res->fetchrow())
					$users[$line['userid']]['username'] = $line['username'];

				$res = $usersdb->prepare_query("SELECT userid, hits, activetime, ip FROM useractivetime WHERE userid IN (%)", array_keys($users));

				while($line = $res->fetchrow()){
					$users[$line['userid']]['hits'] = $line['hits'];
					$users[$line['userid']]['activetime'] = $line['activetime'];
					$users[$line['userid']]['diffip'] = ($line['ip'] != $ip);
				}

				if($mods->isAdmin($userData['userid'], "showemail")){
					$res = $masterdb->prepare_query("SELECT userid, email FROM useremails WHERE userid in (#) ORDER BY active DESC", array_keys($users));
					while($line = $res->fetchrow())
						$users[$line['userid']]['email'][] = $line['email'];
				}

				if($mods->isAdmin($userData['userid'],"editinvoice")){
					$res = $shoppingcart->db->prepare_query("SELECT userid FROM flaggedaccounts WHERE userid IN (#)", array_keys($users));

					while($line = $res->fetchrow())
						$users[$line['userid']]['flagged'] = true;
				}
			}


		//info for deleted users
			$deluserids = array_diff($userids, array_keys($users));

			if($deluserids){
				$res = $db->prepare_query("SELECT * FROM deletedusers WHERE userid IN (#)", $deluserids);
				while($line = $res->fetchrow()){
					$line['exists'] = false;

					$line['activetime'] = $line['time'];
					$line['state'] = ($line['userid'] == $line['deleteid'] ? 'delself' : 'deladmin');
					$line['age'] = '';
					$line['sex'] = '';
					$line['loc'] = 0;
					$line['flagged'] = false;
					$line['email'] = array($line['email']);
					$line['diffip'] = false;

					$users[$line['userid']] = $line;
				}
			}

		//sort
			sortCols($users, SORT_ASC, SORT_CASESTR, 'username', ($sortd == 'ASC' ? SORT_ASC : SORT_DESC), SORT_STRING, $sortt);

		//put the searched user at the top again
			if($firstuid){
				$user = $users[$firstuid];
				unset($users[$firstuid]);
				array_unshift($users, $user);
			}
			
		//limit by page
			$numpages = ceil(count($users)/$pagelen);
			$users = array_slice($users, $page*$pagelen, $pagelen);
		}
	}

	incHeader();

	echo "<table align=center>";
	echo "<form action=$_SERVER[PHP_SELF] method=post>";
	echo "<tr><td class=header colspan=2 align=center>Search User</td></tr>";
	echo "<tr><td class=body>";
	echo "<select name=type class=body>" . make_select_list_key($searchtypes, $type) . "</select>";
	echo "<input class=body type=text name=search value='" . htmlentities($search) . "' size=40>";
	echo "<select name=iptype class=body>" . make_select_list_key($ipsearchtypes, $iptype) . "</select>";
	echo "<input class=body type=submit value=Search>";
	echo "</td></tr>";
	echo "</form>";
	echo "</table>";

	if(isset($users) && count($users)){
		$actions = array();
		if($mods->isAdmin($userData['userid'],"deleteusers")){
			$actions[] = "<option value=delete>Delete User";
			$actions[] = "<option value=unfreeze>UnFreeze User";
		}
		if($mods->isAdmin($userData['userid'],"freezeusers")){
			$actions[] = "<option value=freeze>Freeze User";
		}
		if($mods->isAdmin($userData['userid'],"activateusers")){
			$actions[] = "<option value=activate>Activate Account";
			$actions[] = "<option value=deactivate>Deactivate Account";
		}
		if($mods->isAdmin($userData['userid'],"banusers")){
			$actions[] = "<option value=banip>Ban Ip";
			$actions[] = "<option value=banemail>Ban Email";
		}
		if($mods->isAdmin($userData['userid'],"editinvoice")){
			$actions[] = "<option value=flag>Flag User";
			$actions[] = "<option value=unflag>UnFlag User";
		}




		if(!$mods->isAdmin($userData['userid'], "showemail"))
			unset($selectable['email']);

		if(!$mods->isAdmin($userData['userid'], "editinvoice"))
			unset($selectable['flagged']);
	
		$cols = count($selectable);
		if(count($actions))                                       $cols++;
		if($mods->isAdmin($userData['userid'],"editpictures"))    $cols++;
		if($mods->isAdmin($userData['userid'],"editprofile"))     $cols++;
		if($mods->isAdmin($userData['userid'],"editpreferences")) $cols++;
		if($mods->isAdmin($userData['userid'],"loginlog"))        $cols++;
		if($mods->isAdmin($userData['userid'],"showip"))          $cols++;
		if($mods->isAdmin($userData['userid'],"abuselog"))        $cols++;
	
		echo "<form action=$_SERVER[PHP_SELF] name=users method=post>";
		echo "<table border=0 cellspacing=1 cellpadding=2 align=center>";

		if($mods->isAdmin($userData['userid'],"showip"))
			echo "<tr><td class=body colspan=$cols align=center>IP: " . long2ip($ip) . ", Hostname: " . gethostbyaddr(long2ip($ip)) . "</td></tr>";
	
		echo "<tr>";
		if(count($actions))
			echo "<td class=header width=22>&nbsp;</td>";
		if($mods->isAdmin($userData['userid'],"editpictures"))
			echo "<td class=header>&nbsp;</td>";
		if($mods->isAdmin($userData['userid'],"editprofile"))
			echo "<td class=header>&nbsp;</td>";
		if($mods->isAdmin($userData['userid'],"editpreferences"))
			echo "<td class=header>&nbsp;</td>";
		if($mods->isAdmin($userData['userid'],"loginlog"))
			echo "<td class=header>&nbsp;</td>";
		if($mods->isAdmin($userData['userid'],"showip"))
			echo "<td class=header>&nbsp;</td>";
		if($mods->isAdmin($userData['userid'],"abuselog"))
			echo "<td class=header>&nbsp;</td>";

		$varlist = array();

		$varlist['search']=$search;
		$varlist['type']=$type;
		$varlist['iptype']=$iptype;
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
			if(count($actions))
				echo "<td class=header>" . ($line['exists'] ? "<input type=checkbox name=checkID[] value=$line[userid]" . (in_array($line['userid'], $checkID) ? " checked" : "" ) . ">" : '' ) . "</td>";
			if($mods->isAdmin($userData['userid'],"editpictures"))
				echo "<td class=header>" . ($line['exists'] ? "<a class=header href=/managepicture.php?uid=$line[userid]>Pics</a>" : '' ) . "</td>";
			if($mods->isAdmin($userData['userid'],"editprofile"))
				echo "<td class=header>" . ($line['exists'] ? "<a class=header href=/manageprofile.php?uid=$line[userid]>Profile</a>" : '' ) . "</td>";
			if($mods->isAdmin($userData['userid'],"editpreferences"))
				echo "<td class=header>" . ($line['exists'] ? "<a class=header href=/prefs.php?uid=$line[userid]>Prefs</a>" : '' ) . "</td>";
			if($mods->isAdmin($userData['userid'],"loginlog"))
				echo "<td class=header><a class=header href=/adminloginlog.php?col=user&val=$line[userid]&k=" . makeKey($line['userid']) . ">Logins</a></td>";
			if($mods->isAdmin($userData['userid'],"showip"))
				echo "<td class=header><a class=header href=/adminuserips.php?uid=$line[userid]&type=userid&k=" . makeKey($line['userid']) . ">IPs</a></td>";
			if($mods->isAdmin($userData['userid'],"abuselog"))
				echo "<td class=header><a class=header href=/adminabuselog.php?uid=$line[username]>Abuse: $line[abuses]</a></td>";

			$select = array_keys($selectable);
			foreach($select as $n){
				echo "<td class=$classes[$i] nowrap>";
				switch($n){
					case 'userid':
					case 'age':
					case 'sex':
						echo $line[$n];
						break;
					case 'email':
						echo implode('<br>', $line[$n]);
						break;
					case 'username':
						echo "<a class=body href=/users/". urlencode($line['username']) .">$line[username]</a>";
						break;
					case 'hits':
						if($line['exists'])
							echo number_format($line[$n]);
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
						if($line['exists'])
							echo ($line[$n] > $time ? userdate("M j, y",$line[$n]) : "No");
						break;
					case 'loc':
						if($line['exists'])
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
							case 'delself':
								echo "Deleted Self";
								break;
							case 'deladmin':
								echo "Deleted Admin";
								break;
						}
						break;
					case 'flagged':
						if($line['exists'])
							echo (isset($line['flagged']) ? '<b>Flag</b>' : '');
						break;

					case 'diffip':
						if($line['exists'] && $line['diffip'])
							echo "Yes";
						break;
					
					default:
						echo "Error: $n, $line[$n]";
						break;
				}
				echo "</td>";
			}
			echo "</tr>\n";
		}
		echo "<tr><td class=header colspan=$cols>";

		echo "<table width=100% cellspacing=0 cellpadding=0><tr><td class=header align=left>";

		if(count($actions)){
			foreach($varlist as $n => $v)
				echo "<input type=hidden name='$n' value='$v'>";
			echo "<input class=body type=checkbox onClick=\"this.value=check(this.form,'check')\">";
			echo "<select class=body name=action>";
				echo "<option value=''>Choose an Action";
			echo implode("", $actions); //defined above to know if we need to display the checkbox
			echo "</select>";

			echo "<select class=body name=reason><option value=0>Reason" . make_select_list_key($abuselog->reasons, $reason) . "</select>";
			echo "<select class=body name=freezetime><option value=0>Freeze Length" . make_select_list_key($freezetimes, $freezetime) . "</select>";

			echo "<input class=body type=text name=input size=50>";
			echo "<input class=body type=submit value=Go onClick=\"if(document.users.reason.selectedIndex==0 || document.users.input.value==''){alert('You must specify a reason.'); return false;}\">";
		}

		echo "</td><td class=header align=right>";

		$list = array();
		foreach($varlist as $n => $v)
			$list[] = "$n=" . urlencode($v);
		echo "Page: " . pageList("$_SERVER[PHP_SELF]?" . implode("&",$list),$page,$numpages,'header');

		echo "</td></tr></table>";

		echo "</td></tr>";
		echo "</table>";
		echo "</form>";
	}

	incFooter();

