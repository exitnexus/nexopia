<?

function createMsgFolder($name, $uid = 0){
	global $userData,$msgs,$db;

	if(!$uid)
		$uid = $userData['userid'];

	$db->prepare_query("INSERT INTO msgfolder SET userid = ?, name = ?", $uid, $name);

	$msgs->addMsg("Folder Created");
}

function deleteMsgFolder($id, $uid = 0){
	global $userData,$msgs,$db;

	if(!$uid)
		$uid = $userData['userid'];

	$db->prepare_query("SELECT id FROM msgs WHERE folder IN (?) && userid = ?", $id, $uid);

	$msgids = array();
	while($line = $db->fetchrow())
		$msgids[] = $line['id'];

	moveMsg($msgids, MSG_TRASH, $uid);

	$db->prepare_query("DELETE FROM msgfolder WHERE userid = ? && id IN (?)", $uid, $id);

	$msgs->addMsg("Folder(s) deleted");
}

function renameMsgFolder($id,$name, $uid = 0){
	global $userData,$msgs,$db;

	if(!$uid)
		$uid = $userData['userid'];

	$db->prepare_query("UPDATE msgfolder SET name = ? WHERE userid = ? && id = ?", $name, $uid, $id);

	$msgs->addMsg("Folder renamed");
}

function getMsgFolders($uid = 0){
	global $userData,$db;

	static $folders;

	if(!$uid)
		$uid = $userData['userid'];

	if(isset($folders[$uid]))
		return $folders[$uid];

	$db->prepare_query("SELECT id,name FROM msgfolder WHERE userid = ? ORDER BY name", $uid);


	$folders[$uid] = array(MSG_INBOX => "Inbox", MSG_SENT => "Sent Items", MSG_TRASH => "Trash");
	while($line = $db->fetchrow())
		$folders[$uid][$line['id']] = $line['name'];

	return $folders[$uid];
}

function deleteMsg($checkID, $uid = 0){
	global $msgs,$userData,$db;

	if(!isset($checkID) || !is_array($checkID) || count($checkID)==0)
		return;

	if(!$uid)
		$uid = $userData['userid'];

	moveMsg($checkID, MSG_TRASH, $uid);
}

function moveMsg($checkID,$moveto, $uid = 0){
	global $userData,$msgs,$db;

	if(!isset($checkID) || !is_array($checkID) || count($checkID)==0)
		return;

	if(!$uid)
		$uid = $userData['userid'];

	if($moveto <= 0)
		return;

	if($moveto >= 4){
		$db->prepare_query("SELECT id FROM msgfolder WHERE id = ? && userid = ?", $moveto, $uid);
		if($db->numrows()==0){
			$msgs->addMsg("Folder doesn't exist");
			return;
		}
	}

	if($moveto == MSG_TRASH){
		$db->prepare_query("UPDATE msgs,msgheader SET msgheader.new = 'n' WHERE msgs.userid = ? && msgs.msgheaderid=msgheader.id && msgheader.to = ? && msgheader.new = 'y' && msgs.id IN (?)", $uid, $uid, $checkID);

		$num = $db->affectedrows();

		if($num > 0){
			$db->prepare_query("UPDATE users SET newmsgs = newmsgs - ? WHERE userid = ?", $num, $uid);

			if($uid == $userData['userid'])
				$userData['newmsgs'] -= $num;
		}
	}

	$db->prepare_query("UPDATE msgs SET folder = ? WHERE userid = ? && id IN (?)", $moveto, $uid, $checkID);

	$msgs->addMsg("Message(s) Moved");
	return;
}

function markMsg($checkID, $uid = 0){
	global $userData,$msgs,$db;

	if(!isset($checkID) || !is_array($checkID) || count($checkID)==0)
		return;

	if(!$uid)
		$uid = $userData['userid'];

	$db->prepare_query("UPDATE msgs SET mark = 'y' WHERE userid = ? && id IN (?)", $userData['userid'],  $checkID);

	$msgs->addMsg("Message Marked");
	return;
}

function unMarkMsg($checkID, $uid = 0){
	global $userData,$msgs,$db;

	if(!isset($checkID) || !is_array($checkID) || count($checkID)==0)
		return;

	if(!$uid)
		$uid = $userData['userid'];

	$db->prepare_query("UPDATE msgs SET mark = 'n' WHERE userid = ? && id IN (?)", $userData['userid'],  $checkID);

	$msgs->addMsg("Message UnMarked");
	return;
}

function setNumNewMsgs($uid = 0){
	global $userData,$db;

	if(!$uid)
		$uid = $userData['userid'];

	$db->prepare_query("SELECT count(*) FROM msgs,msgheader WHERE msgs.userid = ? && msgs.msgheaderid=msgheader.id && msgheader.to = ? && new = 'y'", $uid, $uid);
	$numnew = $db->fetchfield();

	if($uid != $userData['userid'] || $numnew != $userData['newmsgs']){
		$db->prepare_query("UPDATE users SET newmsgs = ? WHERE userid = ?", $numnew, $uid);
	}
	if($uid == $userData['userid'])
		$userData['newmsgs'] = $numnew;
}

function deliverMsg($to, $subject, $message, $replyto = 0, $fromname = false, $fromid = false){
	global $userData,$msgs,$emaildomain,$config,$db;

	if($fromname === false)
		$fromname = $userData['username'];
	if($fromid === false)
		$fromid = $userData['userid'];

	$subject = trim($subject);

	$nsubject = removeHTML($subject);
	$nmsg = removeHTML(trim($message));

	$toname=getUserName($to);

	if($nsubject=="")
		$nsubject="No Subject";

	if($fromid && isIgnored($to,$fromid,'msgs')){
		$msgs->addMsg("Message Ignored");
		return false;
	}

	$hash = pack("H*",md5($nmsg));

	ignore_user_abort(true);

	$db->query("LOCK TABLES msgtext WRITE");

	$db->prepare_query("SELECT id FROM msgtext WHERE hash = ?", $hash);

	if($db->numrows()){
		$msgtextid = $db->fetchfield();
	}else{
		$db->prepare_query("INSERT INTO msgtext SET msg = ?, hash = ?", $nmsg, $hash);
		$msgtextid = $db->insertid();
	}

	$db->query("UNLOCK TABLES");

	$db->prepare_query("INSERT INTO msgheader SET `to` = ?, toname = ?, `from` = ?, fromname = ?, date = ?, new = 'y', subject = ?, msgtextid = ?, replyto = ?",
						$to, $toname, $fromid, $fromname, time(), $nsubject, $msgtextid, $replyto);

	$msgheaderid = $db->insertid();

	$db->prepare_query("INSERT INTO msgs SET userid = ?, msgheaderid = ?, other = ?, folder = ?, mark = 'n'", $to, $msgheaderid, $fromid, MSG_INBOX);

	if($fromid)
		$db->prepare_query("INSERT INTO msgs SET userid = ?, msgheaderid = ?, other = ?, folder = ?, mark = 'n'", $fromid, $msgheaderid, $to, MSG_SENT);

	if($replyto)
		$db->prepare_query("UPDATE msgheader SET replied='y' WHERE id = ?", $replyto);

	$db->prepare_query("UPDATE users SET newmsgs = newmsgs+1 WHERE userid = ?", $to);

	if($userData['loggedIn'] && $to == $userData['userid'])
		$userData['newmsgs']++;

	$db->prepare_query("SELECT fwmsgs,email FROM users WHERE userid = ?", $to);
	extract($db->fetchrow());

	if($fwmsgs=='y'){
		$nmsg2 = smilies($nmsg);
		$nmsg2 = parseHTML($nmsg2);

		smtpmail("$toname <$email>", $nsubject, $nmsg2 . "\n\n------Forwarded Offline Message From $config[title]-----\nThe return address is NOT valid", "From: $userData[username] on $config[title] <no-reply@$emaildomain>");
	}

	ignore_user_abort(false);

	return true;
}


function dumpMsgs($uids){
	global $db;

//	$db->prepare_query("INSERT IGNORE INTO msgdump SELECT msgs.id, msgs.userid, msgs.to, msgs.toname, msgs.from, msgs.fromname, msgs.date, msgs.subject, msgtext.msg FROM msgs,msgtext WHERE msgs.id=msgtext.id && msgs.userid IN (?)", $uids);

	$db->prepare_query("INSERT IGNORE INTO msgdump SELECT msgs.id, msgs.userid, msgheader.to, msgheader.toname, msgheader.from, msgheader.fromname, msgheader.date, msgheader.subject, msgtext.msg FROM msgsm,msgheader,msgtext WHERE msgheader.id=msgs.headerid && msgheader.textid=msgtext.id && msgs.userid IN (?)", $uids);
}

function viewDumpedMsgs($uids, $sortbyuser = false){
	global $db;

	$db->prepare_query("SELECT msgdump.*, IF(`to`=msgdump.userid, toname, fromname) AS username, IF(`to`=msgdump.userid, `from`, `to`) AS other, users.age, users.sex FROM msgdump LEFT JOIN users ON IF(`to`=msgdump.userid, `from`, `to`)=users.userid WHERE msgdump.userid IN (?) ORDER BY " . ($sortbyuser ? "other, " : "") . "date", $uids);

	$output = "dumping " . $db->numrows() . " messages<br>\n";

	while($line = $db->fetchrow()){
		$output .= "Msg id:  $line[id]\n";
		$output .= "User:    $line[username]\n";
		$output .= "Folder:  " . ($line['to'] == $line['userid'] ? "Inbox" : "Outbox") . "\n";
		$output .= "To:      $line[toname] ($line[to])" . ($line['to'] == $line['userid'] ? "" : " age: $line[age], sex: $line[sex]") . "\n";
		$output .= "From:    $line[fromname] ($line[from])" . ($line['from'] == $line['userid'] ? "" : " age: $line[age], sex: $line[sex]") . "\n";
		$output .= "Date:    " . userDate("D M j, Y g:i a", $line['date']) . "\n";
		$output .= "Subject: $line[subject]\n";
		$output .= "$line[msg]";

		$output .= "\n\n------------------------------------------------------------\n\n";
	}

	echo "<pre>$output</pre>";
}


