<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isAdmin($userData['userid'],"editmods"))
		die("Permission denied");


	if($action == "Add Pic Mods"){

		$users = preg_split("/[\s]+/", $usernames);

		$uids = array();

		foreach($users as $user){
			$uid = getUserid($user);

			if(!$uid){
				$msgs->addMsg("Usename does not exist");
				continue;
			}

			if($mods->addMod($uid, MOD_PICS, 0)){
				$mods->adminlog("add mod", "Add $user as level 0 pic mod");
				$uids[] = $uid;
				$msgs->addMsg("$user added");
			}
		}

		if(count($uids)){
			$messaging->deliverMsg($uids, $subject, $msg, 0, false, false, false);

			$forums->invite($uids, 203); //mod chat
			$forums->invite($uids, 139); //pic mod
		}


		incHeader();

		echo "Pic mods added, messaged and invited";

		incFooter();
		exit;
	}


	incHeader();

	echo "<table><form action=$_SERVER[PHP_SELF] method=post name=editbox>";
	echo "<tr><td class=header colspan=2 align=center>Add Pic Mods</td></tr>";

	echo "<tr><td class=body>Users to add:<br><textarea class=body name=usernames cols=50 rows=10></textarea></td></tr>";

	echo "<tr><td class=body colspan=2>&nbsp;</td></tr>";

	echo "<tr><td class=header colspan=2 align=center>Message to send</td></tr>";

	echo "<tr><td class=body>Subject: <input class=body type=text name=subject size=50></td></tr>";
	echo "<tr><td class=body>";

	editBox("",true);

	echo "</td></tr>";
	echo "<tr><td class=body colspan=2 align=center><input class=body type=submit name=action value='Add Pic Mods'></td></tr>";
	echo "</form></table>";

	incFooter();

