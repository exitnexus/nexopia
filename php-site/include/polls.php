<?

class polls{

	public $db;
/*
tables:
 -polls
 -pollans
 -pollvotes
*/
	function __construct( & $db ){
		$this->db = & $db;
	}

	function addpoll($question,$answers,$official){
		global $msgs, $mods;

		if(strlen($question) < 5){
			$msgs->addMsg("Question is too short");
			return false;
		}

		foreach($answers as $id => $val)
			if(trim($val) == "")
				unset($answers[$id]);

		if(count($answers) > 10){
			$msgs->addMsg("Too many answers");
			return false;
		}

		$this->db->prepare_query("INSERT INTO polls SET question = ?, date = #, official = ?, moded = 'n'", removeHTML($question), time(), ($official ? 'y' : 'n'));
		$pollid = $this->db->insertid();

		foreach($answers as $ans)
			$this->db->prepare_query("INSERT INTO pollans SET pollid = #, answer = ?", $pollid, removeHTML($ans));

		if($official)
			$mods->newItem(MOD_POLL, $pollid);

		$msgs->addMsg("Poll Added");
		return $pollid;
	}

	function deletePoll($pollid){
		global $msgs;

		if (!is_array($pollid)) {
			if ($pollid == "") {	
				return false;	
			}
		} else {
			if (count($pollid) < 1) {
				return false;
			}
		}
		
		$this->db->prepare_query("DELETE FROM polls WHERE id IN (#)", $pollid);
		$this->db->prepare_query("DELETE FROM pollans WHERE pollid IN (#)", $pollid);
		$this->db->prepare_query("DELETE FROM pollvotes WHERE pollid IN (#)", $pollid);

		$msgs->addMsg("Poll deleted");
		return true;
	}

	function votePoll($pollid,$ansid){
		global $userData, $msgs, $cache;

		if(!$userData['loggedIn'])
			return;

		$ip = ip2int(getip());

		if($ansid!=0){
			$res = $this->db->prepare_query("SELECT id FROM pollans WHERE id = # && pollid = #", $ansid, $pollid);
			if(!$res->fetchrow())
				return;
		}

		$res = $this->db->prepare_query("SELECT id FROM pollvotes WHERE userid = # && pollid = #", $userData['userid'], $pollid);
		$vote = $res->fetchrow();
		if(!$vote){
			if($ansid!=0){
				$this->db->prepare_query("UPDATE polls SET tvotes=tvotes+1 WHERE id = #", $pollid);

				$this->db->prepare_query("UPDATE pollans SET votes = votes+1 WHERE id = #", $ansid);
			}

			$this->db->prepare_query("INSERT IGNORE INTO pollvotes SET userid = #, ip = #, pollid = #, vote = ?, time = #", $userData['userid'], $ip, $pollid, $ansid, time());
		}else{
			$id=$vote['id'];
			$this->db->prepare_query("UPDATE pollvotes SET time = # WHERE id = #", time(), $id);
			$msgs->addMsg("You have already voted");
		}

		$cache->put("pollvote-$userData[userid]-$pollid", 1, 86400);
	}

	function getPoll($pollid = 0, $moded = true){
		global $cache;

		$poll = $cache->get("poll-$pollid");

		if($poll === false){
			if($pollid==0)
				$res = $this->db->query("SELECT * FROM polls WHERE official='y' AND moded='y' ORDER BY date DESC LIMIT 1");
			elseif($moded)
				$res = $this->db->prepare_query("SELECT * FROM polls WHERE moded = 'y' && id = #", $pollid);
			else
				$res = $this->db->prepare_query("SELECT * FROM polls WHERE id = #", $pollid);

			$poll = $res->fetchrow();

			if(!$poll)
				return false;

			$res = $this->db->prepare_query("SELECT * FROM pollans WHERE pollid = #", $poll['id']);

			while($line = $res->fetchrow())
				$poll['answers'][$line['id']]=$line;

			ksort($poll['answers']);

			$cache->put("poll-$pollid", $poll, 60);
		}

		return $poll;
	}


	function pollVoted($pollid){
		global $cache, $userData;

		if(!$userData['loggedIn'])
			return false;

		$voted = $cache->get("pollvote-$userData[userid]-$pollid");

		if($voted === false){
			$res = $this->db->prepare_query("SELECT id FROM pollvotes WHERE userid = # && pollid = #", $userData['userid'], $pollid);
			$voted = count($res->fetchrowset());

			$cache->put("pollvote-$userData[userid]-$pollid", $voted, 86400);
		}

		return $voted;
	}
}

