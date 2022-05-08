<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"listdeletedusers"))
		die("Permission denied");

	$selectlist = array('userid' => 'Userid', 'username' => 'Username', 'email' => 'Email');

	$mods->adminlog('list deletedusers',"list deleted users list");


	$res = $db->prepare_query("SELECT * FROM deletedusers ORDER BY time DESC LIMIT 100");
	$rows = $res->fetchrowset();

	incHeader();

	echo "<table align=center>";

	if(count($rows)){
		echo "<tr>";
		echo "<td class=header>Userid</td>";
		echo "<td class=header>Username</td>";
		echo "<td class=header>Time</td>";
		echo "<td class=header>Reason</td>";
		echo "<td class=header>Deleted by</td>";
		echo "<td class=header>Abuse</td>";
		echo "<td class=header>IPs</td>";
		if($mods->isAdmin($userData['userid'],"loginlog"))
			echo "<td class=header>Logins</td>";
		echo "</tr>";

		$usernames = array();
		foreach($rows as $line){
			echo "<tr>";
			echo "<td class=body align=right>$line[userid]</td>";
			echo "<td class=body><a class=body href='mailto:$line[email]'>$line[username]</a></td>";
			echo "<td class=body nowrap>" . userDate("F j, Y, g:i a", $line['time']) . "</td>";
			echo "<td class=body>$line[reason]</td>";
			echo "<td class=body>";
			if($line['deleteid']==0){
				echo "Automatically";
			}elseif($line['deleteid']==$line['userid']){
				echo $line['username'];
			}else{
				if(!isset($usernames[$line['deleteid']]))
					$usernames[$line['deleteid']] = getUserName($line['deleteid']);
				echo "<a class=body href=/users/". urlencode($usernames[$line["deleteid"]]) .">" . $usernames[$line['deleteid']] . "</a>";
			}
			echo "</td>";
			echo "<td class=body><a class=body href=/adminabuselog.php?uid=$line[userid]>Abuse</a></td>";
			echo "<td class=body><a class=body href=/adminuserips.php?uid=$line[userid]&type=userid&k=" . makeKey($line['userid']) . ">IPs</a></td>";
			if($mods->isAdmin($userData['userid'],"loginlog"))
				echo "<td class=body><a class=body href=/adminloginlog.php?col=userid&val=$line[userid]>Logins</a></td>";
			echo "</tr>";
		}
	}

	echo "</table>";

	incFooter();

