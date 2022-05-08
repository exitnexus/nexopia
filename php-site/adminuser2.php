<?

die("error");
//need to use $_POST instead of globals

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isAdmin($userData['userid'],"listusers") && $userData['userid'] == 1)
		die("Permission denied");

	$selectable=array(	'users.username'=> 'Username',
						'users.userid'	=> 'Userid',
						'jointime'		=> 'Join Time',
						'activetime'	=> 'Active Time',
						'ip'			=> 'IP address',
						'hostname'		=> 'Host Name',
						'activated'		=> 'Activated',
						'hits'			=> 'Number of Hits',
						'loc'			=> 'Location',
						'email'			=> 'Email Address');

	$dbselectable=array('users.username'=> 'Username',
						'users.userid'	=> 'Userid',
						'jointime'		=> 'Join Time',
						'activetime'	=> 'Active Time',
						'ip'			=> 'IP address',
						'activated'		=> 'Activated',
						'hits'			=> 'Number of Hits',
						'loc'			=> 'Location',
						'email'			=> 'Email Address');


	for($i=1;$i<=12;$i++)
		$months[$i] = date("F", mktime(0,0,0,$i,1,0));

	$locations = & new category( $db, "locs");


	switch($action){
		case "delete":
			if(!$mods->isAdmin($userData['userid'],"deleteusers"))
				break;

			if(isset($checkID))
				foreach($checkID as $check){
					$mods->adminlog('delete user',"Delete user $check");
					if(deleteAccount($check,$input))
						$msgs->addMsg("$check Account deleted");
					else
						$msgs->addMsg("Could not delete account $check");
				}
			break;
		case "activate":
			if(!$mods->isAdmin($userData['userid'],"activateusers"))
				break;

			if(isset($checkID))
				foreach($checkID as $check){
					activateAccount($check);
					$mods->adminlog('activate user',"Activate user $check");
					$msgs->addMsg("$check activated");
				}
			break;
		case "deactivate":
			if(!$mods->isAdmin($userData['userid'],"activateusers"))
				break;

			if(isset($checkID))
				foreach($checkID as $check){
					deactivateAccount($check);
					$mods->adminlog('deactivate user',"Deactivate user $check");
					$msgs->addMsg("$check deactivated");
				}
			break;
		case "banemail":
			if(!$mods->isAdmin($userData['userid'],"banusers"))
				break;

			if(isset($checkID)){
				$db->prepare_query("INSERT INTO bannedusers (email, type) SELECT email, 'email' FROM users WHERE userid IN (?)", $checkID);
				foreach($checkID as $check){
					$mods->adminlog('ban email',"Ban email from user $check");
					$msgs->addMsg("email banned from user $check");
				}
			}
			break;
		case "banip":
			if(!$mods->isAdmin($userData['userid'],"banusers"))
				break;

			if(isset($checkID)){
				$db->prepare_query("INSERT INTO bannedusers (ip, type) SELECT DISTINCT ip, 'ip' FROM users WHERE ip != 0 && userid IN (?)", $checkID);
				foreach($checkID as $check){
					$db->query($query);
					$mods->adminlog('ban ip',"Ban ip from user $check");
					$msgs->addMsg("IP banned");
				}
			}
			break;
	}

	if(isset($search)){
		isValidSortd($sortd,'ASC');
		isValidSortt($selectable,$sortt);

		$selects = array();

		if(!isset($select))
			$select = array('username');


		foreach($select as $k => $v)
			if(in_array($v,array_flip($dbselectable)))
				$selects[] = $select[$k];

		$commands = array();

		if(isset($where['username']) && $where['username']!=''){
			if(isset($comp['username']) && $comp['username']=='LIKE')
				$commands[] = $db->prepare("username LIKE ?", "%$where[username]%");
			else
				$commands[] = $db->prepare("username = ?",$where['username']);
		}
		if(isset($where['userid']) && $where['userid']!='')
			$commands[] = "users.userid='$where[userid]'";
		if(isset($where['joinday']) && $where['joinday']!='' && isset($where['joinmonth']) && $where['joinmonth']!='' && isset($where['joinyear']) && $where['joinyear']!=''){
			if(isset($comp['jointime']) && in_array($comp['jointime'],array('>','<')))
				$op = $comp['jointime'];
			else
				$op = '<';
			$jointime = mktime(0,0,0,$where['joinmonth'],$where['joinday'],$where['joinyear']);
			$commands[] = $db->prepare("jointime $op ?", $jointime);
		}
		if(isset($where['activeday']) && $where['activeday']!='' && isset($where['activemonth']) && $where['activemonth']!='' && isset($where['activeyear']) && $where['activeyear']!=''){
			if(isset($comp['activetime']) && in_array($comp['activetime'],array('>','<')))
				$op = $comp['activetime'];
			else
				$op = '<';
			$activetime = mktime(0,0,0,$where['activemonth'],$where['activeday'],$where['activeyear']);
			$commands[] = $db->prepare("activetime $op ?", $activetime);
		}
		if(isset($where['ip']) && $where['ip']!=''){
			$ip = ip2int($where['ip']);
			$commands[] = $db->prepare("ip = ?", $ip);
		}
		if(isset($where['activated']) && $where['activated']!=''){
			if($where['activated']=='y')
				$activated='y';
			else
				$activated='n';
			$commands[] = $db->prepare("activated = ?", $activated);
		}
		if(isset($where['hits']) && $where['hits']!=''){
			if(isset($comp['hits']) && in_array($comp['hits'],array('=','>','<')))
				$op = $comp['hits'];
			else
				$op = '=';
			$commands[] = $db->prepare("hits $op ?", $where['hits']);
		}
		if(isset($where['loc']) && $where['loc']!=''){
			$locbranch = $locations->makeBranch($where['loc']);

			$locs=array();
			$locs[] = $where['loc'];
			foreach($locbranch as $loc)
				$locs[] = $loc['id'];
			$commands[] = $db->prepare("loc IN (?)", $locs);
		}
		if(isset($where['email']) && $where['email']!=''){
			if(isset($comp['email']) && $comp['email']=='LIKE')
				$commands[] = $db->prepare("email LIKE ?", "%$where[email]%");
			else
				$commands[] = $db->prepare("email = ?", $where['email']);

		}

		$page = getREQval('page', 'int');

		$query = "SELECT SQL_CALC_FOUND_ROWS users.userid," . implode(', ',$selects) . " FROM users";
		if(count($commands))
			$query .= " WHERE " . implode(' && ', $commands);
		$query .= " ORDER BY $sortt $sortd, username DESC LIMIT " . ($page*$config['linesPerPage']) . ", $config[linesPerPage]";
		$result = $db->query($query);

		$db->query("SELECT FOUND_ROWS()");
		$numrows = $db->fetchfield();
		$numpages =  ceil($numrows / $config['linesPerPage']);

	}

	incHeader();


	if(isset($search)){
		echo "<table border=1 cellspacing=0 cellpadding=2><form action=$_SERVER[PHP_SELF] name=users>";
		echo "<tr><td class=header width=22>&nbsp;</td>";
		echo "<td class=header>&nbsp;</td>";
		echo "<td class=header>&nbsp;</td>";
		echo "<td class=header>&nbsp;</td>";
		$varlist = array();

		$varlist['search']='Go';
		foreach($select as $n => $v)
			$varlist["select[$n]"] = $v;
		if(isset($comp))
			foreach($comp as $n => $v)
				$varlist["comp[$n]"] = $v;
		if(isset($where))
			foreach($where as $n => $v)
				$varlist["where[$n]"] = $v;

		foreach($select as $item){
			makeSortTableHeader('',$selectable[$item],$item,$varlist);
		}
		echo "</tr>";

		$hosts = array();
		while($line = $db->fetchrow($result)){
			echo "<tr>";
			echo "<td class=header><input type=checkbox name=checkID[] value=$line[userid]></td>";
			echo "<td class=header><a class=header href=managepicture.php?uid=$line[userid]>Pics</a></td>";
			echo "<td class=header><a class=header href=manageprofile.php?uid=$line[userid]>Profile</a></td>";
			echo "<td class=header><a class=header href=prefs.php?uid=$line[userid]>Prefs</a></td>";
			foreach($select as $n){
				$pos = strpos($n,'.');
				if($pos)
					$n = substr($n,$pos+1);
				echo "<td class=body nowrap>";
				if($n=='userid' || $n=='hits' || $n=='email'){
					echo $line[$n];
				}elseif($n=='username'){
					echo "<a class=body href=profile.php?uid=$line[userid]>$line[username]</a>";
				}elseif($n=='jointime' || $n=='activetime'){
					echo userdate("D M j, Y G:i:s",$line[$n]);
				}elseif($n=='loc'){
					echo $locations->getCatName($line['loc']);
				}elseif($n=='ip'){
					echo long2ip($line['ip']);
				}elseif($n=='hostname'){
					if(!isset($hosts[$line['ip']]))
						$hosts[$line['ip']] = gethostbyaddr(long2ip($line['ip']));
					echo $hosts[$line['ip']];
				}elseif($n=='activated'){
					if($line['activated']=='y')
						echo "Yes";
					else
						echo "No";
				}else{
					echo "Error: $n, $line[$n]";
				}

				echo "</td>";
			}
			echo "</tr>";
		}
		echo "<tr><td class=header colspan=" . (count($select)+4) . ">";

		echo "<table width=100%><tr><td class=header align=left>";

		foreach($varlist as $n => $v)
			echo "<input type=hidden name='$n' value='$v'>";
		echo "<input type=hidden name='sortt' value='$sortt'>";
		echo "<input type=hidden name='sortd' value='$sortd'>";
		echo "<select class=body name=action>";
			echo "<option value=''>Choose an Action";
			echo "<option value=delete>Delete User";
			echo "<option value=activate>Activate Account";
			echo "<option value=deactivate>Deactivate Account";
			echo "<option value=banip>Ban Ip";
			echo "<option value=banemail>Ban Email";
		echo "</select>";
		echo "<input class=body type=text name=input>";
		echo "<input class=body type=submit value=Go onClick=\"if(document.users.action.selectedIndex==1 && document.users.input.value==''){alert('You must specify a reason for deleting the users'); return false;}\">";

		echo "</td><td class=header align=right>";

		echo "Page:";
		$start = $page+1>$config['pagesInList'] ? $page-$config['pagesInList'] : 1;
		$finish = $page+$config['pagesInList']<$numpages ? $page+$config['pagesInList']-1 : $numpages;
		echo "<select class=body onChange=\"location.href='$_SERVER[PHP_SELF]?sortt=$sortt&sortd=$sortd&";
		foreach($varlist as $n => $v)
			echo "$n=$v&";
		echo "page='+(this.selectedIndex+$start-1)\">" . make_select_list(range($start,$finish),$page+1) . "</select>";

		echo "</td></tr></table>";

		echo "</td></tr>";
		echo "</form></table>";
	}

	if(!isset($select))
		$select = array('username');
	if(!isset($comp))
		$comp = array('username'=>'','jointime'=>'','activetime'=>'','hits'=>'','email'=>'');
	if(!isset($where))
		$where = array('username'=>'','userid'=>'','joinmonth'=>'','joinday'=>'','joinyear'=>'','activemonth'=>'','activeday'=>'','activeyear'=>'','ip'=>'','activated'=>'','hits'=>'','loc'=>'','email'=>'');

	echo "<table><form action=$_SERVER[PHP_SELF]>";
	echo "<tr><td class=header>Select</td><td class=header>Where</td></tr>";
	echo "<tr>";

	echo "<td class=body valign=top>";
		echo "<select class=body name=select[] size=" .count($selectable) . " multiple=multiple>";
		foreach($selectable as $k => $v){
			echo "<option value='$k'";
			if(in_array($k,$select))
				echo " selected";
			echo ">$v";
		}
		echo "</select>";
	echo "</td>";
	echo "<td class=body>";
		echo "<table>";
		echo "<tr><td class=header>Username:</td>";
			echo "<td class=header><select class=body name=comp[username]>" . make_select_list_key(array('='=>'=','LIKE'=>'contains'),$comp['username']) . "</select></td>";
			echo "<td class=body><input type=text name=where[username] value='$where[username]'></td></tr>";
		echo "<tr><td class=header>Userid:</td>";
			echo "<td class=header align=center> = </td>";
			echo "<td class=body><input type=text name=where[userid] value='$where[userid]' size=5></td></tr>";
		echo "<tr><td class=header>Join Time:</td>";
			echo "<td class=header><select class=body name=comp[jointime]>" . make_select_list_key(array('<'=>'before','>'=>'after'),$comp['jointime']) . "</select></td>";
			echo "<td class=body>";
				echo "<select class=body name=\"where[joinmonth]\"><option value=''>Month" . make_select_list_key($months, $where['joinmonth']) . "</select>";
				echo "<select class=body name=\"where[joinday]\"><option value=''>Day" . make_select_list(range(1,31),$where['joinday']) . "</select>";
				echo "<select class=body name=\"where[joinyear]\"><option value=''>Year" . make_select_list(range(2003,userdate("Y")),$where['joinyear']) . "</select>";
			echo "</td></tr>";
		echo "<tr><td class=header>Active time:</td>";
			echo "<td class=header><select class=body name=comp[activetime]>" . make_select_list_key(array('<'=>'before','>'=>'after'),$comp['activetime']) . "</select></td>";
			echo "<td class=body>";
				echo "<select class=body name=\"where[activemonth]\"><option value=''>Month" . make_select_list_key($months,$where['activemonth']) . "</select>";
				echo "<select class=body name=\"where[activeday]\"><option value=''>Day" . make_select_list(range(1,31),$where['activeday']) . "</select>";
				echo "<select class=body name=\"where[activeyear]\"><option value=''>Year" . make_select_list(range(2003,userdate("Y")),$where['activeyear']) . "</select>";
			echo "</td></tr>";
		echo "<tr><td class=header>IP address:</td>";
			echo "<td class=header align=center> = </td>";
			echo "<td class=body><input type=text name=where[ip] value='$where[ip]'></td></tr>";
		echo "<tr><td class=header>Activated:</td>";
			echo "<td class=header align=center> = </td>";
			echo "<td class=body><select class=body name=where[activated]>" . make_select_list_key(array(''=>' ','y'=>'Yes','n'=>'No'),$where['activated']) . "</select></td></tr>";
		echo "<tr><td class=header>Number of Hits:</td>";
			echo "<td class=header><select class=body name=comp[hits]>" . make_select_list_key(array('='=>'=','>'=>'>','<'=>'<'),$comp['hits']) . "</select></td>";
			echo "<td class=body><input type=text name=where[hits] size=5 value='$where[hits]'></td></tr>";
		echo "<tr><td class=header>Location:</td>";
			echo "<td class=header align=center> = </td>";
			echo "<td class=body><select class=body name=where[loc]><option value=''>"  . makeCatSelect($locations->makeBranch(),$where['loc']) . "</select></td></tr>";
		echo "<tr><td class=header>Email:</td>";
			echo "<td class=header><select class=body name=comp[email]>" . make_select_list_key(array('='=>'=','LIKE'=>'contains'),$comp['email']) . "</select></td>";
			echo "<td class=body><input type=text name=where[email] value='$where[email]'></td></tr>";
		echo "</table>";
	echo "</td></tr>";
	echo "<tr><td colspan=3 class=header align=center><input class=body type=submit name=search value=Search></td></tr>";
	echo "</form></table>";


	incFooter(array('incAdminBlock'));

