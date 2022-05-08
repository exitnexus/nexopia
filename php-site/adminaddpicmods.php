<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isAdmin($userData['userid'],"editmods"))
		die("Permission denied");

	if($action == "Add Pic Mods" || $action == "Remove Pic Mods"){
	
		$add = ($action == "Add Pic Mods");
	
		$usernames = getPOSTval('usernames');
		$users = preg_split("/[\s]+/", $usernames);

		$uids = array();

		foreach($users as $user){
			$uid = getUserid($user);

			if(!$uid){
				$msgs->addMsg("Usename ". htmlentities($user) . " does not exist");
				continue;
			}

			if($add && $mods->addMod($uid, MOD_PICS, 0)){
				$mods->adminlog("add mod", "Add $user as level 0 pic mod");
				$abuselog->addAbuse($uid, ABUSE_ACTION_NOTE, ABUSE_REASON_OTHER, 'Added as pic mod', 'User given pic mod privileges.');
				$uids[] = $uid;
				$msgs->addMsg(htmlentities($user) . " added");
			}
			if(!$add){
				$mods->deleteMod($uid, MOD_PICS);
				$mods->adminlog("remove mod", "Remove $user as a pic mod");
				$abuselog->addAbuse($uid, ABUSE_ACTION_NOTE, ABUSE_REASON_OTHER, 'Removed as pic mod', 'User removed as a pic mod.');
				$uids[] = $uid;
				$msgs->addMsg(htmlentities($user) . " removed");
			}
		}

		if(count($uids)){
			$subject = getPOSTval('subject');
			$msg = getPOSTval('msg');
			$messaging->deliverMsg($uids, $subject, $msg, 0, false, false, false);

			if($add){
				$forums->invite($uids, 203); //mod chat
				$forums->invite($uids, 139); //pic mod
			}else{
				$forums->unInvite($uids, 203); //mod chat
				$forums->unInvite($uids, 139); //pic mod			
			}
		}


		incHeader();

		if($add)
			echo "Pic mods added, messaged and invited";
		else
			echo "Pic mods removed, messaged and uninvited";

		incFooter();
		exit;
	}

	$preload = getREQval('preload');

	incHeader();

	echo "<table align=center><form action=$_SERVER[PHP_SELF] method=post name=editbox>";
	echo "<tr><td class=header colspan=2 align=center>Add/Remove Pic Mods</td></tr>";

	echo "<tr><td class=body>Users to add/remove:<br><textarea class=body name=usernames cols=80 rows=10>";
	echo htmlentities($preload);
	echo "</textarea></td></tr>";

	echo "<tr><td class=body colspan=2>&nbsp;</td></tr>";

	echo "<tr><td class=header colspan=2 align=center>Message to send</td></tr>";

	echo "<tr><td class=body>Subject: <input class=body type=text name=subject size=50></td></tr>";
	echo "<tr><td class=body>";

	editBox("");

	echo "</td></tr>";
	echo "<tr><td class=body colspan=2 align=center>";
	echo "<input class=body type=submit name=action value='Add Pic Mods'>";
	echo "<input class=body type=submit name=action value='Remove Pic Mods'>";
	echo "</td></tr>";
	echo "</form></table>";

	incFooter();

