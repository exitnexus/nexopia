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

	function postUserComment($uid, $msg, $fromid, $parse_bbcode){
		global $usersdb, $config, $cache, $msgs, $userData;

		$msg = trim($msg);

		$spam = spamfilter($msg);

		if(!$spam)
			return false;
	
		$nmsg = html_sanitizer::sanitize($msg);
		
		if($parse_bbcode)
		{
			$nmsg2 = parseHTML($nmsg);
			$nmsg3 = smilies($nmsg2);
			$nmsg3 = wrap($nmsg3);
			$nmsg3 = nl2br($nmsg3);
		}
		else
			$nmsg3 = $nmsg;


		$time = time();


		$dupe = $cache->get("usercommentdupe-$uid-$userData[userid]"); //should block fast dupes, like bots, use a short time since it blocks ALL posts by that user.

		if($dupe){
			$msgs->addMsg("Double post prevention");
			return false;
		}


		$this->db->begin();

		$res = $this->db->prepare_query("SELECT id FROM usercomments WHERE userid = % && authorid = # && time >= #", $uid, $fromid, $time - 10);

		if($res->fetchrow()){ //double post
			$this->db->commit();
			$msgs->addMsg("Double post prevention");
			return false;
		}


		$id = $this->db->getSeqID($uid, DB_AREA_USER_COMMENT);

		$this->db->prepare_query("INSERT INTO usercomments SET userid = %, id = #, authorid = #, time = #,  parse_bbcode = ?, nmsg = ?", $uid, $id, $fromid, $time, $parse_bbcode, $nmsg3);	
		
		$this->db->prepare_query("UPDATE users SET newcomments = newcomments + 1 WHERE userid = %", $uid);

		$this->db->prepare_query("INSERT INTO usercommentsarchive SET userid = %, id = #, authorid = #, time = #, msg = ?",
										$uid, $id, $fromid, $time, $nmsg);

		$this->db->commit();

		$cache->remove("comments5-$uid");
		$cache->incr("newcomments-$uid");

		$cache->put("usercommentdupe-$uid-$userData[userid]", 1, 3); //block dupes for 3 seconds

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
