<?

function addpoll($question,$answers,$official){
	global $msgs, $db, $mods;

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

	$db->prepare_query("INSERT INTO polls SET question = ?, date = ?, official = ?, moded = 'n'", removeHTML($question), time(), ($official ? 'y' : 'n'));
	$pollid = $db->insertid();

	foreach($answers as $ans)
		$db->prepare_query("INSERT INTO pollans SET pollid = ?, answer = ?", $pollid, removeHTML($ans));

	if($official)
		$mods->newItem(MOD_POLL, $pollid);

	$msgs->addMsg("Poll Added");
	return $pollid;
}

function deletePoll($pollid){
	global $msgs,$db;

	$db->prepare_query("DELETE FROM polls WHERE id IN (?)", $pollid);
	$db->prepare_query("DELETE FROM pollans WHERE pollid IN (?)", $pollid);
	$db->prepare_query("DELETE FROM pollvotes WHERE pollid IN (?)", $pollid);

	$msgs->addMsg("Poll deleted");
}

function votePoll($pollid,$ansid){
	global $userData,$msgs,$db;

	if(!$userData['loggedIn'])
		return;

	$ip = ip2int(getip());

	if($ansid!=0){
		$db->prepare_query("SELECT id FROM pollans WHERE id = ? && pollid = ?", $ansid, $pollid);
		if($db->numrows() == 0)
			return;
	}

	$db->prepare_query("SELECT id FROM pollvotes WHERE userid = ? && pollid = ?", $userData['userid'], $pollid);
	if($db->numrows()==0){
		if($ansid!=0){
			$db->prepare_query("UPDATE polls SET tvotes=tvotes+1 WHERE id = ?", $pollid);

			$db->prepare_query("UPDATE pollans SET votes = votes+1 WHERE id = ?", $ansid);
		}

		$db->prepare_query("INSERT INTO pollvotes SET userid = ?, ip = ?, pollid = ?, vote = ?, time = ?", $userData['userid'], $ip, $pollid, $ansid, time());
	}else{
		$id=$db->fetchfield();
		$db->prepare_query("UPDATE pollvotes SET time = ? WHERE id = ?", time(), $id);
		$msgs->addMsg("You have already voted");
	}
}

function getPoll($pollid = 0, $moded = true){
	global $db;
	if($pollid==0)
		$db->query("SELECT * FROM polls WHERE official='y' && moded='y' ORDER BY date DESC LIMIT 1");
	elseif($moded)
		$db->prepare_query("SELECT * FROM polls WHERE moded = 'y' && id = ?", $pollid);
	else
		$db->prepare_query("SELECT * FROM polls WHERE id = ?", $pollid);

	if(!$db->numrows())
		return false;

	$poll = $db->fetchrow();

	$db->prepare_query("SELECT * FROM pollans WHERE pollid = ?", $poll['id']);

	while($line = $db->fetchrow())
		$poll['answers'][$line['id']]=$line;

	ksort($poll['answers']);

	return $poll;
}

