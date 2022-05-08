<?
	$enableCompression = false;

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isAdmin($userData['userid'])){
		header("location: /");
		exit;
	}

	switch($action){
/*		case "Set Top 10":
			setTopLists();
			$mods->adminlog('settoplists',"Set Top lists");
			break;
		case "Fix users gallery":
			fixUserGallery(getUserId($uid));
			$mods->adminlog('fix gallery',"Fix gallery for $uid");
			break;
*/

		case "Transfer Mod":
			if($mods->isAdmin($userData['userid'],'editmods')){
				$from = getUserID($_POST['from']);
				$to = getUserID($_POST['to']);
				if(empty($from) || empty($to))
					break;
				$mods->moveMod($from, $to);
				$mods->adminlog('transfer mod',"Transfer mod powers from $from to $to");
			}
			break;

		case "Remove Friends":
			if($mods->isAdmin($userData['userid'],'listusers')){
				$uid = getUserId($_POST['uid']);

				if(!$uid)
					break;

				$friends = getMutualFriendsList($uid);

				$delids = array();
				foreach($friends as $friendid => $mutual){
					if(!$mutual){
						$delids[] = $friendid;

						$cache->remove("friendids" . USER_FRIENDOF . "-$friendid");
					}
				}

				$usersdb->prepare_query("DELETE FROM friends WHERE userid = % && friendid IN (#)", $uid, $delids);

				$cache->remove("friendids" . USER_FRIENDS . "-$uid");
				$cache->remove("friendsonline-$uid");

			}
			break;

	}



	incHeader();

	echo "<table>";
/*	echo "<form action=$_SERVER[PHP_SELF]>";
	echo "<tr><td class=header>Funcs</td><td class=header>Description</td></tr>";
	echo "<tr><td class=body><input class=body type=submit name=action value='Set Top 10'></td></tr>";
	echo "<tr><td class=body><input class=body type=text name=uid size=10></td></tr>";
	echo "<tr><td class=body><input class=body type=submit name=action value='Fix users gallery'></td></tr>";
	echo "</form>";
*/
	echo "<form action=$_SERVER[PHP_SELF] method=post>";
	echo "<tr><td class=header colspan=2 align=center>Remove non-mutual friends</td></tr>";
	echo "<tr><td class=body colspan=2>Remove from <input class=body type=text name=uid size=10></td></tr>";
	echo "<tr><td class=body><input class=body type=submit name=action value='Remove Friends'></td></tr>";
	echo "</form>";

	if($mods->isadmin($userData['userid'],'editmods')){
		echo "<form action=$_SERVER[PHP_SELF] method=post>";
		echo "<tr><td class=header colspan=2 align=center>Transfer Mod</td></tr>";
		echo "<tr><td class=body colspan=2>From <input class=body type=text name=from size=10> to <input class=body type=text name=to size=10></td></tr>";
		echo "<tr><td class=body><input class=body type=submit name=action value='Transfer Mod'></td></tr>";
		echo "</form>";
	}
	echo "</table>";

	incFooter();




