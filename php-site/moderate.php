<?

	$login=1;

	set_time_limit(300);

	require_once("include/general.lib.php");

	if(!$mods->isMod($userData['userid']))
		die("Permission denied");

	if(!isset($mode))
		$mode = "";

	if(empty($id))
		$id = 0;

	if(isset($vote)){
//		echo "mode: $mode<br>";

		switch($mode){
			case MOD_PICS:
			case MOD_PICABUSE:
			case MOD_SIGNPICS:
			case MOD_FORUMRANK:
			case MOD_FORUMPOST:
			case MOD_GALLERY:
			case MOD_GALLERYABUSE:
			case MOD_USERABUSE:
//			case MOD_QUESTIONABLEPICS:
//		print_r($checkID);


				if(isset($checkID) && is_array($checkID))
					$mods->vote($checkID,$mode);
				break;

			case MOD_ARTICLE:
			case MOD_BANNER:
			case MOD_POLL:

//				echo "vote: $vote, id: $id";

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
		case MOD_GALLERY:			if(isset($moditemcounts[MOD_GALLERY]))			displayGallery();			break;
		case MOD_GALLERYABUSE:		if(isset($moditemcounts[MOD_GALLERYABUSE]))		displayGalleryAbuse();		break;
		case MOD_USERABUSE:			if(isset($moditemcounts[MOD_USERABUSE]))		displayUserAbuse();			break;
		case MOD_BANNER:			if(isset($moditemcounts[MOD_BANNER]))			displayBanners();			break;
		case MOD_ARTICLE:			if(isset($moditemcounts[MOD_ARTICLE]))			displayArticles($id);		break;
		case MOD_POLL:				if(isset($moditemcounts[MOD_POLL]))				displayPolls($id);			break;
	}
	displayTypes($moditemcounts); // exit

///////////////

function displayTypes($moditemcounts){
	global $PHP_SELF, $mods;

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
			echo "<tr><td class=body><a class=body href=$PHP_SELF?mode=$type>" . $mods->modtypes[$type] . "</a></td><td class=body>$num</td></tr>";
		echo "</table>";
	}

	incFooter();
	exit;
}

function displayPic(){
	global $PHP_SELF, $config, $db, $userData, $mods;

	$lvl = $mods->getModLvl($userData['userid'], MOD_PICS);

	$prefs = $mods->getModPrefs($userData['userid'], MOD_PICS);

	$ids = $mods->getModItems(MOD_PICS, $prefs['picsperpage'], 6);

	if(!count($ids))
		return;

	$rows = array();
	$db->prepare_query("SELECT picspending.id, users.userid, username, age,sex,description FROM users,picspending WHERE users.userid=picspending.itemid && picspending.id IN (?)", $ids);

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

	echo "<form action=\"$PHP_SELF\" method=post>";
	echo "<input type=hidden name=action value=vote>";
	echo "<input type=hidden name=mode value=" . MOD_PICS . ">";

	echo "<table cellpadding=3>";

	$picloc = $config['picloc'];

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

function displayPicAbuse(){
	global $PHP_SELF, $config, $db, $userData, $mods;

	$lvl = $mods->getModLvl($userData['userid'], MOD_PICABUSE);

	$prefs = $mods->getModPrefs($userData['userid'], MOD_PICABUSE);

	$ids = $mods->getModItems(MOD_PICABUSE, $prefs['picsperpage'], 20);

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

	$db->prepare_query("SELECT abuse.itemid, abuse.userid,username,reason FROM abuse,users WHERE users.userid=abuse.userid && abuse.type = ? && abuse.itemid IN (?)", MOD_PICABUSE, array_keys($rows));

	$abuses = array();
	while($line = $db->fetchrow())
		$abuses[$line['itemid']][] = $line;

	incHeader();

	echo "<form action=\"$PHP_SELF\" method=post>";
	echo "<input type=hidden name=action value=vote>";
	echo "<input type=hidden name=mode value=" . MOD_PICABUSE . ">";

	echo "<table cellpadding=3>";

	$picloc = $config['picloc'];

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

function displayQuestionablePics(){
	global $PHP_SELF, $config, $db, $userData, $mods;

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

	echo "<form action=\"$PHP_SELF\" method=post>";
	echo "<input type=hidden name=action value=vote>";
	echo "<input type=hidden name=mode value=" . MOD_QUESTIONABLEPICS . ">";

	echo "<table cellpadding=3>";

	$picloc = $config['picloc'];

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
	global $PHP_SELF,$config,$db, $userData, $mods;

	$lvl = $mods->getModLvl($userData['userid'], MOD_SIGNPICS);

	$prefs = $mods->getModPrefs($userData['userid'], MOD_SIGNPICS);

	$ids = $mods->getModItems(MOD_SIGNPICS, $prefs['picsperpage'], 6);

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

	echo "<form action=\"$PHP_SELF\" method=post>";
	echo "<input type=hidden name=action value=vote>";
	echo "<input type=hidden name=mode value=" . MOD_SIGNPICS . ">";

	echo "<table cellpadding=3>";

	$picloc = $config['picloc'];

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
	global $PHP_SELF,$config,$db, $userData, $mods;

	$lvl = $mods->getModLvl($userData['userid'], MOD_GALLERY);

	$prefs = $mods->getModPrefs($userData['userid'], MOD_GALLERY);

	$ids = $mods->getModItems(MOD_GALLERY, $prefs['picsperpage'], 6);

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

	echo "<form action=\"$PHP_SELF\" method=post>";
	echo "<input type=hidden name=action value=vote>";
	echo "<input type=hidden name=mode value=" . MOD_GALLERY . ">";

	echo "<table cellpadding=3>";

	$picloc = $config['gallerypicloc'];

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
	global $PHP_SELF,$config,$db, $userData, $mods;

	$lvl = $mods->getModLvl($userData['userid'], MOD_GALLERYABUSE);

	$prefs = $mods->getModPrefs($userData['userid'], MOD_GALLERYABUSE);

	$ids = $mods->getModItems(MOD_GALLERYABUSE, $prefs['picsperpage'], 6);

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


	$db->prepare_query("SELECT abuse.itemid, abuse.userid,username,reason FROM abuse,users WHERE users.userid=abuse.userid && abuse.type = ? && abuse.itemid IN (?)", MOD_GALLERYABUSE, array_keys($rows));

	$abuses = array();
	while($line = $db->fetchrow())
		$abuses[$line['itemid']][] = $line;

	incHeader();

	echo "<form action=\"$PHP_SELF\" method=post>";
	echo "<input type=hidden name=action value=vote>";
	echo "<input type=hidden name=mode value=" . MOD_GALLERYABUSE . ">";

	echo "<table cellpadding=3>";

	$picloc = $config['gallerypicloc'];

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

function displayUserAbuse(){
	global $PHP_SELF,$config,$db, $userData, $mods;

	$lvl = $mods->getModLvl($userData['userid'], MOD_USERABUSE);

	$prefs = $mods->getModPrefs($userData['userid'], MOD_USERABUSE);

	$ids = $mods->getModItems(MOD_USERABUSE, $prefs['picsperpage'], 20);

	if(!count($ids))
		return;

	$rows = array();
	$db->prepare_query("SELECT userid as id,username,age,sex FROM users WHERE userid IN (?)", $ids);

	while($line = $db->fetchrow()){
		$rows[$line['id']] = $line;
		unset($ids[$line['id']]);
	}

	if(count($ids))
		$mods->deleteItem(MOD_USERABUSE, $ids);

	if(!count($rows))
		return;

	$db->prepare_query("SELECT abuse.itemid, abuse.userid,username,reason FROM abuse,users WHERE users.userid=abuse.userid && abuse.type = ? && abuse.itemid IN (?)", MOD_USERABUSE, array_keys($rows));

	$abuses = array();
	while($line = $db->fetchrow())
		$abuses[$line['itemid']][] = $line;

	incHeader();

	echo "<form action=\"$PHP_SELF\" method=post>";
	echo "<input type=hidden name=action value=vote>";
	echo "<input type=hidden name=mode value=" . MOD_USERABUSE . ">";

	echo "<table cellpadding=3>";

	foreach($rows as $line){
		if($line['sex']=='Female') 	$bgcolor = '#FFAAAA';
		else						$bgcolor = '#AAAAFF';

		echo "<tr>";
		echo "<td class=body valign=top style=\"background-color: $bgcolor\">";
		echo "Username: <a class=body href=profile.php?uid=$line[id] target=_new>$line[username]</a><br>";
		echo "Age: $line[age]<br>";
		echo "Sex: <b>$line[sex]</b><br><br>";
		echo "<input type=checkbox name=checkID[$line[id]] id=checkidy$line[id] value=y><label for=\"checkidy$line[id]\" class=side><b>Done</b></label>";
		echo "</td></tr>";

		if(isset($abuses[$line['id']])){
			echo "<tr><td class=header>Abuse Reports</tr></tr>";
			echo "<tr><td class=body>";

			echo "<table border=0 width=100%>";
			foreach($abuses[$line['id']] as $abuse){
				echo "<tr><td class=header>";
				echo "<a class=header href=profile.php?uid=$abuse[userid]>$abuse[username]</a>";
				echo "</td><td class=body>$abuse[reason]</td></tr>";
			}
			echo "</table>";
			echo "</td></tr>";
		}

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

function displayBanners(){
	global $PHP_SELF,$config,$db, $userData, $mods;

	$lvl = $mods->getModLvl($userData['userid'], MOD_BANNER);

	$prefs = $mods->getModPrefs($userData['userid'], MOD_BANNER);

	$ids = $mods->getModItems(MOD_BANNER, 1, 20);

	$id = current($ids);

	incHeader();

	echo "<form action=\"$PHP_SELF\" method=post>";
	echo "<table>";
	echo "<input type=hidden name=mode value=" . MOD_BANNER . ">";
	echo "<input type=hidden name=id value=$id>";

	echo "<tr><td class=body>";
	echo banner($id);
	echo "</td></tr>";

	echo "<tr><td class=header><input class=body type=submit style=\"width:100px\" name=vote value=Accept><input class=body type=submit style=\"width:100px\" name=vote value=Deny></td></tr>";
	echo "</table></form>";

	incFooter();
	exit;
}

function displayForumRanks(){
	global $PHP_SELF,$config,$db, $userData, $mods;

	$lvl = $mods->getModLvl($userData['userid'], MOD_FORUMRANK);

	$prefs = $mods->getModPrefs($userData['userid'], MOD_FORUMRANK);

	$ids = $mods->getModItems(MOD_FORUMRANK, $prefs['picsperpage'], 20);

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

	echo "<form action=\"$PHP_SELF\" method=post>";
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
	global $PHP_SELF,$config,$db, $userData, $mods;

	$lvl = $mods->getModLvl($userData['userid'], MOD_FORUMPOST);

	$prefs = $mods->getModPrefs($userData['userid'], MOD_FORUMPOST);

	$ids = $mods->getModItems(MOD_FORUMPOST, $prefs['picsperpage'], 20);

	if(!count($ids))
		return;

	$rows = array();
	$db->prepare_query("SELECT forumposts.id, threadid, title, nmsg, forumposts.author, forumposts.authorid, forumposts.time FROM forumposts, forumthreads WHERE forumposts.threadid=forumthreads.id && forumposts.id IN (?)", $ids);

	while($line = $db->fetchrow()){
		$rows[$line['id']] = $line;
		unset($ids[$line['id']]);
	}

	if(count($ids))
		$mods->deleteItem(MOD_FORUMPOST, $ids);

	if(!count($rows))
		return;

	$db->prepare_query("SELECT abuse.itemid, abuse.userid,username,reason FROM abuse,users WHERE users.userid=abuse.userid && abuse.type = ? && abuse.itemid IN (?)", MOD_FORUMPOST, array_keys($rows));

	$abuses = array();
	while($line = $db->fetchrow())
		$abuses[$line['itemid']][] = $line;

	incHeader();

	echo "<form action=\"$PHP_SELF\" method=post>";
	echo "<input type=hidden name=action value=vote>";
	echo "<input type=hidden name=mode value=" . MOD_FORUMPOST . ">";

	echo "<table cellpadding=3>";

	$db->prepare_query("SELECT forumpostsperpage FROM users WHERE userid = ?", $userData['userid']);
	$postsPerPage = $db->fetchfield();

	foreach($rows as $line){
		$db->prepare_query("SELECT count(*) FROM forumposts WHERE id < ? && threadid = ?", $line['id'], $line['threadid']);
		$postnum = $db->fetchfield();

		$page = floor($postnum / $postsPerPage);

		echo "<tr><td class=header>Thread title</td><td class=header><a class=header target=_new href=forumviewthread.php?tid=$line[threadid]&page=$page>$line[title]</a></td></tr>";
		echo "<tr><td class=header>Post Author</td><td class=header><a class=header target=_new href=profile.php?uid=$line[authorid]>$line[author]</a></td></tr>";
		echo "<tr><td class=header>Post Date</td><td class=header>" . userdate("l F j, Y, g:i a",$line['time']) . "</td></tr>";
		echo "<tr><td class=body colspan=2>$line[nmsg]</td></tr>";

		if(isset($abuses[$line['id']])){
			echo "<tr><td class=header colspan=2>Abuse Reports</tr></tr>";
			echo "<tr><td class=body colspan=2>";

			echo "<table border=0 width=100%>";
			foreach($abuses[$line['id']] as $abuse){
				echo "<tr><td class=header>";
				echo "<a class=header href=profile.php?uid=$abuse[userid]>$abuse[username]</a>";
				echo "</td><td class=body>$abuse[reason]</td></tr>";
			}
			echo "</table>";
			echo "</td></tr>";
		}

		echo "<tr><td class=body></td><td class=body>";
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
	global $PHP_SELF,$userData,$db, $mods;

	if($id){

		$poll = getPoll($id, false);

		incHeader();

		echo "<form action=\"$PHP_SELF\">";
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
		$db->prepare_query("SELECT id, question FROM polls WHERE official = 'y' && moded = 'n' ORDER BY date ASC");

		$rows = array();
		while($line = $db->fetchrow())
			$rows[] = $line;

		incHeader();

		echo "<table align=center>";
		echo "<tr><td class=header>Question</td></tr>";

		foreach($rows as $line){
			echo "<tr>";
			echo "<td class=body><a class=body href=\"$PHP_SELF?mode=" . MOD_POLL . "&id=$line[id]\">$line[question]</a></td>";
			echo "</tr>\n";
		}
		echo "</table>";

		incFooter();
		exit;
	}
}

function displayArticles($id){
	global $PHP_SELF,$userData,$db, $mods;

	if($id){
		$db->prepare_query("SELECT * FROM articles WHERE id = ?", $id);
		$line = $db->fetchrow();

		incHeader();

		echo "<form action=\"$PHP_SELF\">";
		echo "<table align=center>";
		echo "<input type=hidden name=mode value=" . MOD_ARTICLE . ">";
		echo "<input type=hidden name=id value=$id>";


		$cats = & new category("cats");
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

		echo "<tr><td class=body colspan=2>$line[ntext]</td></tr>";
		echo "<tr><td class=header colspan=2><input class=body type=submit style=\"width:100px\" name=vote value=Accept><input class=body type=submit style=\"width:100px\" name=vote value=Deny></td></tr>";
		echo "</table></form>";

		incFooter();
		exit;
	}else{
		$db->prepare_query("SELECT id, submittime, authorid, author, title, category FROM articles WHERE moded = 'n' ORDER BY submittime ASC");

		$rows = array();
		while($line = $db->fetchrow())
			$rows[] = $line;

		$categories = & new category("cats");

		incHeader();

		echo "<table align=center>";
		echo "<tr><td class=header>Title</td><td class=header>Author</td><td class=header>Date</td><td class=header>Category</td></tr>";

		foreach($rows as $line){
			echo "<tr>";

			echo "<td class=body><a class=body href=\"$PHP_SELF?mode=" . MOD_ARTICLE . "&id=$line[id]\">$line[title]</a></td>";

			echo "<td class=body>";
			if($line['authorid'])
				echo "<a class=body href=profile.php?uid=$line[authorid]>$line[author]</a>";
			else
				echo "$line[author]";
			echo "</td>";

			echo "<td class=body>" . userdate("m/d/Y", $line['submittime']) . "</td>";
			echo "<td class=body>";

			$root = $categories->makeroot($line['category']);

			$cats = array();
			foreach($root as $category)
				$cats[] = "$category[name]";

			echo implode(" > ",$cats);

			echo "</td>";
			echo "</tr>\n";
		}
		echo "</table>";


		incFooter();
		exit;
	}
}
/*
function displayArticle($id){
	global $PHP_SELF,$userData,$db, $mods;

	$lvl = $mods->getModLvl($userData['userid'], MOD_ARTICLE);

	$prefs = $mods->getModPrefs($userData['userid'], MOD_ARTICLE);

	$ids = $mods->getModItems(MOD_ARTICLE, 1, 1);

	$id = current($ids);


	$db->prepare_query("SELECT * FROM articles WHERE id = ?", $id);
	$line = $db->fetchrow();

	incHeader();

	echo "<form action=\"$PHP_SELF\" method=post>";
	echo "<table>";
	echo "<input type=hidden name=mode value=" . MOD_ARTICLE . ">";
	echo "<input type=hidden name=id value=$id>";


	$cats = & new category("cats");
	$root = $cats->makeroot($line['category']);

	echo "<tr><td class=header>";

	foreach($root as $cat)
		echo "<a class=header href=articlelist.php?cat=$cat[id]>$cat[name]</a> > ";

	echo "</td><td class=header>Author: <a class=header href=profile.php?uid=$line[authorid]>$line[author]</a></td></tr>";
	if($mods->isAdmin($userData['userid'],'articles'))
		echo "<tr><td class=header colspan=2>Title: <a class=header href=adminarticle.php?action=edit&id=$line[id]>$line[title]</a></td></tr>";
	else
		echo "<tr><td class=header colspan=2>Title: $line[title]</td></tr>";

	echo "<tr><td class=body colspan=2>" . nl2br(parseHTML(smilies($line['text']))) . "</td></tr>";
	echo "<tr><td class=header colspan=2><input class=body type=submit style=\"width:100px\" name=vote value=Accept><input class=body type=submit style=\"width:100px\" name=vote value=Deny></td></tr>";
	echo "</table></form>";

	incFooter();
	exit;
}
*/
