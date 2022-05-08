<?

class usercomments{

	var $db;
	var $archivedb;
/*
tables
 -usercomments
 -usercommentstext
*/

	function usercomments( & $db, & $archivedb ){
		$this->db = & $db;
		$this->archivedb = & $archivedb;
	}

	function prune(){
		$deletetime = time() - 86400*30;
		$this->db->prepare_query(false, "DELETE FROM usercomments WHERE time <= #", $deletetime );
		$this->db->prepare_query(false, "DELETE FROM usercommentstext WHERE time <= #", $deletetime );
	}

	function postUserComment($uid, $msg, $fromid, $fromname){
		global $db, $config, $cache, $msgs;

		$msg = trim($msg);

		$spam = spamfilter($msg);

		if(!$spam)
			return false;

		$nmsg = removeHTML($msg);
		$nmsg2 = parseHTML($nmsg);
		$nmsg3 = smilies($nmsg2);
		$nmsg3 = wrap($nmsg3);
		$nmsg3 = nl2br($nmsg3);


		$time = time();

		$this->db->begin();

		$this->db->prepare_query($uid, "SELECT id FROM usercomments WHERE itemid = # && authorid = # && time >= #", $uid, $fromid, $time - 10);

		if($this->db->numrows() > 0){ //double post
			$this->db->commit();
			$msgs->addMsg("Double post prevention");
			return false;
		}


		$this->db->prepare_query($uid, "INSERT INTO usercomments SET itemid = #, author = ?, authorid = #, time = #", $uid, $fromname, $fromid, $time);

		$insertid = $this->db->insertid();

		$this->db->prepare_query($uid, "INSERT INTO usercommentstext SET id = #, time = #, nmsg = ?", $insertid, $time, $nmsg3);

		$this->db->commit();

		$db->prepare_query("UPDATE users SET newcomments = newcomments + 1 WHERE userid = #", $uid);

		$this->archivedb->prepare_query("INSERT INTO commentsarchive SET id = #, `to` = #, toname = ?, `from` = #, fromname = ?, date = #, msg = ?",
										$insertid, $uid, getUserName($uid), $fromid, $fromname, $time, $nmsg);

		$this->archivedb->close();

		$cache->remove(array($uid, "comments5-$uid"));
/*
		$new = $cache->incr(array($uid, "newcomments-$uid"));

		if($new === false){
			$db->prepare_query("SELECT newcomments WHERE userid = #", $uid)
			$new = (int)$db->fetchfield() + 1;

			$cache->put(array($uid, "newcomments-$uid"), $new, $config['maxAwayTime']);
		}
*/
		return true;
	}

	function archiveMonth($month, $year){
		$month = (int)$month;
		$year = (int)$year;

		if($year < 100)
			$year += 2000;

		$table = "commentsarchive$year" . ($month < 10 ? '0' : '') . $month;

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
			  `msg` text NOT NULL
			) TYPE=MyISAM MAX_ROWS=4294967295 AVG_ROW_LENGTH=50;");

		$this->archivedb->prepare_query("INSERT IGNORE INTO $table SELECT * FROM commentsarchive WHERE date BETWEEN # AND #", $start, $end);
		$this->archivedb->prepare_query("DELETE FROM commentsarchive WHERE date BETWEEN # AND #", $start, $end);
	}
}

