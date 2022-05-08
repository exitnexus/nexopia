<?

function scoreCurve($rawScore){
	if($rawScore==0)	return "N/A";
//	return number_format( (2.55*atan($rawScore-5)+6.5) ,1);
//	return number_format( (3.5*atan($rawScore/2-2.5)+5.83) ,1);
	return number_format( (3.5*atan(($rawScore-5)/3)+6.4) ,1);
//	return number_format( 4*x/5 + 2 ,1);
}

function votePic($id,$score){
	global $userData, $msgs, $config, $db, $cache;
	if(!isset($id) || !isset($score) || !in_array($score,range(1,10))  || !$userData['loggedIn'])
		return false;

	$blocked='n';

	$ip = ip2int(getip());

	$db->prepare_query("SELECT itemid, vote, votes, score, sex FROM pics WHERE id = #", $id);

	if($db->numrows() == 0)
		return;

	$data = $db->fetchrow();

	if($userData['userid']==$data['itemid']){
		$msgs->addMsg("You can't vote for yourself");
		return;
	}

	if($data['vote']=='n'){
		$msgs->addMsg("Voting isn't enabled for this picture.");
		return;
	}

	$db->prepare_query("SELECT id FROM votehist WHERE userid = # && picid = #", $userData['userid'], $id);
	if($db->numrows()){
		$id = $db->fetchfield();
		$db->prepare_query("UPDATE votehist SET time = # WHERE id = #", time(), $id);

		$msgs->addMsg("You have already voted for that picture");
	}else{
		$numVotes = $cache->incr(array($userData['userid'], "numpicvotes-$userData[userid]"));

		if($numVotes === false){
			$db->prepare_query("SELECT count(*) FROM votehist WHERE userid = #", $userData['userid']);
			$numVotes = (int)$db->fetchfield() + 1;

			$cache->put(array($userData['userid'], "numpicvotes-$userData[userid]"), $numVotes, 10800);
		}

		if($numVotes > $config['minVotesToBlock']){
			$sumVotes = $cache->incr(array($userData['userid'], "sumpicvotes-$userData[userid]"), $score);

			if($sumVotes === false){
				$db->prepare_query("SELECT SUM(vote) FROM votehist WHERE userid = #", $userData['userid']);
				$sumVotes = (int)$db->fetchfield() + $score;

				$cache->put(array($userData['userid'], "sumpicvotes-$userData[userid]"), $sumVotes, 10800);
			}

			if($sumVotes/$numVotes < $config['minAvgVote'] && $score <= $config['maxVoteBlocked'])
				$blocked='y';
		}

		if($blocked=='n'){
//			$db->prepare_query("UPDATE pics SET score = (((score*votes)+$score)/(votes+1)), votes = votes+1 , v$score=v$score+1 WHERE id = #", $id);
			$db->prepare_query("UPDATE pics SET v$score=v$score+1, votes = v1+v2+v3+v4+v5+v6+v7+v8+v9+v10, score = (v1 + 2*v2 + 3*v3 + 4*v4 + 5*v5 + 6*v6 + 7*v7 + 8*v8 + 9*v9 + 10*v10)/votes WHERE id = #", $id);
		}

		$db->prepare_query("INSERT IGNORE INTO votehist SET ip = #, userid = #, picid = #, vote = #, time = #, blocked = ?", $ip, $userData['userid'], $id, $score, time(), $blocked);

		$prev = array("picid" => $id, "vote" => $score, "score" => ($data['score']*$data['votes'] + $score)/($data['votes']+1), "votes" => $data['votes']+1);

		$cache->put(array($userData['userid'], "lastpicvote-$userData[userid]"), $prev, 10800);
	}
}

function getTopPics($sex, $minage, $maxage){
	global $db;

	$db->prepare_query("SELECT id, username FROM picstop WHERE sex = ? && age IN (#) ORDER BY RAND() DESC LIMIT 5", $sex, range($minage, $maxage));

	$rows = array();
	while($line = $db->fetchrow())
		$rows[$line['id']] = $line['username'];

	return $rows;
}


