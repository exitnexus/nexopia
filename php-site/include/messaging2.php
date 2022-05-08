<?

define("MSG_INBOX",	1);
define("MSG_SENT",	2);
define("MSG_TRASH",	3);


class messaging{

	var $db;
	var $archivedb;
/*
tables:
 -msgs
 -msgheader
 -msgtext
 -msgfolder
*/

	function messaging( & $db, & $archivedb ){
		$this->db = & $db;
		$this->archivedb = & $archivedb;
	}

	function createMsgFolder($name, $uid = 0){
		global $userData, $msgs, $cache;

		if(!$uid)
			$uid = $userData['userid'];

		$this->db->prepare_query("INSERT INTO msgfolder SET userid = ?, name = ?", $uid, $name);

		$cache->remove(array($uid, "msgfolders-$uid"));

		$msgs->addMsg("Folder Created");
	}

	function deleteMsgFolder($id, $uid = 0){
		global $userData, $msgs, $cache;

		if(!$uid)
			$uid = $userData['userid'];

		$this->db->prepare_query("SELECT id FROM msgs WHERE folder IN (?) && userid = ?", $id, $uid);

		$msgids = array();
		while($line = $this->db->fetchrow())
			$msgids[] = $line['id'];

		$this->moveMsg($msgids, MSG_TRASH, $uid);

		$this->db->prepare_query("DELETE FROM msgfolder WHERE userid = ? && id IN (?)", $uid, $id);

		$cache->remove(array($uid, "msgfolders-$uid"));

		$msgs->addMsg("Folder(s) deleted");
	}

	function renameMsgFolder($id,$name, $uid = 0){
		global $userData, $msgs, $cache;

		if(!$uid)
			$uid = $userData['userid'];

		$this->db->prepare_query("UPDATE msgfolder SET name = ? WHERE userid = ? && id = ?", $name, $uid, $id);

		$cache->remove(array($uid, "msgfolders-$uid"));

		$msgs->addMsg("Folder renamed");
	}

	function getMsgFolders($uid = 0){
		global $userData, $cache, $config;

		static $folders;

		if(!$uid)
			$uid = $userData['userid'];

		if(!isset($folders[$uid])){

			$folders[$uid] = $cache->get(array($uid, "msgfolders-$uid"));

			if(!$folders[$uid]){
				$this->db->prepare_query("SELECT id,name FROM msgfolder WHERE userid = ?", $uid);


				$folders[$uid] = array(MSG_INBOX => "Inbox", MSG_SENT => "Sent Items", MSG_TRASH => "Trash");

				$temp = array();
				while($line = $this->db->fetchrow())
					$temp[$line['id']] = $line['name'];

				sortCols($temp, SORT_ASC, SORT_CASESTR, 'name');

				foreach($temp as $k => $v)
					$folders[$uid][$k] = $v;

				$cache->put(array($uid, "msgfolders-$uid"), $folders[$uid], 86400);
			}

		}
		return $folders[$uid];
	}

	function deleteMsg($checkID, $uid = 0){
		global $msgs, $userData;

		if(!isset($checkID) || !is_array($checkID) || count($checkID)==0)
			return;

		if(!$uid)
			$uid = $userData['userid'];

		$this->moveMsg($checkID, MSG_TRASH, $uid);
	}

	function moveMsg($checkID,$moveto, $uid = 0){
		global $userData, $msgs, $cache;

		if(!isset($checkID) || !is_array($checkID) || count($checkID)==0)
			return;

		if(!$uid)
			$uid = $userData['userid'];

		if($moveto <= 0)
			return;

		if($moveto >= 4){
			$this->db->prepare_query("SELECT id FROM msgfolder WHERE id = ? && userid = ?", $moveto, $uid);
			if($this->db->numrows()==0){
				$msgs->addMsg("Folder doesn't exist");
				return;
			}
		}

		if($moveto == MSG_TRASH){
			$this->db->prepare_query("UPDATE msgs,msgheader SET msgheader.new = 'n' WHERE msgs.userid = ? && msgs.msgheaderid=msgheader.id && msgheader.to = ? && msgheader.new = 'y' && msgs.id IN (?)", $uid, $uid, $checkID);

			$num = $this->db->affectedrows();

			if($num > 0){
				global $db;
				$db->prepare_query("UPDATE users SET newmsgs = newmsgs - ? WHERE userid = ?", $num, $uid);
//				$cache->decr(array($uid, "newmsgs-$uid"), $num);
				$cache->remove(array($uid, "newmsglist-$uid"));

				if($uid == $userData['userid'])
					$userData['newmsgs'] -= $num;
			}
		}

		$this->db->prepare_query("UPDATE msgs SET folder = ? WHERE userid = ? && id IN (?)", $moveto, $uid, $checkID);

		$msgs->addMsg("Message(s) Moved");
		return;
	}

	function markMsg($checkID, $uid = 0){
		global $userData, $msgs;

		if(!isset($checkID) || !is_array($checkID) || count($checkID)==0)
			return;

		if(!$uid)
			$uid = $userData['userid'];

		$this->db->prepare_query("UPDATE msgs SET mark = 'y' WHERE userid = ? && id IN (?)", $userData['userid'],  $checkID);

		$msgs->addMsg("Message Marked");
		return;
	}

	function unMarkMsg($checkID, $uid = 0){
		global $userData, $msgs;

		if(!isset($checkID) || !is_array($checkID) || count($checkID)==0)
			return;

		if(!$uid)
			$uid = $userData['userid'];

		$this->db->prepare_query("UPDATE msgs SET mark = 'n' WHERE userid = ? && id IN (?)", $userData['userid'],  $checkID);

		$msgs->addMsg("Message UnMarked");
		return;
	}

	function setNumNewMsgs($uid = 0){
		global $userData, $cache, $config;

		if(!$uid)
			$uid = $userData['userid'];

		$this->db->prepare_query("SELECT count(*) FROM msgs, msgheader WHERE msgs.userid = ? && msgs.msgheaderid=msgheader.id && msgheader.to = ? && new = 'y'", $uid, $uid);
		$numnew = $this->db->fetchfield();

		if($uid != $userData['userid'] || $numnew != $userData['newmsgs']){
			$this->db->prepare_query("UPDATE users SET newmsgs = ? WHERE userid = ?", $numnew, $uid);
//			$cache->put(array($uid, "newmsgs-$uid"), $numnew, $config['maxAwayTime']);
			$cache->remove(array($uid, "newmsglist-$uid"));
		}
		if($uid == $userData['userid'])
			$userData['newmsgs'] = $numnew;
	}

	function deliverMsg($to, $subject, $message, $replyto = 0, $fromname = false, $fromid = false, $ignorable = true){
		global $userData, $msgs, $emaildomain, $config, $db, $cache;

		if($fromname === false)
			$fromname = $userData['username'];
		if($fromid === false){
			$fromid = $userData['userid'];
			$age = $userData['age'];
		}else
			$age = 0;

		if(is_array($to))
			$replyto = 0;

		$db->prepare_query("SELECT userid, username, fwmsgs, email FROM users WHERE userid IN (?)", $to);
		$tousers = array();

		while($line = $db->fetchrow())
			$tousers[$line['userid']] = $line;

		if(!count($tousers)){
			$msgs->addMsg("User doesn't exist");
			return false;
		}

		$nsubject = removeHTML(trim($subject));
		$nmsg = removeHTML(trim($message));

		if($nsubject=="")
			$nsubject="No Subject";

		if($ignorable && $fromid){
			foreach($tousers as $to => $line){
				if(isIgnored($to, $fromid, 'msgs', $age, ($replyto > 0) )){
					$msgs->addMsg("Message Ignored");
					unset($tousers[$to]);
				}
			}
			if(!count($tousers))
				return false;
		}

		$hash = pack("H*", md5($nmsg)); //store hex as binary

	//	ignore_user_abort(true);

	//	$this->db->query("LOCK TABLES msgtext WRITE");

		$this->db->begin();

		$this->db->prepare_query("SELECT id FROM msgtext WHERE hash = ?", $hash); // FOR UPDATE

		if($this->db->numrows()){
			$msgtextid = $this->db->fetchfield();
		}else{
			$this->db->prepare_query("INSERT INTO msgtext SET msg = ?, hash = ?", $nmsg, $hash);
			$msgtextid = $this->db->insertid();
		}

	//	$this->db->query("UNLOCK TABLES");

		$time = time();

		foreach($tousers as $to => $user){

			$this->db->prepare_query("INSERT INTO msgheader SET `to` = ?, toname = ?, `from` = ?, fromname = ?, date = ?, new = 'y', subject = ?, msgtextid = ?, replyto = ?",
								$to, $user['username'], $fromid, $fromname, $time, $nsubject, $msgtextid, $replyto);

			$msgheaderid = $this->db->insertid();

			$this->db->prepare_query("INSERT INTO msgs SET userid = ?, msgheaderid = ?, other = ?, folder = ?, mark = 'n'", $to, $msgheaderid, $fromid, MSG_INBOX);

			if($fromid)
				$this->db->prepare_query("INSERT INTO msgs SET userid = ?, msgheaderid = ?, other = ?, folder = ?, mark = 'n'", $fromid, $msgheaderid, $to, MSG_SENT);

			$this->archivedb->prepare_query("INSERT INTO msgarchive SET id = ?, `to` = ?, toname = ?, `from` = ?, fromname = ?, date = ?, subject = ?, msg = ?",
						$msgheaderid, $to, $user['username'], $fromid, $fromname, $time, $nsubject, $nmsg);

			$new = $cache->remove(array($to, "newmsglist-$to"));
		}

		$this->archivedb->close();

		if($replyto)
			$this->db->prepare_query("UPDATE msgheader SET replied='y' WHERE id = ?", $replyto);


		$this->db->commit();

		$db->prepare_query("UPDATE users SET newmsgs = newmsgs+1 WHERE userid IN (?)", array_keys($tousers));

/*
		$new = $cache->incr(array($to, "newmsgs-$to"));

		if($new === false){
			$db->prepare_query("SELECT newmsgs WHERE userid = ?", $to)
			$new = (int)$db->fetchfield() + 1;

			$cache->put(array($to, "newmsgs-$to"), $new, $config['maxAwayTime']);
		}
*/

		if($userData['loggedIn'] && $to == $userData['userid'])
			$userData['newmsgs']++;


		foreach($tousers as $to => $user){
			if($user['fwmsgs'] == 'y'){
				$nmsg2 = smilies($nmsg);
				$nmsg2 = parseHTML($nmsg2);

				smtpmail("$user[email]", $nsubject, "From: $fromname\n\n$nmsg2\n\n------Forwarded Offline Message From $config[title]-----\nThe return address is NOT valid", "From: " . (strpos($fromname, ':') === false && strpos($fromname, ';') === false && strpos($fromname,',') === false ? "$fromname on " : "") . "$config[title] <no-reply@$emaildomain>");
			}
		}

	//	ignore_user_abort(false);

		return true;
	}

	function prune(){
		$time = time();

	$timer = new timer();
	$timer->start("messaging prune - " . gmdate("F j, g:i a T"));


	echo $timer->lap("delete old msgs");
		$this->db->prepare_query("DELETE FROM msgheader WHERE date <= ?", $time - 86400*21);
	echo $timer->lap("delete old msgs from trash");
		$this->db->prepare_query("DELETE FROM msgs WHERE folder = ?", MSG_TRASH);

//done above, no distinction between folders now
//	echo $timer->lap("delete old msgs from in/out");
//		$this->db->prepare_query("DELETE msgs FROM msgs, msgheader WHERE msgs.msgheaderid = msgheader.id && date <= ? && folder IN (?)", $time - 86400*21, array(MSG_INBOX, MSG_SENT));

	echo $timer->lap("delete stranded msgs rows");
		$this->db->query("DELETE msgs FROM msgs LEFT JOIN msgheader ON msgs.msgheaderid = msgheader.id WHERE msgheader.id IS NULL");
	echo $timer->lap("delete stranded msgheader rows");
		$this->db->query("DELETE msgheader FROM msgheader LEFT JOIN msgs ON msgs.msgheaderid = msgheader.id WHERE msgs.msgheaderid IS NULL"); //deleted by both users
	echo $timer->lap("delete stranded msgtext rows");
		$this->db->query("DELETE msgtext FROM msgtext LEFT JOIN msgheader ON msgtext.id = msgheader.msgtextid WHERE msgheader.msgtextid IS NULL");
	echo $timer->stop();
	}

	function dumpMsgs($uids){
		die("Rewrite to use the archive");
	//	$this->db->prepare_query("INSERT IGNORE INTO msgdump SELECT msgs.id, msgs.userid, msgs.to, msgs.toname, msgs.from, msgs.fromname, msgs.date, msgs.subject, msgtext.msg FROM msgs,msgtext WHERE msgs.id=msgtext.id && msgs.userid IN (?)", $uids);

	//	$this->db->prepare_query("INSERT IGNORE INTO msgdump SELECT msgs.id, msgs.userid, msgheader.to, msgheader.toname, msgheader.from, msgheader.fromname, msgheader.date, msgheader.subject, msgtext.msg FROM msgs,msgheader,msgtext WHERE msgheader.id=msgs.msgheaderid && msgheader.msgtextid=msgtext.id && msgs.userid IN (?)", $uids);

	//SELECT msgs.id, msgs.userid, msgheader.to, msgheader.toname, msgheader.from, msgheader.fromname, msgheader.date, msgheader.subject, msgtext.msg FROM msgs,msgheader,msgtext WHERE msgheader.id=msgs.msgheaderid && msgheader.msgtextid=msgtext.id && msgheader.from IN (319711)
	}

	function viewDumpedMsgs($uids, $sortbyuser = false){
		$this->db->prepare_query("SELECT msgdump.*, IF(`to`=msgdump.userid, toname, fromname) AS username, IF(`to`=msgdump.userid, `from`, `to`) AS other, users.age, users.sex FROM msgdump LEFT JOIN users ON IF(`to`=msgdump.userid, `from`, `to`)=users.userid WHERE msgdump.userid IN (?) ORDER BY " . ($sortbyuser ? "other, " : "") . "date", $uids);

		$output = "dumping " . $this->db->numrows() . " messages<br>\n";

		while($line = $this->db->fetchrow()){
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

	function archiveMonth($month, $year){
		$month = (int)$month;
		$year = (int)$year;

		if($year < 100)
			$year += 2000;

		$table = "msgarchive$year" . ($month < 10 ? '0' : '') . $month;

		$start = gmmktime(0,0,0,$month,1,$year);
		$end = gmmktime(23,59,59,$month,gmdate('t',$startdate),$year);

		$this->archivedb->query(
			"CREATE TABLE IF NOT EXISTS `$table` (
			  `id` int(10) unsigned NOT NULL default '0',
			  `to` int(10) unsigned NOT NULL default '0',
			  `toname` varchar(12) NOT NULL default '',
			  `from` int(10) unsigned NOT NULL default '0',
			  `fromname` varchar(12) NOT NULL default '',
			  `date` int(11) NOT NULL default '0',
			  `subject` varchar(64) NOT NULL default '',
			  `msg` text NOT NULL,
			  PRIMARY KEY  (`id`)
			) TYPE=MyISAM;");

		$this->archivedb->prepare_query("INSERT IGNORE INTO $table SELECT * FROM msgarchive WHERE date BETWEEN ? AND ?", $start, $end);
		$this->archivedb->prepare_query("DELETE FROM msgarchive WHERE date BETWEEN ? AND ?", $start, $end);
	}
}


