<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"listdeletedusers"))
		die("Permission denied");

	if(empty($page)) $page=0;

	if(empty($uid))
		$uid="";

	$mods->adminlog('list deletedusers',"list deleted users: $uid");

	$query = "SELECT SQL_CALC_FOUND_ROWS * FROM deletedusers";
	if($uid!="")
		$query .= " WHERE " . $db->prepare("username = ?",$uid);
	$query .=" ORDER BY id DESC LIMIT " . $page*$config['linesPerPage'] . ", $config[linesPerPage]";
	$result = $db->query($query);


	$rowresult = $db->query("SELECT FOUND_ROWS()");
	$numrows = $db->fetchfield();
	$numpages =  ceil($numrows / $config['linesPerPage']);

	incHeader();

	echo "<table width=100%>";
	echo "<tr><td class=header>Userid</td><td class=header>Username</td><td class=header>Time</td><td class=header>Reason</td><td class=header>Deleted by</td></tr>";

	$usernames = array();
	while($line = $db->fetchrow($result)){
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
				$usernames[$line['deleteid']]=getUserName($line['deleteid']);
			echo $usernames[$line['deleteid']];
		}
		echo "</td>";
		echo "</tr>";
	}

	echo "<tr><td class=header colspan=5>";

	echo "<table width=100%><tr>";
	echo "<form action=$PHP_SELF>";
	echo "<td class=header>";
	echo "Username: <input class=body type=text name=uid value='$uid'><input class=body type=submit value=Go>";
	echo "</td>";
	echo "</form>";

	echo "<td class=header align=right>";
	echo "Page: " . pageList("$PHP_SELF",$page,$numpages,'header');
	echo "</td>";
	echo "</tr></table>";
	echo "</td></tr>";

	echo "</table>";

	incFooter();
