<?

define("MSG_INBOX",	1);
define("MSG_SENT",	2);
define("MSG_TRASH",	3);


class messaging{

	var $db;
	var $archivedb;
/*
tables:
 -msgs - balanceable by userid
 -msgtext - not balanced yet.
 -msgfolder - balanced by userid

problems balancing msgtext:
-balance by id
	-how do you choose which server to give it to to begin with? use mysql auto_increment skipping?
-balance by to/from
	-stores multiple copies of busy messages, adding a server could screw things up
-balance by hash
	-hash not stored in msgs table, how do you know which server to get it from?
*/

	function messaging( & $db, & $archivedb ){
		$this->db = & $db;
		$this->archivedb = & $archivedb;
	}

	function createMsgFolder($name, $uid = 0){
		global $userData, $msgs, $cache;

		if(!$uid)
			$uid = $userData['userid'];

		$this->db->prepare_query($uid, "INSERT INTO msgfolder SET userid = #, name = ?", $uid, $name);

		$cache->remove(array($uid, "msgfolders-$uid"));

		$msgs->addMsg("Folder Created");
	}

	function deleteMsgFolder($id, $uid = 0){
		global $userData, $msgs, $cache;

		if(!$uid)
			$uid = $userData['userid'];

		$this->db->prepare_query($uid, "SELECT id FROM msgs WHERE folder IN (#) && userid = #", $id, $uid);

		$msgids = array();
		while($line = $this->db->fetchrow())
			$msgids[] = $line['id'];

		$this->moveMsg($msgids, MSG_TRASH, $uid);

		$this->db->prepare_query($uid, "DELETE FROM msgfolder WHERE userid = # && id IN (#)", $uid, $id);

		$cache->remove(array($uid, "msgfolders-$uid"));

		$msgs->addMsg("Folder(s) deleted");
	}

	function renameMsgFolder($id,$name, $uid = 0){
		global $userData, $msgs, $cache;

		if(!$uid)
			$uid = $userData['userid'];

		$this->db->prepare_query($uid, "UPDATE msgfolder SET name = ? WHERE userid = # && id = #", $name, $uid, $id);

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
				$this->db->prepare_query($uid, "SELECT id,name FROM msgfolder WHERE userid = #", $uid);


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

	function moveMsg($checkID, $moveto, $uid = 0){
		global $userData, $msgs, $cache;

		if(!isset($checkID) || !is_array($checkID) || count($checkID)==0)
			return;

		if(!$uid)
			$uid = $userData['userid'];

		if($moveto <= 0)
			return;

		if($moveto >= 4){
			$this->db->prepare_query($uid, "SELECT id FROM msgfolder WHERE id = # && userid = #", $moveto, $uid);
			if($this->db->numrows()==0){
				$msgs->addMsg("Folder doesn't exist");
				return;
			}
		}

		if($moveto == MSG_TRASH){
			$this->db->prepare_query($uid, "UPDATE msgs SET status = 'read' WHERE userid = # && `to` = # && status = 'new' && id IN (#)", $uid, $uid, $checkID);

			$num = $this->db->affectedrows();

			if($num > 0){
				global $db;
				$db->prepare_query("UPDATE users SET newmsgs = newmsgs - # WHERE userid = #", $num, $uid);
//				$cache->decr(array($uid, "newmsgs-$uid"), $num);
				$cache->remove(array($uid, "newmsglist-$uid"));

				if($uid == $userData['userid'])
					$userData['newmsgs'] -= $num;
			}
		}

		$this->db->prepare_query($uid, "UPDATE msgs SET folder = # WHERE userid = # && id IN (#)", $moveto, $uid, $checkID);

		$msgs->addMsg("Message(s) Moved");
		return;
	}

	function markMsg($checkID, $uid = 0){
		global $userData, $msgs;

		if(!isset($checkID) || !is_array($checkID) || count($checkID)==0)
			return;

		if(!$uid)
			$uid = $userData['userid'];

		$this->db->prepare_query($uid, "UPDATE msgs SET mark = 'y' WHERE userid = # && id IN (#)", $uid, $checkID);

		$msgs->addMsg("Message Marked");
		return;
	}

	function unMarkMsg($checkID, $uid = 0){
		global $userData, $msgs;

		if(!isset($checkID) || !is_array($checkID) || count($checkID)==0)
			return;

		if(!$uid)
			$uid = $userData['userid'];

		$this->db->prepare_query($uid, "UPDATE msgs SET mark = 'n' WHERE userid = # && id IN (#)", $uid, $checkID);

		$msgs->addMsg("Message UnMarked");
		return;
	}

	function setNumNewMsgs($uid = 0){
		global $db, $userData, $cache, $config;

		if(!$uid)
			$uid = $userData['userid'];

		$this->db->prepare_query($uid, "SELECT count(*) FROM msgs WHERE userid = # && `to` = # && new = 'y'", $uid, $uid);
		$numnew = $this->db->fetchfield();

		if($uid != $userData['userid'] || $numnew != $userData['newmsgs']){
			$db->prepare_query("UPDATE users SET newmsgs = # WHERE userid = #", $numnew, $uid);
//			$cache->put(array($uid, "newmsgs-$uid"), $numnew, $config['maxAwayTime']);
			$cache->remove(array($uid, "newmsglist-$uid"));
		}
		if($uid == $userData['userid'])
			$userData['newmsgs'] = $numnew;
	}

/*
	function getMsg($uid, $id){
		global $cache;

		$this->db->prepare_query($uid, "SELECT id, `to`, toname, `from`, fromname, date, status, subject, replyto, msgtextid, folder FROM msgs WHERE userid = # && id = #", $uid, $id);
		$msg = $this->db->fetchrow();

		if(!$msg)
			return false;

		$msgtext = $cache->get(array($msg['msgtextid'], "msgtext-$msg[msgtextid]"));

		if($msgtext === false){
			$this->db->prepare_query("SELECT msg, compressed, html FROM msgtext WHERE id = #", $msg['msgtextid']);
			$msgtext = $this->db->fetchrow();

			if($msgtext['compressed'] == 'y')
				$msgtext['msg'] = gzuncompress($msgtext['msg']);

			$cache->put(array($msg['msgtextid'], "msgtext-$msg[msgtextid]"), $msgtext, 86400);
		}

		return array_merge($msg, $msgtext);
	}
/*/
	function getMsg($uid, $ids){
		global $cache;

		$multiple = is_array($ids);

		$this->db->prepare_query($uid, "SELECT id, `to`, toname, `from`, fromname, date, status, subject, replyto, msgtextid, folder, othermsgid FROM msgs WHERE userid = # && id IN (#)", $uid, $ids);

		$msgs = array();

		while($line = $this->db->fetchrow())
			$msgs[$line['id']] = $line;

		if(!count($msgs))
			return ($multiple ? array() : false);

		$msgtextids = array();

		foreach($msgs as $msg){
			$msgtext = $cache->get(array($msg['msgtextid'], "msgtext-$msg[msgtextid]"));

			if($msgtext === false)
				$msgtextids[$msg['msgtextid']] = $msg['id'];
			else{
				$msgs[$msg['id']]['msg'] = $msgtext['msg'];
				$msgs[$msg['id']]['html'] = $msgtext['html'];
			}
		}

		if(count($msgtextids)){
			$this->db->prepare_query("SELECT id, msg, compressed, html FROM msgtext WHERE id IN (#)", array_keys($msgtextids));

			while($line = $this->db->fetchrow()){

				if($line['compressed'] == 'y')
					$line['msg'] = gzuncompress($line['msg']);

				$cache->put(array($line['id'], "msgtext-$line[id]"), $line, 86400);

				$msgs[$msgtextids[$line['id']]]['msg'] = $line['msg'];
				$msgs[$msgtextids[$line['id']]]['html'] = $line['html'];
			}
		}

		return ($multiple ? $msgs : array_shift($msgs));
	}
//*/

	function deliverMsg($to, $subject, $message, $replyto = 0, $fromname = false, $fromid = false, $ignorable = true, $html = false){
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

		$db->prepare_query("SELECT userid, username, fwmsgs, email FROM users WHERE userid IN (#)", $to);
		$tousers = array();

		while($line = $db->fetchrow())
			$tousers[$line['userid']] = $line;

		if(!count($tousers)){
			$msgs->addMsg("User doesn't exist");
			return false;
		}

		$nsubject = removeHTML(trim($subject));

		if(!$html)
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

		$time = time();

	//	ignore_user_abort(true);

	//	$this->db->query("LOCK TABLES msgtext WRITE");

		$this->db->begin();

		$this->db->prepare_query("SELECT id FROM msgtext WHERE hash = ?", $hash); // FOR UPDATE

		if($this->db->numrows()){
			$msgtextid = $this->db->fetchfield();
			$this->db->prepare_query("UPDATE msgtext SET date = # WHERE id = #", $time, $msgtextid);
		}else{

			$compressed = 'n';
			$html = 'n';

/*
			$len = strlen($nmsg);
			if ($this->_compress_enable && $this->_compress_threshold && $len >= $this->_compress_threshold){
				$c_val = gzcompress($nmsg, 9);
				$c_len = $this->_byte_count($c_val);

				if($c_len < $len*(1 - COMPRESS_SAVINGS)){
					$nmsg = $c_val;
					$compressed = 'y';
				}
			}
*/
			$this->db->prepare_query("INSERT INTO msgtext SET msg = ?, hash = ?, date = #, html = ?, compressed = ?", $nmsg, $hash, $time, $html, $compressed);
			$msgtextid = $this->db->insertid();

			$msgtext = array('compressed' => $compressed, 'html' => $html, 'msg' => $nmsg);

//			$cache->put(array($msgtextid, "msgtext-$msgtextid"), $msgtext, 86400);
		}

	//	$this->db->query("UNLOCK TABLES");

		$otherreply = 0;

		if($replyto){
			$this->db->prepare_query($to, "SELECT othermsgid FROM msgs WHERE id = #", $replyto);
			$otherreply = $this->db->fetchrow();

			if($otherreply)
				$otherreply = $otherreply['othermsgid'];
		}


		foreach($tousers as $to => $user){
			$this->db->prepare_query($to, "INSERT INTO msgs SET userid = #, folder = #, otheruserid = #, `to` = #, toname = ?, `from` = #, fromname = ?, date = #, status = 'new', subject = ?, msgtextid = #, replyto = #",
								$to, MSG_INBOX, $fromid, $to, $user['username'], $fromid, $fromname, $time, $nsubject, $msgtextid, $otherreply);

			$firstmsgid = $this->db->insertid();

			if($fromid){
				$this->db->prepare_query($fromid, "INSERT INTO msgs SET userid = #, folder = #, otheruserid = #, `to` = #, toname = ?, `from` = #, fromname = ?, date = #, status = 'new', subject = ?, msgtextid = #, replyto = #, othermsgid = #",
									$fromid, MSG_SENT, $to, $to, $user['username'], $fromid, $fromname, $time, $nsubject, $msgtextid, $replyto, $firstmsgid);

				$secondmsgid = $this->db->insertid();

				$this->db->prepare_query($to, "UPDATE msgs SET othermsgid = # WHERE id = #", $secondmsgid, $firstmsgid);
			}

			$this->archivedb->prepare_query("INSERT INTO msgarchive SET id = ?, `to` = ?, toname = ?, `from` = ?, fromname = ?, date = ?, subject = ?, msg = ?",
						$firstmsgid, $to, $user['username'], $fromid, $fromname, $time, $nsubject, $nmsg);

			$new = $cache->remove(array($to, "newmsglist-$to"));
		}

		if($otherreply)
			$this->db->prepare_query(array($to, $fromid), "UPDATE msgs SET status='replied' WHERE id IN (#,#)", $replyto, $otherreply);



		$this->db->commit();

		$db->prepare_query("UPDATE users SET newmsgs = newmsgs+1 WHERE userid IN (#)", array_keys($tousers));

		$this->archivedb->close();


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

	echo $timer->lap("delete msgs from trash");
		$this->db->prepare_query(false, "DELETE FROM msgs WHERE folder = #", MSG_TRASH);
	echo $timer->lap("delete old msgs");
		$this->db->prepare_query(false, "DELETE FROM msgs WHERE date <= #", $time - 86400*21);
	echo $timer->lap("delete stranded msgtext rows");
		$this->db->prepare_query("DELETE FROM msgtext WHERE date <= #", $time - 86400*21);
	echo $timer->stop();
	}

/*
	function dumpMsgs($uids){
		die("Rewrite to use the archive");
	//	$this->db->prepare_query("INSERT IGNORE INTO msgdump SELECT msgs.id, msgs.userid, msgs.to, msgs.toname, msgs.from, msgs.fromname, msgs.date, msgs.subject, msgtext.msg FROM msgs,msgtext WHERE msgs.id=msgtext.id && msgs.userid IN (?)", $uids);

	//	$this->db->prepare_query("INSERT IGNORE INTO msgdump SELECT msgs.id, msgs.userid, msgheader.to, msgheader.toname, msgheader.from, msgheader.fromname, msgheader.date, msgheader.subject, msgtext.msg FROM msgs,msgheader,msgtext WHERE msgheader.id=msgs.msgheaderid && msgheader.msgtextid=msgtext.id && msgs.userid IN (?)", $uids);

	//SELECT msgs.id, msgs.userid, msgheader.to, msgheader.toname, msgheader.from, msgheader.fromname, msgheader.date, msgheader.subject, msgtext.msg FROM msgs,msgheader,msgtext WHERE msgheader.id=msgs.msgheaderid && msgheader.msgtextid=msgtext.id && msgheader.from IN (319711)
	}

	function viewDumpedMsgs($uids, $sortbyuser = false){
		$this->db->prepare_query("SELECT msgdump.*, IF(`to`=msgdump.userid, toname, fromname) AS username, IF(`to`=msgdump.userid, `from`, `to`) AS other, users.age, users.sex FROM msgdump LEFT JOIN users ON IF(`to`=msgdump.userid, `from`, `to`)=users.userid WHERE msgdump.userid IN (#) ORDER BY " . ($sortbyuser ? "other, " : "") . "date", $uids);

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
*/

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
			) TYPE=MyISAM MAX_ROWS=4294967295 AVG_ROW_LENGTH=50;");

		$this->archivedb->prepare_query("INSERT IGNORE INTO $table SELECT * FROM msgarchive WHERE date BETWEEN # AND #", $start, $end);
		$this->archivedb->prepare_query("DELETE FROM msgarchive WHERE date BETWEEN # AND #", $start, $end);
	}
}


