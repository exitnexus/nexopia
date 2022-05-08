<?

class usercomments{

	public $db;

	public $deletetime;
/*
tables
 -usercomments
 -usercommentstext
*/

	function __construct( & $db ){
		$this->db = & $db;

		$this->deletetime = time() - 86400*30;
	}

	function prune(){
		$this->db->prepare_query("DELETE FROM usercomments WHERE time <= #", $this->deletetime );
	}

	function delete($uid, $ids){
		global $cache, $userData, $mods;

		$this->db->prepare_query("DELETE FROM usercomments WHERE userid = % && id IN (#)", $uid, $ids);

		$cache->remove("comments5-$uid");

		if($uid != $userData['userid'])
			$mods->adminlog("delete user comments", "Delete user comments: userid $uid");

	}

	function postUserComment($uid, $msg, $fromid){
		global $usersdb, $config, $cache, $msgs, $userData, $archive;

		$msg = trim($msg);

		$spam = spamfilter($msg);

		if(!$spam)
			return false;
	
		$nmsg = cleanHTML($msg);
		
		$nmsg2 = parseHTML($nmsg);
		$nmsg3 = smilies($nmsg2);
		$nmsg3 = wrap($nmsg3);


		$time = time();


		$this->db->begin();

		$res = $this->db->prepare_query("SELECT id FROM usercomments WHERE userid = % && authorid = # && time >= #", $uid, $fromid, $time - 10);

		if($res->fetchrow()){ //double post
			$this->db->commit();
			$msgs->addMsg("Double post prevention");
			return false;
		}


		$id = $this->db->getSeqID($uid, DB_AREA_USER_COMMENT);

		$this->db->prepare_query("INSERT INTO usercomments SET userid = %, id = #, authorid = #, time = #, nmsg = ?", $uid, $id, $fromid, $time, $nmsg3);	
		
		$this->db->prepare_query("UPDATE users SET newcomments = newcomments + 1 WHERE userid = %", $uid);

//		$this->db->prepare_query("INSERT INTO usercommentsarchive SET userid = %, id = #, authorid = #, time = #, msg = ?",
//										$uid, $id, $fromid, $time, $nmsg);

		$archive->save($fromid, $id, ARCHIVE_COMMENT, ARCHIVE_VISIBILITY_USER, $uid, 0, '', $nmsg);


		$this->db->commit();

		enqueue("Comment", "create", $fromid, array($uid, $id));	

		$cache->remove("comments5-$uid");
		$cache->incr("newcomments-$uid");

/*
		$cache->put("comment-$insertid", array(	'id' => $insertid,
												'author' => $fromname,
												'authorid' => $fromid,
												'time' => $time,
												'nmsg' => $nmsg3), 86400*7);

		$cache->append("commentids-$uid", ",$insertid", 86400*7);
*/
		return true;
	}
}
