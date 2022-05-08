<?
	$enableCompression = false;

	$login=1;

	require_once("include/general.lib.php");
	require_once("include/backup.php");

	if(!$mods->isAdmin($userData['userid'])){
		header("location: /");
		exit;
	}

	switch($action){
		case "Set Top 10":
			setTopLists();
			$mods->adminlog('settoplists',"Set Top lists");
			break;
		case "Fix users gallery":
			fixUserGallery(getUserId($uid));
			$mods->adminlog('fix comments',"Fix comments for $uid");
			break;
		case "Give Plus":
			if($mods->isadmin($userData['userid'],'listinvoices')){
				$to = getUserID($to);
				if(empty($to))
					break;
				addPremium($to,$duration);
				$mods->adminlog('add plus',"Add Plus to $to for $duration months");
			}
			break;
		case "Transfer Plus":
			if($mods->isadmin($userData['userid'],'listinvoices')){
				$from = getUserID($from);
				$to = getUserID($to);
				if(empty($from) || empty($to))
					break;
				transferPremium($from, $to);
				$mods->adminlog('transfer plus',"Transfer Plus from $from to $to");
			}
			break;
		case "Transfer Mod":
			if($mods->isadmin($userData['userid'],'editmods')){
				$from = getUserID($from);
				$to = getUserID($to);
				if(empty($from) || empty($to))
					break;
				$mods->moveMod($from, $to);
				$mods->adminlog('transfer mod',"Transfer mod powers from $from to $to");
			}
			break;
	}



	incHeader();

	echo "<table><form action=$PHP_SELF>";
	echo "<tr><td class=header>Funcs</td><td class=header>Description</td></tr>";
	echo "<tr><td class=body><input class=body type=submit name=action value='Set Top 10'></td></tr>";
	echo "<tr><td class=body><input class=body type=text name=uid size=10></td></tr>";
	echo "<tr><td class=body><input class=body type=submit name=action value='Fix users gallery'></td></tr>";
	echo "</form>";
	if($mods->isadmin($userData['userid'],'listinvoices')){
		echo "<form action=$PHP_SELF>";
		echo "<tr><td class=header colspan=2 align=center>Transfer Plus</td></tr>";
		echo "<tr><td class=body colspan=2>From <input class=body type=text name=from size=10> to <input class=body type=text name=to size=10></td></tr>";
		echo "<tr><td class=body><input class=body type=submit name=action value='Transfer Plus'></td></tr>";
		echo "</form>";

		echo "<form action=$PHP_SELF>";
		echo "<tr><td class=header colspan=2 align=center>Add Plus</td></tr>";
		echo "<tr><td class=body colspan=2>To <input class=body type=text name=to size=10> for <input class=body type=text name=duration size=3> months</td></tr>";
		echo "<tr><td class=body><input class=body type=submit name=action value='Give Plus'></td></tr>";
		echo "</form>";
	}
	if($mods->isadmin($userData['userid'],'editmods')){
		echo "<form action=$PHP_SELF>";
		echo "<tr><td class=header colspan=2 align=center>Transfer Mod</td></tr>";
		echo "<tr><td class=body colspan=2>From <input class=body type=text name=from size=10> to <input class=body type=text name=to size=10></td></tr>";
		echo "<tr><td class=body><input class=body type=submit name=action value='Transfer Mod'></td></tr>";
		echo "</form>";
	}
	echo "</table>";

	incFooter();




