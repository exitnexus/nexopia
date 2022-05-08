<?

	$login=1;

	set_time_limit(300);

	require_once("include/general.lib.php");

	if(!$mods->isMod($userData['userid']))
		die("Permission denied");

	$mode = getREQval('mode', 'int');

	$id = getREQval('id', 'int');

	$vote = getREQval('vote');

	if(!empty($vote)){
//		echo "mode: $mode<br>";

		$checkID = getPOSTval('checkID', 'array');

		switch($mode){
			case MOD_PICS:
			case MOD_PICABUSE:
			case MOD_SIGNPICS:
			case MOD_FORUMRANK:
			case MOD_FORUMPOST:
			case MOD_FORUMBAN:
			case MOD_GALLERY:
			case MOD_GALLERYABUSE:
			case MOD_USERABUSE:
			case MOD_USERABUSE_CONFIRM:
//			case MOD_QUESTIONABLEPICS:
//		print_r($checkID);


				if(count($checkID))
					$mods->vote($checkID,$mode);
				break;

			case MOD_ARTICLE:
//			case MOD_BANNER:
			case MOD_POLL:

//				echo "vote: $vote, id: $id";

				if(count($checkID)){
					$mods->vote($checkID,$mode);
					break;
				}

				if($vote == "Accept") 	$vote = 'y';
				elseif($vote == "Deny")	$vote = 'n';
				else			break;

				$mods->vote(array($id => $vote),$mode);
				$id = 0;
				break;
		}
	}

	$moditemcounts = $mods->getModItemCounts();

	switch($mode){
		case MOD_PICS:				if(isset($moditemcounts[MOD_PICS]))				displayPic();				break;
		case MOD_PICABUSE:			if(isset($moditemcounts[MOD_PICABUSE]))			displayPicAbuse();			break;
		case MOD_SIGNPICS:			if(isset($moditemcounts[MOD_SIGNPICS]))			displaySignPics();			break;
		case MOD_QUESTIONABLEPICS:	if(isset($moditemcounts[MOD_QUESTIONABLEPICS]))	displayQuestionablePics();	break;
		case MOD_FORUMRANK:			if(isset($moditemcounts[MOD_FORUMRANK]))		displayForumRanks();		break;
		case MOD_FORUMPOST:			if(isset($moditemcounts[MOD_FORUMPOST]))		forumPostAbuse();			break;
		case MOD_FORUMBAN:			if(isset($moditemcounts[MOD_FORUMBAN]))			forumBans();				break;
		case MOD_GALLERY:			if(isset($moditemcounts[MOD_GALLERY]))			displayGallery();			break;
		case MOD_GALLERYABUSE:		if(isset($moditemcounts[MOD_GALLERYABUSE]))		displayGalleryAbuse();		break;
		case MOD_USERABUSE:			if(isset($moditemcounts[MOD_USERABUSE]))		displayUserAbuse(MOD_USERABUSE);			break;
		case MOD_USERABUSE_CONFIRM:	if(isset($moditemcounts[MOD_USERABUSE_CONFIRM]))displayUserAbuse(MOD_USERABUSE_CONFIRM);	break;
		case MOD_BANNER:			if(isset($moditemcounts[MOD_BANNER]))			displayBanners();			break;
		case MOD_ARTICLE:			if(isset($moditemcounts[MOD_ARTICLE]))			displayArticles($id);		break;
		case MOD_POLL:				if(isset($moditemcounts[MOD_POLL]))				displayPolls($id);			break;
	}
	displayTypes($moditemcounts); // exit

///////////////

function displayTypes($moditemcounts){
	global $mods;

	incHeader();

	$types = array();
	foreach($moditemcounts as $type => $num)
		if($num > 0)
			$types[$type] = $num;

	if(count($types)==0){
		echo "No requests";
	}else{
		echo "<table>";
		foreach($types as $type => $num)
			echo "<tr><td class=body><a class=body href=$_SERVER[PHP_SELF]?mode=$type>" . $mods->modtypes[$type] . "</a></td><td class=body>$num</td></tr>";
		echo "</table>";
	}

	incFooter();
	exit;
}

function displayPic(){
	global $config, $db, $userData, $mods;

	$lvl = $mods->getModLvl($userData['userid'], MOD_PICS);

	$prefs = $mods->getModPrefs($userData['userid'], MOD_PICS);

	$ids = $mods->getModItems(MOD_PICS, $prefs['picsperpage'], 3, 120);

	if(!count($ids))
		return;

	$rows = array();
	$db->prepare_query("SELECT picspending.id, users.userid, username, age, sex, description, time FROM users, picspending WHERE users.userid=picspending.itemid && picspending.id IN (?)", $ids);

	while($line = $db->fetchrow()){
		$rows[$line['id']] = $line;
		unset($ids[$line['id']]);
	}

	if(count($ids) > 0)
		$mods->deleteItem(MOD_PICS, $ids);

	if(count($rows) == 0)
		return;

	incHeader();

	if($prefs['autoscroll'] == 'y')
		echo "<script> function jump(i){ location.href='#pic' + i;} </script>\n";
	else
		echo "<script> function jump(i){ return true; } </script>\n";

	echo "<form action=$_SERVER[PHP_SELF] method=post>";
	echo "<input type=hidden name=action value=vote>";
	echo "<input type=hidden name=mode value=" . MOD_PICS . ">";

	echo "<table cellpadding=3>";

	$picloc = "http://www.nexopia.com" . $config['picdir'];

	$time = time();

	$i=1;
	foreach($rows as $line){
		if($line['sex']=='Female') 	$bgcolor = '#FFAAAA';
		else						$bgcolor = '#AAAAFF';

		echo "<tr><td class=body valign=top align=right style=\"background-color: $bgcolor\"><img src=$picloc" . floor($line['id']/1000) . "/$line[id].jpg></td>";
		echo "<td class=body valign=top style=\"background-color: $bgcolor\">";
		echo "<a class=body href=profile.php?uid=$line[userid] target=_new name=pic$i>$line[username]</a><br>";
		echo "Age: $line[age]<br>";
		echo "Sex: <b>$line[sex]</b><br><br>";
		echo "<input type=radio name=checkID[$line[id]] id=checkidy$line[id] value=y onFocus=\"this.checked=true;setTimeout('jump(" . ($i+1) . ")',50);\"><label for=\"checkidy$line[id]\" class=side><b>Accept</b></label><br>";
		echo "<input type=radio name=checkID[$line[id]] id=checkidn$line[id] value=n onFocus=\"this.checked=true;setTimeout('jump(" . ($i+1) . ")',50);\"><label for=\"checkidn$line[id]\" class=side><b>Deny</b></label>";
		echo "<br><br>$line[description]";

		echo "<br><br>" . number_format(($time - $line['time'])/3600, 2) . " hours";
		echo "</td></tr>";
		echo "<tr><td colspan=2>&nbsp;<br>&nbsp;</td></tr>\n";

		$i++;
	}
	echo "<tr><td colspan=2>";
	echo "<input class=body type=submit style=\"width:200px\" name=vote value=Vote>";
	echo "</td></tr>";

	echo "</table>";
	echo "</form>";

	incFooter();
	exit;
}

function displayPicAbuse(){
	global $config, $db, $userData, $mods;

	$lvl = $mods->getModLvl($userData['userid'], MOD_PICABUSE);

	$prefs = $mods->getModPrefs($userData['userid'], MOD_PICABUSE);

	$ids = $mods->getModItems(MOD_PICABUSE, $prefs['picsperpage'], 60, 180);

	if(!count($ids))
		return;

	$rows = array();
	$db->prepare_query("SELECT pics.id, users.userid, username, pics.age, pics.sex, description FROM users,pics WHERE users.userid=pics.itemid && pics.id IN (?)", $ids);

	while($line = $db->fetchrow()){
		$rows[$line['id']] = $line;
		unset($ids[$line['id']]);
	}

	if(count($ids) > 0)
		$mods->deleteItem(MOD_PICABUSE, $ids);

	if(count($rows) == 0)
		return;

	$db->prepare_query("SELECT abuse.itemid, abuse.userid, username, reason, time, age, sex, firstpic FROM abuse, users WHERE users.userid=abuse.userid && abuse.type = ? && abuse.itemid IN (?)", MOD_PICABUSE, array_keys($rows));

	$abuses = array();
	while($line = $db->fetchrow())
		$abuses[$line['itemid']][] = $line;

	incHeader(750);

	echo "<form action=$_SERVER[PHP_SELF] method=post>";
	echo "<input type=hidden name=action value=vote>";
	echo "<input type=hidden name=mode value=" . MOD_PICABUSE . ">";

	echo "<table cellpadding=3>";

	$picloc = "http://www.nexopia.com" . $config['picdir'];

	foreach($rows as $line){
		if($line['sex']=='Female') 	$bgcolor = '#FFAAAA';
		else						$bgcolor = '#AAAAFF';

		echo "<tr><td class=body valign=top align=center style=\"background-color: $bgcolor\"><img src=$picloc" . floor($line['id']/1000) . "/$line[id].jpg>";
		echo "<br>$line[description]";
		echo "</td>";


		echo "<td class=body valign=top style=\"background-color: $bgcolor\">";
		echo "<a class=body href=profile.php?uid=$line[userid] target=_new>$line[username]</a><br>";
		echo "Age: $line[age]<br>";
		echo "Sex: <b>$line[sex]</b><br><br>";
		echo "<input type=radio name=checkID[$line[id]] id=checkidy$line[id] value=y><label for=\"checkidy$line[id]\" class=side><b>Accept</b></label><br>";
		echo "<input type=radio name=checkID[$line[id]] id=checkidn$line[id] value=n><label for=\"checkidn$line[id]\" class=side><b>Deny</b></label>";

		echo "<br><br>";
		echo "<a class=body href=messages.php?action=write&to=$line[userid]>Send Message</a><br><br>";
		if($mods->isAdmin($userData['userid'],'listusers')){
			echo "<a class=body href=/adminuser.php?type=userid&search=$line[userid]>User Search</a><br>";
			echo "<a class=body href=/adminuserips.php?uid=$line[userid]&type=userid>IP Search</a><br>";
			echo "<a class=body href=/adminabuselog.php?uid=$line[username]>Abuse</a><br>";
			if($mods->isAdmin($userData['userid'],"editprofile"))
				echo "<a class=body href=/manageprofile.php?uid=$line[userid]>Profile</a><br>";
			if($mods->isAdmin($userData['userid'],"editpictures"))
				echo "<a class=body href=/managepicture.php?uid=$line[userid]>Pictures</a><br>";
		}

		echo "</td></tr>";

		if(isset($abuses[$line['id']])){

			echo "<tr><td class=body colspan=2>";

			echo "<table border=0 width=100%>";
			foreach($abuses[$line['id']] as $abuse){
				echo "<tr><td class=body2 valign=top width=100>";
				echo "<a class=body href=profile.php?uid=$abuse[userid]>$abuse[username]</a><br>";
				if($abuse['firstpic'])
					echo "<img src=$imgserver$config[thumbdir]" . floor($abuse['firstpic']/1000) . "/$abuse[firstpic].jpg><br>";
				echo "Age $abuse[age] - $abuse[sex]<br>";
				echo "<a class=body href=messages.php?action=write&to=$abuse[userid]>Send Message</a><br>";
				echo "</td><td class=body2 valign=top>";
				echo "<b>" . userDate("F j, Y", $abuse['time']) . "<br><br>";
				echo "$abuse[reason]<br><br></td></tr>";
			}
			echo "</table>";
			echo "</td></tr>";
		}

		echo "<tr><td colspan=2>&nbsp;<br>&nbsp;</td></tr>\n";
	}
	echo "<tr><td colspan=2>";
	echo "<input class=body type=submit style=\"width:200px\" name=vote value=Vote>";
	echo "</td></tr>";

	echo "</table>";
	echo "</form>";

	incFooter();
	exit;
}

function displayQuestionablePics(){
	global $config, $db, $userData, $mods;

	$lvl = $mods->getModLvl($userData['userid'], MOD_QUESTIONABLEPICS);

	$prefs = $mods->getModPrefs($userData['userid'], MOD_QUESTIONABLEPICS);

	$ids = $mods->getModItems(MOD_QUESTIONABLEPICS, $prefs['picsperpage'], 10);

	if(!count($ids))
		return;

	$rows = array();
	$db->prepare_query("SELECT pics.id, users.userid, username, pics.age, pics.sex, description FROM users,pics WHERE users.userid=pics.itemid && pics.id IN (?)", $ids);

	while($line = $db->fetchrow()){
		$rows[$line['id']] = $line;
		unset($ids[$line['id']]);
	}

	if(count($ids) > 0)
		$mods->deleteItem(MOD_QUESTIONABLEPICS, $ids);

	if(count($rows) == 0)
		return;

	$db->prepare_query("SELECT modvoteslog.picid, modvoteslog.modid, users.username, modvoteslog.vote, modvoteslog.time, mods.level FROM modvoteslog, users, mods WHERE users.userid=modvoteslog.modid && mods.userid = modvoteslog.modid && modvoteslog.picid IN (?)", array_keys($rows));

	$votes = array();
	while($line = $db->fetchrow())
		$votes[$line['picid']][] = $line;

	incHeader();

	echo "<form action=$_SERVER[PHP_SELF] method=post>";
	echo "<input type=hidden name=action value=vote>";
	echo "<input type=hidden name=mode value=" . MOD_QUESTIONABLEPICS . ">";

	echo "<table cellpadding=3>";

	$picloc = "http://www.nexopia.com" . $config['picdir'];

	foreach($rows as $line){
		if($line['sex']=='Female') 	$bgcolor = '#FFAAAA';
		else						$bgcolor = '#AAAAFF';

		echo "<tr><td class=body valign=top align=right style=\"background-color: $bgcolor\"><img src=$picloc" . floor($line['id']/1000) . "/$line[id].jpg></td>";
		echo "<td class=body valign=top style=\"background-color: $bgcolor\">";
		echo "<a class=body href=profile.php?uid=$line[userid] target=_new>$line[username]</a><br>";
		echo "Age: $line[age]<br>";
		echo "Sex: <b>$line[sex]</b><br><br>";
		echo "<input type=radio name=checkID[$line[id]] id=checkidy$line[id] value=y><label for=\"checkidy$line[id]\" class=side><b>Accept</b></label><br>";
		echo "<input type=radio name=checkID[$line[id]] id=checkidn$line[id] value=n><label for=\"checkidn$line[id]\" class=side><b>Deny</b></label>";
		echo "<br><br>$line[description]";
		echo "</td></tr>";

		if(isset($votes[$line['id']])){
			echo "<tr><td class=header colspan=2>Votes</tr></tr>";
			echo "<tr><td class=body colspan=2>";

			echo "<table>";
			$score = 0;
			foreach($votes[$line['id']] as $vote){
				echo "<tr>";
				echo "<td class=body><a class=body href=profile.php?uid=$vote[modid]>$vote[username]</a></td>";
				echo "<td class=body>" . ($vote['vote'] == 'y' ? "Accept" : "Deny") . "</td>";
				echo "<td class=body>$vote[level]</td>";
				echo "</tr>";
				$score += ($vote['vote'] == 'y' ? $vote['level'] : 0 - $vote['level']);
			}
			echo "<tr><td class=header></td><td class=header>" . ( $score > 0 ? "Accept" : "Deny" ) . "</td><td class=header>$score</td></tr>";
			echo "</table>";
			echo "</td></tr>";
		}

		echo "<tr><td colspan=2>&nbsp;<br>&nbsp;</td></tr>\n";

	}
	echo "<tr><td colspan=2>";
	echo "<input class=body type=submit style=\"width:200px\" name=vote value=Vote>";
	echo "</td></tr>";

	echo "</table>";
	echo "</form>";

	incFooter();
	exit;
}

function displaySignPics(){
	global $config,$db, $userData, $mods;

	$lvl = $mods->getModLvl($userData['userid'], MOD_SIGNPICS);

	$prefs = $mods->getModPrefs($userData['userid'], MOD_SIGNPICS);

	$ids = $mods->getModItems(MOD_SIGNPICS, $prefs['picsperpage'], 3, 60);

	if(!count($ids))
		return;

	$rows = array();
	$db->prepare_query("SELECT pics.id, users.userid, username, pics.age, pics.sex, description FROM users,pics WHERE users.userid=pics.itemid && pics.id IN (?)", $ids);

	while($line = $db->fetchrow()){
		$rows[$line['id']] = $line;
		unset($ids[$line['id']]);
	}

	if(count($ids) > 0)
		$mods->deleteItem(MOD_SIGNPICS, $ids);

	if(count($rows) == 0)
		return;

	incHeader();

	if($prefs['autoscroll'] == 'y')
		echo "<script> function jump(i){ location.href='#pic' + i;} </script>\n";
	else
		echo "<script> function jump(i){ return true; } </script>\n";

	echo "<form action=$_SERVER[PHP_SELF] method=post>";
	echo "<input type=hidden name=action value=vote>";
	echo "<input type=hidden name=mode value=" . MOD_SIGNPICS . ">";

	echo "<table cellpadding=3>";

	$picloc = "http://www.nexopia.com" . $config['picdir'];

	$i=1;
	foreach($rows as $line){
		if($line['sex']=='Female') 	$bgcolor = '#FFAAAA';
		else						$bgcolor = '#AAAAFF';

		echo "<tr><td class=body valign=top align=right style=\"background-color: $bgcolor\"><img src=$picloc" . floor($line['id']/1000) . "/$line[id].jpg></td>";
		echo "<td class=body valign=top style=\"background-color: $bgcolor\">";
		echo "<a class=body href=profile.php?uid=$line[userid] target=_new name=pic$i>$line[username]</a><br>";
		echo "Age: $line[age]<br>";
		echo "Sex: <b>$line[sex]</b><br><br>";
		echo "<input type=radio name=checkID[$line[id]] id=checkidy$line[id] value=y onFocus=\"this.checked=true;setTimeout('jump(" . ($i+1) . ")',50);\"><label for=\"checkidy$line[id]\" class=side><b>Accept</b></label><br>";
		echo "<input type=radio name=checkID[$line[id]] id=checkidn$line[id] value=n onFocus=\"this.checked=true;setTimeout('jump(" . ($i+1) . ")',50);\"><label for=\"checkidn$line[id]\" class=side><b>Deny</b></label>";
		echo "<br><br>$line[description]";
		echo "</td></tr>";
		echo "<tr><td colspan=2>&nbsp;<br>&nbsp;</td></tr>\n";

		$i++;
	}
	echo "<tr><td colspan=2>";
	echo "<input class=body type=submit style=\"width:200px\" name=vote value=Vote>";
	echo "</td></tr>";

	echo "</table>";
	echo "</form>";

	incFooter();
	exit;
}

function displayGallery(){
	global $config,$db, $userData, $mods;

	$lvl = $mods->getModLvl($userData['userid'], MOD_GALLERY);

	$prefs = $mods->getModPrefs($userData['userid'], MOD_GALLERY);

	$ids = $mods->getModItems(MOD_GALLERY, $prefs['picsperpage'], 3, 60);

	if(!count($ids))
		return;

	$rows = array();
	$db->prepare_query("SELECT gallery.id, users.userid,username,age,users.sex, gallery.description FROM users,gallery WHERE users.userid=gallery.userid && gallery.id IN (?)", $ids);

	while($line = $db->fetchrow()){
		$rows[$line['id']] = $line;
		unset($ids[$line['id']]);
	}

	if(count($ids))
		$mods->deleteItem(MOD_GALLERY, $ids);

	if(!count($rows))
		return;

	incHeader();

	if($prefs['autoscroll'] == 'y')
		echo "<script> function jump(i){ location.href='#pic' + i;} </script>\n";
	else
		echo "<script> function jump(i){ return true; } </script>\n";

	echo "<form action=$_SERVER[PHP_SELF] method=post>";
	echo "<input type=hidden name=action value=vote>";
	echo "<input type=hidden name=mode value=" . MOD_GALLERY . ">";

	echo "<table cellpadding=3>";

	$picloc = "http://www.nexopia.com" . $config['gallerypicdir'];
	$i = 1;

	foreach($rows as $line){
		if($line['sex']=='Female') 	$bgcolor = '#FFAAAA';
		else						$bgcolor = '#AAAAFF';

		echo "<tr><td class=body valign=top align=right style=\"background-color: $bgcolor\"><img src=$picloc" . floor($line['id']/1000) . "/$line[id].jpg></td>";
		echo "<td class=body valign=top style=\"background-color: $bgcolor\">";
		echo "<a class=body href=profile.php?uid=$line[userid] target=_new name=pic$i>$line[username]</a><br>";
		echo "Age: $line[age]<br>";
		echo "Sex: <b>$line[sex]</b><br><br>";
		echo "<input type=radio name=checkID[$line[id]] id=checkidy$line[id] value=y onFocus=\"this.checked=true;setTimeout('jump(" . ($i+1) . ")',50);\"><label for=\"checkidy$line[id]\" class=side><b>Accept</b></label><br>";
		echo "<input type=radio name=checkID[$line[id]] id=checkidn$line[id] value=n onFocus=\"this.checked=true;setTimeout('jump(" . ($i+1) . ")',50);\"><label for=\"checkidn$line[id]\" class=side><b>Deny</b></label>";
		echo "<br><br>$line[description]";
		echo "</td></tr>";

		echo "<tr><td colspan=2>&nbsp;<br>&nbsp;</td></tr>\n";
		$i++;
	}
	echo "<tr><td colspan=2>";
	echo "<input class=body type=submit style=\"width:200px\" name=vote value=Vote>";
	echo "</td></tr>";

	echo "</table>";
	echo "</form>";

	incFooter();
	exit;
}

function displayGalleryAbuse(){
	global $config,$db, $userData, $mods;

	$lvl = $mods->getModLvl($userData['userid'], MOD_GALLERYABUSE);

	$prefs = $mods->getModPrefs($userData['userid'], MOD_GALLERYABUSE);

	$ids = $mods->getModItems(MOD_GALLERYABUSE, $prefs['picsperpage'], 20, 60);

	if(!count($ids))
		return;

	$rows = array();
	$db->prepare_query("SELECT gallery.id, users.userid,username,age,users.sex, gallery.description FROM users,gallery WHERE users.userid=gallery.userid && gallery.id IN (?)", $ids);

	while($line = $db->fetchrow()){
		$rows[$line['id']] = $line;
		unset($ids[$line['id']]);
	}

	if(count($ids))
		$mods->deleteItem(MOD_GALLERYABUSE, $ids);

	if(!count($rows))
		return;


	$db->prepare_query("SELECT abuse.itemid, abuse.userid, username, reason, time FROM abuse,users WHERE users.userid=abuse.userid && abuse.type = ? && abuse.itemid IN (?)", MOD_GALLERYABUSE, array_keys($rows));

	$abuses = array();
	while($line = $db->fetchrow())
		$abuses[$line['itemid']][] = $line;

	incHeader();

	echo "<form action=$_SERVER[PHP_SELF] method=post>";
	echo "<input type=hidden name=action value=vote>";
	echo "<input type=hidden name=mode value=" . MOD_GALLERYABUSE . ">";

	echo "<table cellpadding=3>";

	$picloc = "http://www.nexopia.com" . $config['gallerypicdir'];

	foreach($rows as $line){
		if($line['sex']=='Female') 	$bgcolor = '#FFAAAA';
		else						$bgcolor = '#AAAAFF';

		echo "<tr><td class=body valign=top align=right style=\"background-color: $bgcolor\"><img src=$picloc" . floor($line['id']/1000) . "/$line[id].jpg></td>";
		echo "<td class=body valign=top style=\"background-color: $bgcolor\">";
		echo "<a class=body href=profile.php?uid=$line[userid] target=_new>$line[username]</a><br>";
		echo "Age: $line[age]<br>";
		echo "Sex: <b>$line[sex]</b><br><br>";
		echo "<input type=radio name=checkID[$line[id]] id=checkidy$line[id] value=y><label for=\"checkidy$line[id]\" class=side><b>Accept</b></label><br>";
		echo "<input type=radio name=checkID[$line[id]] id=checkidn$line[id] value=n><label for=\"checkidn$line[id]\" class=side><b>Deny</b></label>";
		echo "<br><br>$line[description]";
		echo "</td></tr>";

		if(isset($abuses[$line['id']])){
			echo "<tr><td class=header colspan=2>Abuse Reports</tr></tr>";
			echo "<tr><td class=body colspan=2>";

			echo "<table border=0 width=100%>";
			foreach($abuses[$line['id']] as $abuse){
				echo "<tr><td class=header>";
				echo "<a class=header href=profile.php?uid=$abuse[userid]>$abuse[username]</a>";
				if($abuse['time'])
					echo "<br>" . userDate("F j, Y", $abuse['time']);
				echo "</td><td class=body>$abuse[reason]</td></tr>";
			}
			echo "</table>";
			echo "</td></tr>";
		}

		echo "<tr><td colspan=2>&nbsp;<br>&nbsp;</td></tr>\n";

	}
	echo "<tr><td colspan=2>";
	echo "<input class=body type=submit style=\"width:200px\" name=vote value=Vote>";
	echo "</td></tr>";

	echo "</table>";
	echo "</form>";

	incFooter();
	exit;
}

function displayUserAbuse($type){ //type = MOD_USERABUSE or MOD_USERABUSE_CONFIRM
	global $config, $db, $userData, $mods, $abuselog;

	$lvl = $mods->getModLvl($userData['userid'], $type);

	$prefs = $mods->getModPrefs($userData['userid'], $type);

	$ids = $mods->getModItems($type, $prefs['picsperpage'], 60, 180);

	if(!count($ids))
		return;

	$rows = array();
	$result1 = $db->prepare_query("SELECT userid as id, username, age, sex, loc, firstpic, frozen FROM users WHERE userid IN (#)", $ids);

	$result2 = $abuselog->db->prepare_query("SELECT id, userid, username, modid, modname, action, reason, time, subject FROM abuselog WHERE userid IN (#) ORDER BY time DESC", $ids);

	while($line = $db->fetchrow($result1)){
		$rows[$line['id']] = $line;
		$rows[$line['id']]['abuse'] = array();
		unset($ids[$line['id']]);
	}

	while($line = $abuselog->db->fetchrow($result2))
		$rows[$line['userid']]['abuse'][] = $line;

	if(count($ids))
		$mods->deleteItem($type, $ids);

	if(!count($rows))
		return;

	$db->prepare_query("SELECT abuse.itemid, abuse.userid, username, reason, time, age, sex, loc, firstpic FROM abuse LEFT JOIN users ON users.userid=abuse.userid WHERE abuse.type = ? && abuse.itemid IN (?)", $type, array_keys($rows));

	$abuses = array();
	while($line = $db->fetchrow())
		$abuses[$line['itemid']][] = $line;

	incHeader(750);

	$locations = & new category( $db, "locs");

	echo "<form action=$_SERVER[PHP_SELF] method=post>";
	echo "<input type=hidden name=action value=vote>";
	echo "<input type=hidden name=mode value=" . $type . ">";

	echo "<table cellpadding=3 width=100%>";

	foreach($rows as $line){

		echo "<tr><td class=header colspan=4>";
		echo "<a class=header href=profile.php?uid=$line[id] target=_new>$line[username]</a> - ";
		echo "Age $line[age] - $line[sex] - " . $locations->getCatName($line['loc']);
		echo "</td></tr>";

		echo "<tr>";

		echo "<td class=body valign=top>";
		if($line['firstpic']){
			echo "<a class=body href=profile.php?uid=$line[id] target=_new>";
			echo "<img src=$config[thumbdir]" . floor($line['firstpic']/1000) . "/$line[firstpic].jpg border=0>";
			echo "</a>";
		}else
			echo "No Pic";
		echo "</td>";


		echo "<td class=body valign=top nowrap>";

		if($line['frozen'] == 'y')
			echo "<b>Frozen</b><br><br>";

		if($mods->isAdmin($userData['userid'],'listusers')){
			echo "<a class=body href=/adminuser.php?type=userid&search=$line[id]>User Search</a><br>";
			echo "<a class=body href=/adminuserips.php?uid=$line[id]&type=userid>IP Search</a><br>";
//			echo "<a class=body href=/adminabuselog.php?uid=$line[username]>Abuse Log: " . count($line['abuse']) . "</a><br>";
			if($mods->isAdmin($userData['userid'],"editprofile"))
				echo "<a class=body href=/manageprofile.php?uid=$line[id]>Profile</a><br>";
			if($mods->isAdmin($userData['userid'],"editpictures"))
				echo "<a class=body href=/managepicture.php?uid=$line[id]>Pictures</a><br><br>";
		}
		echo "<a class=body href=messages.php?action=write&to=$line[id]>Send Message</a><br>";
		echo "<a class=body href=reportabuse.php?type=" . $type . "&id=$line[id]&section=otherabuse>Report Abuse</a>";

		echo "<br><br>";

		if($type == MOD_USERABUSE){
			echo "<input type=radio name=checkID[$line[id]] id=checkidy$line[id] value=y><label for=\"checkidy$line[id]\" class=side><b>Done and Delete</b></label><br>";
			echo "<input type=radio name=checkID[$line[id]] id=checkidn$line[id] value=n><label for=\"checkidn$line[id]\" class=side><b>Ready for Admin</b></label><br>";
			echo "<input type=radio name=checkID[$line[id]] id=checkidi$line[id] value=i><label for=\"checkidi$line[id]\" class=side><b>Ignore</b></label>";
		}else{ //$type = MOD_USERABUSE_CONFIRM
			echo "<input type=checkbox name=checkID[$line[id]] id=checkidy$line[id] value=y><label for=\"checkidy$line[id]\" class=side><b>Done</b></label>";
		}

		echo "</td>";

		if($mods->isAdmin($userData['userid'],'abuselog')){
			echo "<td class=body valign=top>";

			echo "<table>";
			echo "<tr>";
			echo "<td class=header>User</td>";
			echo "<td class=header>Mod</td>";
			echo "<td class=header>Action</td>";
			echo "<td class=header>Reason</td>";
			echo "<td class=header>Subject</td>";
			echo "<td class=header>Time</td>";
			echo "</tr>";

			$i=0;
			$show = 15;
			foreach($line['abuse'] as $row){
				echo "<tr>";
				echo "<td class=body><a class=body href=profile.php?uid=$row[userid] target=_new>$row[username]</a></td>";
				echo "<td class=body nowrap><a class=body href=profile.php?uid=$row[modid] target=_new>$row[modname]</a></td>";
				echo "<td class=body nowrap>" . $abuselog->actions[$row['action']] . "</td>";
				echo "<td class=body nowrap>" . $abuselog->reasons[$row['reason']] . "</td>";
				echo "<td class=body><a class=body href=adminabuselog.php?action=view&id=$row[id]>$row[subject]</a></td>";
				echo "<td class=body nowrap>" . userDate("M j, Y, g:i a", $row['time']) . "</td>";
				echo "</tr>";
				if(++$i >= $show)
					break;
			}
			if(count($line['abuse']) > $show)
				echo "<tr><td class=body colspan=6>Showing $show of " . count($line['abuse']) . " abuselog entries. <a class=body href=/adminabuselog.php?uid=$line[username]>Click for more</a></td></tr>";

			echo "</table>";

			echo "</td>";
		}

		echo "<td class=body valign=top>";

		echo "</td>";


		echo "</tr>";

		if(isset($abuses[$line['id']])){
			echo "<tr><td class=body colspan=4>";

			echo "<table border=0 width=100% cellpadding=2>";
			foreach($abuses[$line['id']] as $abuse){

				echo "<tr>";
				echo "<td class=body2 valign=top width=100>";
				if($abuse['firstpic']){
					echo "<a class=body href=profile.php?uid=$abuse[userid] target=_new>";
					echo "<img src=$config[thumbdir]" . floor($abuse['firstpic']/1000) . "/$abuse[firstpic].jpg border=0>";
					echo "</a>";
				}else{
					echo "No Pic";
				}
				echo "</td>";
				echo "<td class=body2 valign=top width=80>";
				echo "<a class=body href=profile.php?uid=$abuse[userid] target=_new>$abuse[username]</a><br>";
				echo "Age $abuse[age] - $abuse[sex]<br>";
				echo $locations->getCatName($abuse['loc']) . "<br>";
				echo userDate("F j, Y", $abuse['time']) . "<br>";
				echo "<a class=body href=messages.php?action=write&to=$abuse[userid]>Send Message</a><br>";
				echo "</td>";
				echo "<td class=body2 valign=top>";
				echo "$abuse[reason]</td>";

				echo "</tr>";
			}
			echo "</table>";
			echo "</td></tr>";
		}

		echo "<tr><td colspan=2>&nbsp;<br>&nbsp;</td></tr>\n";
	}
	echo "<tr><td class=body colspan=4>";
	echo "<input class=body type=submit style=\"width:200px\" name=vote value=Vote>";
	echo "</td></tr>";

	echo "</table>";
	echo "</form>";

	incFooter();
	exit;
}

function displayBanners(){
	global $config,$db, $userData, $mods, $banner;

	$lvl = $mods->getModLvl($userData['userid'], MOD_BANNER);

	$prefs = $mods->getModPrefs($userData['userid'], MOD_BANNER);

	$ids = $mods->getModItems(MOD_BANNER, 1, 20);

	$id = current($ids);

	incHeader();

	echo "<form action=$_SERVER[PHP_SELF] method=post>";
	echo "<table>";
	echo "<input type=hidden name=mode value=" . MOD_BANNER . ">";
	echo "<input type=hidden name=id value=$id>";

	echo "<tr><td class=body>";
	echo $banner->getbannerId($id);
	echo "</td></tr>";

	echo "<tr><td class=header><input class=body type=submit style=\"width:100px\" name=vote value=Accept><input class=body type=submit style=\"width:100px\" name=vote value=Deny></td></tr>";
	echo "</table></form>";

	incFooter();
	exit;
}

function displayForumRanks(){
	global $config,$db, $userData, $mods;

	$lvl = $mods->getModLvl($userData['userid'], MOD_FORUMRANK);

	$prefs = $mods->getModPrefs($userData['userid'], MOD_FORUMRANK);

	$ids = $mods->getModItems(MOD_FORUMRANK, $prefs['picsperpage'], 5, 120);

	if(!count($ids))
		return;

	$rows = array();
	$db->prepare_query("SELECT forumrankspending.id,forumrankspending.userid,username,forumrankspending.forumrank FROM users,forumrankspending WHERE users.userid=forumrankspending.userid && forumrankspending.id IN (?)", $ids);

	while($line = $db->fetchrow()){
		$rows[$line['id']] = $line;
		unset($ids[$line['id']]);
	}

	if(count($ids))
		$mods->deleteItem(MOD_FORUMRANK, $ids);

	if(!count($rows))
		return;



	incHeader();

	if($prefs['autoscroll'] == 'y')
		echo "<script> function jump(i){ location.href='#rank' + i;} </script>\n";
	else
		echo "<script> function jump(i){ return true; } </script>\n";

	echo "<form action=$_SERVER[PHP_SELF] method=post>";
	echo "<input type=hidden name=action value=vote>";
	echo "<input type=hidden name=mode value=" . MOD_FORUMRANK . ">";

	echo "<table cellpadding=3>";

	$i=0;
	foreach($rows as $line){
		echo "<tr><td class=body><a class=body href=profile.php?uid=$line[userid] target=_new name=rank$i>$line[username]</a></td></tr>";
		echo "<tr><td class=body>$line[forumrank]</td></tr>";
		echo "<tr><td class=body>";
		echo "<input type=radio name=checkID[$line[id]] id=checkidy$line[id] value=y onFocus=\"this.checked=true;setTimeout('jump(" . ($i+1) . ")',50);\"><label for=\"checkidy$line[id]\" class=side><b>Accept</b></label> ";
		echo "<input type=radio name=checkID[$line[id]] id=checkidn$line[id] value=n onFocus=\"this.checked=true;setTimeout('jump(" . ($i+1) . ")',50);\"><label for=\"checkidn$line[id]\" class=side><b>Deny</b></label>";
		echo "</td></tr>";

		echo "<tr><td>&nbsp;<br>&nbsp;</td></tr>\n";
		$i++;
	}
	echo "<tr><td class=body>";
	echo "<input class=body type=submit style=\"width:200px\" name=vote value=Vote>";
	echo "</td></tr>";

	echo "</table>";
	echo "</form>";

	incFooter();
	exit;
}

function forumPostAbuse(){
	global $config, $db, $profiledb, $userData, $mods, $forums;

	$lvl = $mods->getModLvl($userData['userid'], MOD_FORUMPOST);

	$prefs = $mods->getModPrefs($userData['userid'], MOD_FORUMPOST);

	$ids = $mods->getModItems(MOD_FORUMPOST, $prefs['picsperpage'], 60, 180);

	if(!count($ids))
		return;

	$rows = array();
	$posterids = array();
	$posterdata = array();
	$forums->db->prepare_query("SELECT forumposts.id, threadid, title, nmsg, forumposts.author, forumposts.authorid, forumposts.time, forumthreads.locked, forumthreads.flag, forumthreads.sticky, forumthreads.announcement, forums.name, forums.official, forumthreads.forumid, forumposts.edit FROM forumposts, forumthreads, forums WHERE forumposts.threadid=forumthreads.id && forumthreads.forumid=forums.id && forumposts.id IN (?)", $ids);

	while($line = $forums->db->fetchrow()){
		$rows[$line['id']] = $line;
		$posterids[$line['authorid']] = $line['authorid'];
		unset($ids[$line['id']]);
	}

	if(count($ids))
		$mods->deleteItem(MOD_FORUMPOST, $ids);

	if(!count($rows))
		return;

	if(count($posterids)){
		$db->prepare_query("SELECT userid, online, age, sex, posts, firstpic, forumrank, showpostcount, '' as nsigniture, premiumexpiry, frozen FROM users WHERE userid IN (#)", $posterids);

		while($line = $db->fetchrow())
			$posterdata[$line['userid']] = $line;


		$profiledb->prepare_query($posterids, "SELECT userid, nsigniture FROM profile WHERE userid IN (#) && enablesignature = 'y'", $posterids);

		while($line = $profiledb->fetchrow())
			$posterdata[$line['userid']]['nsigniture'] = $line['nsigniture'];
	}

	$db->prepare_query("SELECT abuse.itemid, abuse.userid, username, age, sex, firstpic, reason, time FROM abuse,users WHERE users.userid=abuse.userid && abuse.type = ? && abuse.itemid IN (?)", MOD_FORUMPOST, array_keys($rows));

	$abuses = array();
	while($line = $db->fetchrow())
		$abuses[$line['itemid']][] = $line;

	$time = time();

	incHeader(750);

	echo "<form action=$_SERVER[PHP_SELF] method=post>";
	echo "<input type=hidden name=action value=vote>";
	echo "<input type=hidden name=mode value=" . MOD_FORUMPOST . ">";

	echo "<table cellpadding=3 align=center>";

	//$db->prepare_query("SELECT forumpostsperpage FROM users WHERE userid = ?", $userData['userid']);
	$postsPerPage = $userData['forumpostsperpage']; //$db->fetchfield();

	foreach($rows as $line){
		$forums->db->prepare_query("SELECT count(*) FROM forumposts WHERE id < ? && threadid = ?", $line['id'], $line['threadid']);
		$postnum = $forums->db->fetchfield();

		$page = floor($postnum / $postsPerPage);

		echo "<tr><td class=header colspan=2>";

		if($line['flag']=='y')			echo "<img src=$config[imageloc]flag.gif> ";
		if($line['locked']=='y')		echo "<img src=$config[imageloc]locked.png> ";
		if($line['sticky']=='y')		echo "<img src=$config[imageloc]up.png> ";
		if($line['announcement']=='y')	echo "Announcement: ";

		if($line['official']=='y')
			echo "<a class=header href=forums.php>Forums</a> > ";
		else
			echo "<a class=header href=forumsusercreated.php>User Created Forums</a> > ";

		echo "<a class=header target=_new href=forumthreads.php?fid=$line[forumid]>$line[name]</a> > ";
		echo "<a class=header target=_new href=forumviewthread.php?tid=$line[threadid]&page=$page#p$line[id]>$line[title]</a>";
		echo "</td></tr>";

		echo "<tr>";
		echo "<td class=body valign=top nowrap width=100>";

		if(isset($posterdata[$line['authorid']])){
			echo "<a class=body href=profile.php?uid=$line[authorid]><b>$line[author]</b></a><br>";
			$data = $posterdata[$line['authorid']];

			if($data['frozen'] == 'y')
				echo "<b>frozen account</b><br>";
		}else{
			echo "<b>$line[author]</b><br>deleted account<br>";
			$line['authorid']=0;
		}

		if($line['authorid']){
			if($data['forumrank']!="" && $data['premiumexpiry'] > $time)
				echo $data['forumrank'];
			else
				echo $forums->forumrank($data['posts']);
			echo "<br>";
			if($data['online'] == 'y')
				echo "- Online -<br>";
		}

		if($config['forumPic'] && $line['authorid'] && $data['firstpic']>0)
			echo "<a class=header href=profile.php?uid=$line[authorid]><img src=http://" . chooseImageServer($data['firstpic']) . $config['thumbdir'] . floor($data['firstpic']/1000) . "/$data[firstpic].jpg border=0></a><br>";

		if($line['authorid']){
			echo "<br>Age <i>$data[age]</i>, $data[sex]<br>";
			if($data['showpostcount'] == 'y')
				echo "Posts: <i>" . number_format($data['posts']) . "</i><br>";
		}

		echo "</td><td class=body valign=top width=100%>";
		echo $line['nmsg'] . "&nbsp;";

		if($line['edit'])
			echo "<br><br>[edited on " . userdate("F j, Y \\a\\t g:i a",$line['edit']) . "]";

		if($line['authorid'] && $data['nsigniture']!="")// && ($userData['loggedIn'] || $userData['showsigs'] == 'y'))
			echo "<br><br>________________________________<br>" . $data['nsigniture'] . "&nbsp;";

		echo "</td></tr>\n";
		echo "<tr><td class=small colspan=2>";

		echo "<table width=100% cellspacing=0 cellpadding=0><tr><td class=small>";

		echo userdate("l F j, Y, g:i a",$line['time']) ."</td><td align=right class=small>";

		$links = array();

		$links[] = "<a class=small href=\"messages.php?action=write&to=$line[authorid]\">Send Message</a>";
		$links[] = "<a class=small href=\"/manageprofile.php?uid=$line[authorid]&section=forums\">Edit Sig</a>"; //<img src=$skindir/forum/quote.jpg border=0>

		echo implode(" &nbsp; &nbsp; ", $links);

		echo "</td></tr></table>";

		echo "</td></tr>";


		if(isset($abuses[$line['id']])){
			echo "<tr><td class=header colspan=2>Abuse Reports</tr></tr>";
			echo "<tr><td class=body colspan=2>";

			echo "<table border=0 width=100%>";
			foreach($abuses[$line['id']] as $abuse){
				echo "<tr>";
				echo "<td class=body2 valign=top width=100>";
				if($abuse['firstpic']){
					echo "<a class=body href=profile.php?uid=$abuse[userid] target=_new>";
					echo "<img src=$config[thumbdir]" . floor($abuse['firstpic']/1000) . "/$abuse[firstpic].jpg border=0>";
					echo "</a>";
				}else{
					echo "No Pic";
				}
				echo "</td>";
				echo "<td class=body2 valign=top width=80>";
				echo "<a class=body href=profile.php?uid=$abuse[userid] target=_new>$abuse[username]</a><br>";
				echo "Age $abuse[age] - $abuse[sex]<br>";
				echo userDate("F j, Y", $abuse['time']) . "<br>";
				echo "<a class=body href=messages.php?action=write&to=$abuse[userid]>Send Message</a><br>";
				echo "</td>";
				echo "<td class=body2 valign=top>";
				echo "$abuse[reason]</td>";

				echo "</tr>";

			}
			echo "</table>";
			echo "</td></tr>";
		}

		echo "<tr><td class=body></td><td class=body>";
		echo "<input type=checkbox name=checkID[$line[id]] id=checkidy$line[id] value=y><label for=\"checkidy$line[id]\" class=side><b>Done</b></label>";
		echo "</td></tr>";


		echo "<tr><td colspan=2>&nbsp;<br>&nbsp;</td></tr>\n";

	}
	echo "<tr><td class=body colspan=2>";
	echo "<input class=body type=submit style=\"width:200px\" name=vote value=Vote>";
	echo "</td></tr>";

	echo "</table>";
	echo "</form>";

	incFooter();
	exit;
}

function forumBans(){
	global $config, $db, $forums, $userData, $mods, $mutelength;

	$lvl = $mods->getModLvl($userData['userid'], MOD_FORUMBAN);

	$prefs = $mods->getModPrefs($userData['userid'], MOD_FORUMBAN);

	$ids = $mods->getModItems(MOD_FORUMBAN, $prefs['picsperpage'], 0,0);//20, 60);

	if(!count($ids))
		return;

	$rows = array();
//	$db->prepare_query("SELECT forumposts.id, threadid, title, nmsg, forumposts.author, forumposts.authorid, forumposts.time FROM forumposts, forumthreads WHERE forumposts.threadid=forumthreads.id && forumposts.id IN (?)", $ids);

	$forums->db->prepare_query("SELECT 	forummute.id,
								forummute.userid,
								mutetime,
								unmutetime,
								forummutereason.modid,
								reason,
								forumid,
								forums.name
						FROM 	forummute,
								forummutereason
							LEFT JOIN forums ON forummute.forumid = forums.id
						WHERE 	forummute.id = forummutereason.id &&
								forummute.id IN (?)", $ids);

	$uids = array();
	$users = array();
	while($line = $forums->db->fetchrow()){
		$rows[$line['id']] = $line;
		$uids[$line['userid']] = $line['userid'];
		$uids[$line['modid']] = $line['modid'];
		unset($ids[$line['id']]);
	}


	if(count($ids))
		$mods->deleteItem(MOD_FORUMBAN, $ids);

	if(!count($rows))
		return;

	$db->prepare_query("SELECT userid, username FROM users WHERE userid IN (?)", $uids);

	while($line = $db->fetchrow())
		$users[$line['userid']] = $line['username'];

	incHeader();

	echo "<form action=$_SERVER[PHP_SELF] method=post>";
	echo "<input type=hidden name=action value=vote>";
	echo "<input type=hidden name=mode value=" . MOD_FORUMBAN . ">";

	echo "<table cellpadding=3>";

	foreach($rows as $line){
		echo "<tr><td class=header>Forum:</td><td class=header>" . ($line['forumid'] ? "<a class=header href=forumthreads.php?fid=$line[forumid]>$line[name]</a>" : "Global" ) . "</td></tr>";
		echo "<tr><td class=header>Username:</td><td class=header><a class=header target=_new href=profile.php?uid=$line[userid]>" . $users[$line['userid']] . "</a></td></tr>";
		echo "<tr><td class=header>Mod:</td><td class=header><a class=header target=_new href=profile.php?uid=$line[modid]>" . $users[$line['modid']] . "</a></td></tr>";
		echo "<tr><td class=header>Mute Time:</td><td class=header>" . userDate("l F j, Y, g:i a", $line['mutetime']) . "</td></tr>";
		if($line['unmutetime'])
			echo "<tr><td class=header>Un-Mute Time:</td><td class=header>" . userDate("l F j, Y, g:i a", $line['unmutetime']) . "</td></tr>";
		echo "<tr><td class=header>Length:</td><td class=header>" . $forums->mutelength[($line['unmutetime'] ? $line['unmutetime'] - $line['mutetime'] : 0)] . "</td></tr>";
		echo "<tr><td class=header>Reason:</td><td class=body>$line[reason]</td></tr>";

		echo "<tr><td class=body colspan=2>";
		echo "<input type=checkbox name=checkID[$line[id]] id=checkidy$line[id] value=y><label for=\"checkidy$line[id]\" class=side><b>Done</b></label>";
		echo "</td></tr>";

		echo "<tr><td colspan=2>&nbsp;<br>&nbsp;</td></tr>\n";

	}
	echo "<tr><td>";
	echo "<input class=body type=submit style=\"width:200px\" name=vote value=Vote>";
	echo "</td></tr>";

	echo "</table>";
	echo "</form>";

	incFooter();
	exit;
}

function displayPolls($id){
	global $userData,$db, $mods, $polls;

	if($id){

		$poll = $polls->getPoll($id, false);

		incHeader();

		echo "<form action=$_SERVER[PHP_SELF]>";
		echo "<table align=center>";
		echo "<input type=hidden name=mode value=" . MOD_POLL . ">";
		echo "<input type=hidden name=id value=$id>";

		echo "<tr><td class=header>$poll[question]</td></tr>";

		foreach($poll['answers'] as $ans)
			echo "<tr><td class=body> - $ans[answer]</td></tr>";

		echo "<tr><td class=header colspan=2><input class=body type=submit style=\"width:100px\" name=vote value=Accept><input class=body type=submit style=\"width:100px\" name=vote value=Deny></td></tr>";
		echo "</table></form>";

		incFooter();
		exit;
	}else{
		$polls->db->prepare_query("SELECT id, question FROM polls WHERE official = 'y' && moded = 'n' ORDER BY date ASC");

		$rows = array();
		while($line = $polls->db->fetchrow())
			$rows[$line['id']] = $line['question'];

		$polls->db->prepare_query("SELECT pollid, answer FROM pollans WHERE pollid IN (#)", array_keys($rows));

		$ans = array();
		while($line = $polls->db->fetchrow())
			$ans[$line['pollid']][] = $line['answer'];

		incHeader();

		echo "<table align=center>";
		echo "<tr><td class=header></td><td class=header>Question</td><td class=header>Answers</td></tr>";

		echo "<form action=$_SERVER[PHP_SELF] method=post>";
		echo "<input type=hidden name=mode value=" . MOD_POLL . ">";
		echo "<input type=hidden name=id value=$id>";

		foreach($rows as $qid => $q){
			echo "<tr>";
			echo "<td class=body><input type=checkbox name=checkID[$qid] value=n></td>";
			echo "<td class=body><a class=body href=\"$_SERVER[PHP_SELF]?mode=" . MOD_POLL . "&id=$qid\">$q</a></td>";
			echo "<td class=body>" . implode(", ", $ans[$qid]) . "</td>";
			echo "</tr>\n";
		}

		echo "<tr><td class=body colspan=3><input class=body type=submit name=vote value=Deny></td></tr>";

		echo "</form>";
		echo "</table>";

		incFooter();
		exit;
	}
}

function displayArticles($id){
	global $userData,$db, $mods;

//DELETE articles FROM articles LEFT JOIN moditems ON articles.id=moditems.itemid WHERE articles.moded = 'n' && moditems.id IS NULL

	if($id){
		$db->prepare_query("SELECT * FROM articles WHERE id = ?", $id);
		$line = $db->fetchrow();

		incHeader();

		echo "<form action=$_SERVER[PHP_SELF]>";
		echo "<table align=center>";
		echo "<input type=hidden name=mode value=" . MOD_ARTICLE . ">";
		echo "<input type=hidden name=id value=$id>";


		$cats = & new category( $db, "cats");
		$root = $cats->makeroot($line['category']);

		echo "<tr><td class=header>";

		$cats = array();
		foreach($root as $category)
			$cats[] = "$category[name]";

		echo implode(" > ",$cats);

		echo "</td><td class=header>Author: <a class=header href=profile.php?uid=$line[authorid]>$line[author]</a></td></tr>";
		if($mods->isAdmin($userData['userid'],'articles'))
			echo "<tr><td class=header colspan=2>Title: <a class=header href=adminarticle.php?action=edit&id=$line[id]>$line[title]</a></td></tr>";
		else
			echo "<tr><td class=header colspan=2>Title: $line[title]</td></tr>";

		echo "<tr><td class=body colspan=2>";
		echo nl2br(smilies(parseHTML($line['text']))) . "&nbsp;";
//		echo "$line[ntext]";
		echo "</td></tr>";
		echo "<tr><td class=header colspan=2><input class=body type=submit style=\"width:100px\" name=vote value=Accept><input class=body type=submit style=\"width:100px\" name=vote value=Deny></td></tr>";
		echo "</table></form>";

		incFooter();
		exit;
	}else{
		$db->prepare_query("SELECT id, submittime, authorid, author, title, category, LENGTH(text) as length FROM articles WHERE moded = 'n' ORDER BY submittime ASC");

		$rows = array();
		while($line = $db->fetchrow())
			$rows[] = $line;

		$categories = & new category( $db, "cats");

		incHeader();

		echo "<table align=center>";
		echo "<tr>";
		echo "<td class=header>#</td>";
		echo "<td class=header>ID</td>";
		echo "<td class=header>Title</td>";
		echo "<td class=header>Author</td>";
		echo "<td class=header>Date</td>";
		echo "<td class=header>Category</td>";
		echo "<td class=header>Length</td>";
		echo "</tr>";

		$classes = array('body2','body');
		$i=0;

		$num = 1;
		foreach($rows as $line){
			echo "<tr>";
			echo "<td class=" . $classes[$i = !$i] . ">$num</td>";
			echo "<td class=" . $classes[$i] . ">$line[id]</td>";

			echo "<td class=" . $classes[$i] . "><a class=body href=\"$_SERVER[PHP_SELF]?mode=" . MOD_ARTICLE . "&id=$line[id]\">$line[title]</a></td>";

			echo "<td class=" . $classes[$i] . ">";
			if($line['authorid'])
				echo "<a class=body href=profile.php?uid=$line[authorid]>$line[author]</a>";
			else
				echo "$line[author]";
			echo "</td>";

			echo "<td class=" . $classes[$i] . ">" . userdate("m/d/Y", $line['submittime']) . "</td>";
			echo "<td class=" . $classes[$i] . ">";

			$root = $categories->makeroot($line['category']);

			$cats = array();
			foreach($root as $category)
				$cats[] = "$category[name]";

			echo implode(" > ",$cats);

			echo "</td>";
			echo "<td class=" . $classes[$i] . " align=right>$line[length]</td>";
			echo "</tr>\n";
			$num++;
		}
		echo "</table>";


		incFooter();
		exit;
	}
}

