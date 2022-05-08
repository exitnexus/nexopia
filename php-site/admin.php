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
/*		case "Set Top 10":
			setTopLists();
			$mods->adminlog('settoplists',"Set Top lists");
			break;
		case "Fix users gallery":
			fixUserGallery(getUserId($uid));
			$mods->adminlog('fix gallery',"Fix gallery for $uid");
			break;
*/
		case "Give Plus":
			if($mods->isAdmin($userData['userid'],'listinvoices')){
				$to = getUserID($_POST['to']);
				if(empty($to) || empty($_POST['duration']))
					break;
				addPremium($to, $_POST['duration']);
				$mods->adminlog('add plus',"Add Plus to $to for $_POST[duration] months");
			}
			break;
		case "Transfer Plus":
			if($mods->isAdmin($userData['userid'],'listinvoices')){
				$from = getUserID($_POST['from']);
				$to = getUserID($_POST['to']);
				if(empty($from) || empty($to))
					break;
				transferPremium($from, $to);
				$mods->adminlog('transfer plus',"Transfer Plus from $from to $to");
			}
			break;
		case "Fix Plus":
			if($mods->isAdmin($userData['userid'],'listinvoices')){
				$uid = getUserID($_POST['uid']);
				if(empty($uid))
					break;
				fixPremium($uid);
				$mods->adminlog('fix plus',"Fix Plus for $uid");
			}
			break;
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

				$db->prepare_query("SELECT friendid FROM friends WHERE userid = ?", $uid);

				$friendids = array();
				while($line = $db->fetchrow())
					$friendids[$line['friendid']] = $line['friendid'];

				$db->prepare_query("SELECT userid FROM friends WHERE userid IN (?) && friendid = ?", $friendids, $uid);

				while($line = $db->fetchrow())
					unset($friendids[$line['userid']]);

				$db->prepare_query("DELETE FROM friends WHERE userid = ? && friendid IN (?)", $uid, $friendids);
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

	if($mods->isadmin($userData['userid'],'listinvoices')){
		echo "<form action=$_SERVER[PHP_SELF] method=post>";
		echo "<tr><td class=header colspan=2 align=center>Transfer Plus</td></tr>";
		echo "<tr><td class=body colspan=2>From <input class=body type=text name=from size=10> to <input class=body type=text name=to size=10></td></tr>";
		echo "<tr><td class=body><input class=body type=submit name=action value='Transfer Plus'></td></tr>";
		echo "</form>";

		echo "<form action=$_SERVER[PHP_SELF] method=post>";
		echo "<tr><td class=header colspan=2 align=center>Add Plus</td></tr>";
		echo "<tr><td class=body colspan=2>To <input class=body type=text name=to size=10> for <input class=body type=text name=duration size=3> months</td></tr>";
		echo "<tr><td class=body><input class=body type=submit name=action value='Give Plus'></td></tr>";
		echo "</form>";

		echo "<form action=$_SERVER[PHP_SELF] method=post>";
		echo "<tr><td class=header colspan=2 align=center>Fix Plus</td></tr>";
		echo "<tr><td class=body colspan=2>Fix for <input class=body type=text name=uid size=10></td></tr>";
		echo "<tr><td class=body><input class=body type=submit name=action value='Fix Plus'></td></tr>";
		echo "</form>";
	}
	if($mods->isadmin($userData['userid'],'editmods')){
		echo "<form action=$_SERVER[PHP_SELF] method=post>";
		echo "<tr><td class=header colspan=2 align=center>Transfer Mod</td></tr>";
		echo "<tr><td class=body colspan=2>From <input class=body type=text name=from size=10> to <input class=body type=text name=to size=10></td></tr>";
		echo "<tr><td class=body><input class=body type=submit name=action value='Transfer Mod'></td></tr>";
		echo "</form>";
	}
	echo "</table>";

	incFooter();




