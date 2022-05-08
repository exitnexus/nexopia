<?

	$login=1;

	require_once("include/general.lib.php");

	$isAdmin = $mods->isAdmin($userData['userid'],'editpictures');

	if(!isset($uid) || !$isAdmin)
		$uid = $userData['userid'];

	$db->prepare_query("SELECT firstpic, signpic FROM users WHERE userid = ?", $uid);
	list($userfirstpic, $usersignpic) = $db->fetchrow(0,MYSQL_NUM);

	if($uid == $userData['userid']){
		$maxpics = ($userData['premium'] ? $config['maxpicspremium'] : $config['maxpics']);
		if($usersignpic == 'y')
			$maxpics++;
	}


	switch($action){
		case "moveup":
			if($userData['userid']==$uid)
				increasepriority($id,"pics",$db->prepare("itemid = ?",$uid));
			setFirstPic($uid);
			break;
		case "movedown":
			if($userData['userid']==$uid)
				decreasepriority($id,"pics",$db->prepare("itemid = ?", $uid));
			setFirstPic($uid);
			break;
		case "deletepic":
			$db->prepare_query("SELECT itemid,signpic FROM pics WHERE id = ?", $id);
			if($db->numrows()){
				$line = $db->fetchrow();

				if($line['itemid']==$uid)
					removePic($id);

				setFirstPic($uid);
				if($uid != $userData['userid'])
					$mods->adminlog("delete picture", "Delete user picture: userid $uid");
			}
			break;
		case "deletepending":
			$db->prepare_query("SELECT itemid FROM picspending WHERE id = ?", $id);
			if($db->numrows()){
				$line = $db->fetchrow();

				if($line['itemid']==$uid)
					removePicPending($id);

				setFirstPic($uid);
				if($uid != $userData['userid'])
					$mods->adminlog("delete pending picture", "Delete pending user picture: userid $uid");
			}
			break;
		case "Upload":
			if($uid == $userData['userid']){
				$db->prepare_query("SELECT count(*) FROM pics WHERE itemid = ?", $userData['userid']);
				$num = $db->fetchfield();
				$db->prepare_query("SELECT count(*) FROM picspending WHERE itemid = ?", $userData['userid']);
				$num += $db->fetchfield();

				if($num < $maxpics) //premium check
					addPic($userfile,(isset($vote) ? 'y' : 'n'),$description, (isset($signpic)));
			}
			break;
		case "edit":
			if($uid != $userData['userid'])
				$mods->adminlog("edit picture", "Edit user picture: userid $uid");
			edit($id); //exit
		case "editpending":
			if($uid != $userData['userid'])
				$mods->adminlog("edit pending picture", "Edit pending user picture: userid $uid");
			editPending($id); //exit
		case "Update":
			if($uid != $userData['userid'])
				$mods->adminlog("update picture", "Update user pictures: userid $uid");
			update($id,(isset($vote) ? 'y' : 'n'),$description);
			break;
		case "updatepending":
			if($uid != $userData['userid'])
				$mods->adminlog("update pending picture", "Update Pending user picture: userid $uid");
			updatePending($id,(isset($vote) ? 'y' : 'n'),$description);
			break;
		case "Reset Votes":
			if($uid != $userData['userid'])
				$mods->adminlog("reset picture votes", "Reset votes on picture $id, userid $uid");
			if($uid != $userData['userid'] || $userData['premium']){
				$db->prepare_query("UPDATE pics SET v1=0, v2=0, v3=0, v4=0, v5=0, v6=0, v7=0, v8=0, v9=0, v10=0, votes=0, score=0 WHERE id = ? && itemid = ?", $id, $uid);
				$msgs->addMsg("Votes Reset");
			}
		case "Unverify Picture":
			if($uid == $userData['userid'])
				break;
			$mods->adminlog("unverify picture", "Unverify picture $id from userid $uid");
			$db->prepare_query("UPDATE pics SET signpic = 'n' WHERE id = ? && itemid = ?", $id, $uid);

			break;
	}

	listPictures();

/////////////////////////////

function listPictures(){
	global $db, $PHP_SELF, $uid, $userData, $config, $action, $maxpics, $usersignpic, $userfirstpic;

	incHeader();

	$numpics = 0;
	$signpic = 'n';
	$firstpic = 0;

	if($action != "" ) $db->begin();
	$db->prepare_query("SELECT id,vote,description,votes,score,priority,signpic FROM pics WHERE itemid = ? ORDER BY priority", $uid);
	if($action != "" ) $db->commit();

	if($db->numrows()){
		$numpics += $db->numrows();

		echo "<table width=100%>";

		echo "<tr><td class=header>Approved Pictures</td><td class=header>Description</td>";
		if($config['votingenabled'])
			echo "<td class=header>Votes</td><td class=header>Score</td>";
		echo "<td class=header>Sign Pic</td><td class=header>Functions</td></tr>";

		$i=1;
		$ids = array();
		while($line = $db->fetchrow()){
			if($line['priority'] != $i){
				$ids[$line['id']] = $i;
				$line['priority'] = $i;
			}

			if($i == 1)
				$firstpic = $line['id'];

			$i++;
			if($line['signpic'] == 'y')
				$signpic = 'y';

			echo "<tr>";
			echo "<td class=body><a href=profile.php?picid=$line[id]><img src=$config[thumbloc]" . floor($line['id']/1000) . "/$line[id].jpg border=0></a></td>";
			echo "<td class=body>$line[description]</td>";
			if($config['votingenabled']){
				if($line['vote']=='n'){
					$score="N/A";
					$line['votes']="N/A";
				}else{
					if($line['score'] == 0)
						$score = "N/A";
					else
						$score = scoreCurve($line['score']);
				}
				echo "<td class=body>$line[votes]</td><td class=body>$score</td>";
			}
			echo "<td class=body>" . ($line['signpic'] == 'y' ? "Sign Pic" : "") . "</td>";

			echo "<td class=body><a class=body href=\"$PHP_SELF?uid=$uid&action=edit&id=$line[id]\"><img src=$config[imageloc]edit.gif border=0 alt='Edit'></a>";
			if($userData['userid']==$uid){
				echo " <a class=body href=\"$PHP_SELF?action=moveup&id=$line[id]\"><img src=$config[imageloc]up.png border=0 alt='Move Up'></a>";
				echo " <a class=body href=\"$PHP_SELF?action=movedown&id=$line[id]\"><img src=$config[imageloc]down.png border=0 alt='Move Down'></a>";
			}
			echo " <a class=body href=\"javascript:confirmLink('$PHP_SELF?uid=$uid&action=deletepic&id=$line[id]','delete this picture')\"><img src=$config[imageloc]delete.gif border=0 alt='Delete'></a></td>";

			echo "</tr>";
		}
		echo "</table><br><br>";

		if(count($ids)){
			foreach($ids as $id => $i)
				$db->prepare_query("UPDATE pics SET priority = ? WHERE id = ?", $i, $id);
		}
	}

	if($signpic != $usersignpic || $firstpic != $userfirstpic)
		$db->prepare_query("UPDATE users SET signpic = ?, firstpic = ? WHERE userid = ?", $signpic, $firstpic, $uid);


	if($action != "" ) $db->begin(); //ask the main db server, otherwise new pics might not show up till a refresh.

	$db->prepare_query("SELECT id,vote,description FROM picspending WHERE itemid = ? ORDER BY id", $uid);

	if($action != "" ) $db->commit();

	if($db->numrows()){
		$numpics += $db->numrows();
		echo "<table width=100%>";
		echo "<tr><td class=body colspan=3>Pictures must be moderated for content before they will show up on your profile. These pictures are currently waiting to be moderated. The moderation process takes anywhere between a few minutes and several hours.</td></tr>";
		echo "<tr><td class=header>Pictures Pending Approval</td><td class=header>Description</td>";
		echo "<td class=header>Functions</td></tr>";

		while($line = $db->fetchrow()){
			echo "<tr>";
			echo "<td class=body><img src=$config[thumbloc]" . floor($line['id']/1000) . "/$line[id].jpg border=0></td>";
			echo "<td class=body>$line[description]</td>";

			echo "<td class=body><a class=body href=\"$PHP_SELF?uid=$uid&action=editpending&id=$line[id]\"><img src=$config[imageloc]edit.gif border=0 alt='Edit'></a>";
			echo " <a class=body href=\"javascript:confirmLink('$PHP_SELF?uid=$uid&action=deletepending&id=$line[id]','delete this picture')\"><img src=$config[imageloc]delete.gif border=0 alt='Delete'></a></td>";

			echo "</tr>";
		}
		echo "</table><br><br>";
	}

	if($userData['userid']==$uid){
		if($numpics < $maxpics){
			echo "<table align=center>";

			echo "<tr><td class=header colspan=2>Upload a new Picture</td></tr>";

			echo "<form action=\"$PHP_SELF\" method=post enctype=\"multipart/form-data\">\n";
			echo "<tr><td class=body>Select a Picture:</td><td class=body><input class=body type=file name=userfile size=20></td></tr>";
			echo "<tr><td class=body>Enter a Description</td><td class=body><input class=body type=text name=description maxlength=64 size=30></td></tr>";

			if($config['votingenabled'])
				echo "<tr><td class=body colspan=2>" . makeCheckBox('vote', "Allow users to vote for this picture") . "</td></tr>";
			echo "<tr><td class=body colspan=2>" . makeCheckBox('signpic', "Verify that this is a sign picture") . "</td></tr>";
			echo "<tr><td class=body colspan=2>";
			echo "<b>A sign picture is a picture with you holding a sign with your <u>username and Nexopia</u> written on it.<br>";
			echo "Once your sign picture is verified, you may upload one extra picture.</b><br>";
			echo "Uploaded pictures must follow the rules in the FAQ. Essentially it must be a recognizable picture of you.<br>";
			echo "All pictures will be moderated, and won't show up on your profile until then.";
			echo "</td></tr>";
			echo "<tr><td class=body></td><td><input class=body type=submit name=action value=Upload></td></tr>\n";
			echo "</form>";

			echo "</table>";
		}
	}
	incFooter();
}


function edit($id){
	global $uid,$config,$isAdmin, $db, $PHP_SELF, $userData;

	$query = $db->prepare("SELECT vote,description,v1,v2,v3,v4,v5,v6,v7,v8,v9,v10, (v1+v2+v3+v4+v5+v6+v7+v8+v9+v10) as sum, signpic FROM pics WHERE id = ? && itemid = ?", $id, $uid);
	$db->query($query);
	$data = $db->fetchrow();

	incHeader();

	echo "<table align=center>\n";
	echo "<form action=\"$PHP_SELF\" method=post>\n";

	echo "<tr><td class=body><img src=\"$config[picloc]" . floor($id/1000) . "/$id.jpg\"></td><td class=body valign=top>";

	if($config['votingenabled']){
		echo "<table width=250><tr><td class=header colspan=2>Vote Histograph</td></tr>";

		$maxval=0;
		for($i=1;$i<=10;$i++)
			if($data["v$i"]!=0 && $data["v$i"]/$data['sum'] > $maxval)
				$maxval = $data["v$i"]/$data['sum'];

		if($maxval==0)	$width=0;
		else			$width=150/$maxval;

		echo "<tr><td class=body>Score</td><td class=body>Number of Votes</td></tr>";
		for($i=1;$i<=10;$i++)
			echo "<tr><td class=body>$i</td><td class=body><img src=$config[imageloc]red.png width=" . ($data['sum']==0 ? 0 : $data["v$i"]/$data['sum']*$width) . " height=10> " . ($data["v$i"]) . "</td></tr>";
		echo "</table></td></tr>";
	}

	echo "</table>";
	echo "<table align=center>";
	echo "<input type=hidden name=id value=$id>";
	echo "<input type=hidden name=uid value=$uid>";

	if($config['votingenabled'])
		echo "<tr><td class=body>Enable Voting:</td><td class=body><input type=checkbox name=vote" . ($data['vote']=='y'? " checked" : "") ."></td>";

	echo "<tr><td class=body>Description:</td><td class=body><input class=body type=text name=description value=\"" . htmlentities($data['description']) . "\" size=40 maxlength=64></td></tr>";
	echo "<tr><td class=body></td><td class=body>";
	echo "<input class=body type=submit name=action value=Update>";
	echo "<input class=body type=submit name=action value=Cancel>";
	if($config['votingenabled'] && $userData['premium'])
		echo "<input class=body type=submit name=action value='Reset Votes'>";
	if($isAdmin && $data['signpic'] == 'y')
		echo "<input class=body type=submit name=action value='Unverify Picture'>";

	echo "</td></tr>\n";

	echo "</table>\n";

	incFooter();
	exit;
}

function update($id,$vote,$description){
	global $uid,$msgs,$isAdmin, $db;

	$db->prepare_query("UPDATE pics SET vote = ?,description = ? WHERE id = ? && itemid = ?", $vote, removeHTML($description), $id, $uid);

	$msgs->addMsg("Update Complete");
	return;
}


function editPending($id){
	global $uid,$config,$isAdmin, $db, $PHP_SELF;

	$query = $db->prepare("SELECT vote,description FROM picspending WHERE id = ? && itemid = ?", $id, $uid);
	$db->query($query);
	$data = $db->fetchrow();

	incHeader();

	echo "<table>\n";
	echo "<form action=\"$PHP_SELF\" method=post>\n";
	echo "<tr><td class=body colspan=2><img src=\"$config[picloc]" . floor($id/1000) . "/$id.jpg\"></td></tr>";

	echo "<input type=hidden name=action value=updatepending>";
	echo "<input type=hidden name=id value=$id>";
	echo "<input type=hidden name=uid value=$uid>";

	if($config['votingenabled'])
		echo "<tr><td class=body>Enable Voting:</td><td class=body><input type=checkbox name=vote" . ($data['vote']=='y'? " checked" : "") ."></td>";

	echo "<tr><td class=body>Description:</td><td class=body><input class=body type=text name=description value=\"" . htmlentities($data['description']) . "\" size=40 maxlength=64></td></tr>";
	echo "<tr><td class=body></td><td class=body><input class=body type=submit value=Update></td></tr>\n";

	echo "</table>\n";

	incFooter();
	exit;
}

function updatePending($id,$vote,$description){
	global $uid,$msgs,$isAdmin, $db;

	$db->prepare_query("UPDATE picspending SET vote = ?,description = ? WHERE id = ? && itemid = ?", $vote, removeHTML($description), $id, $uid);

	$msgs->addMsg("Update Complete");
	return;
}
