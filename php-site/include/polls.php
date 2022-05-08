<?

class polls{

	var $db;
/*
tables:
 -polls
 -pollans
 -pollvotes
*/
	function polls( & $db ){
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

		$this->db->prepare_query("DELETE FROM polls WHERE id IN (#)", $pollid);
		$this->db->prepare_query("DELETE FROM pollans WHERE pollid IN (#)", $pollid);
		$this->db->prepare_query("DELETE FROM pollvotes WHERE pollid IN (#)", $pollid);

		$msgs->addMsg("Poll deleted");
	}

	function votePoll($pollid,$ansid){
		global $userData, $msgs, $cache;

		if(!$userData['loggedIn'])
			return;

		$ip = ip2int(getip());

		if($ansid!=0){
			$this->db->prepare_query("SELECT id FROM pollans WHERE id = # && pollid = #", $ansid, $pollid);
			if($this->db->numrows() == 0)
				return;
		}

		$this->db->prepare_query("SELECT id FROM pollvotes WHERE userid = # && pollid = #", $userData['userid'], $pollid);
		if($this->db->numrows()==0){
			if($ansid!=0){
				$this->db->prepare_query("UPDATE polls SET tvotes=tvotes+1 WHERE id = #", $pollid);

				$this->db->prepare_query("UPDATE pollans SET votes = votes+1 WHERE id = #", $ansid);
			}

			$this->db->prepare_query("INSERT IGNORE INTO pollvotes SET userid = #, ip = #, pollid = #, vote = ?, time = #", $userData['userid'], $ip, $pollid, $ansid, time());
		}else{
			$id=$this->db->fetchfield();
			$this->db->prepare_query("UPDATE pollvotes SET time = # WHERE id = #", time(), $id);
			$msgs->addMsg("You have already voted");
		}

		$cache->put(array($userData['userid'], "pollvote-$userData[userid]-$pollid"), 1, 86400);
	}

	function getPoll($pollid = 0, $moded = true){
		global $cache;

		$poll = $cache->get(array($pollid, "poll-$pollid"));

		if($poll === false){
			if($pollid==0)
				$this->db->query("SELECT * FROM polls WHERE official='y' && moded='y' ORDER BY date DESC LIMIT 1");
			elseif($moded)
				$this->db->prepare_query("SELECT * FROM polls WHERE moded = 'y' && id = #", $pollid);
			else
				$this->db->prepare_query("SELECT * FROM polls WHERE id = #", $pollid);

			if(!$this->db->numrows())
				return false;

			$poll = $this->db->fetchrow();

			$this->db->prepare_query("SELECT * FROM pollans WHERE pollid = #", $poll['id']);

			while($line = $this->db->fetchrow())
				$poll['answers'][$line['id']]=$line;

			ksort($poll['answers']);

			$cache->put(array($pollid, "poll-$pollid"), $poll, 60);
		}

		return $poll;
	}


	function pollVoted($pollid){
		global $cache, $userData;

		if(!$userData['loggedIn'])
			return false;

		$voted = $cache->get(array($userData['userid'], "pollvote-$userData[userid]-$pollid"));

		if($voted === false){
			$this->db->prepare_query("SELECT id FROM pollvotes WHERE userid = # && pollid = #", $userData['userid'], $pollid);
			$voted = $this->db->numrows();

			$cache->put(array($userData['userid'], "pollvote-$userData[userid]-$pollid"), $voted, 86400);
		}

		return $voted;
	}
}

