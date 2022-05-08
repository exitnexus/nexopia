<?

function scoreCurve($rawScore){
	if($rawScore==0)	return "N/A";
//	return number_format( (2.55*atan($rawScore-5)+6.5) ,1);
//	return number_format( (3.5*atan($rawScore/2-2.5)+5.83) ,1);
	return number_format( (3.5*atan(($rawScore-5)/3)+6.4) ,1);
//	return number_format( 4*x/5 + 2 ,1);
}

function votePic($id,$score){
	global $userData,$msgs,$config,$db;
	if(!isset($id) || !isset($score) || !in_array($score,range(1,10))  || !$userData['loggedIn'])
		return false;

	$blocked='n';

	$ip = ip2int(getip());

	$db->prepare_query("SELECT itemid,vote,votes,score,sex FROM pics WHERE id = ?", $id);

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

	$db->prepare_query("SELECT id FROM votehist WHERE userid = ? && picid = ?", $userData['userid'], $id);
	if($db->numrows()){
		$id = $db->fetchfield();
		$db->prepare_query("UPDATE votehist SET time = ? WHERE id = ?", time(), $id);
		$msgs->addMsg("You have already voted for that picture");
	}else{
		$db->prepare_query("SELECT count(*) FROM votehist WHERE userid = ?", $userData['userid']);

		$numVotes = $db->fetchfield();

		if($numVotes > $config['minVotesToBlock']){
			$db->prepare_query("SELECT vote,count(vote) AS count FROM votehist WHERE userid = ? GROUP BY vote", $userData['userid']);

			$total=0;
			while($line = $db->fetchrow())
				$total+= $line['count']*$line['vote'];

			if($total/$numVotes < $config['minAvgVote'] && $score<=$config['maxVoteBlocked'])
				$blocked='y';
		}

		if($blocked=='n'){
/*			$top='n';
			if($data['votes']+1 >= $config['minVotesTop10'] && ((($data['score']*$data['votes'])+$score)/($data['votes']+1)) > $config['minScoreTop10' . $data['sex']])
				$top='y';
*/			$db->prepare_query("UPDATE pics SET score = (((score*votes)+$score)/(votes+1)), votes = votes+1 , v$score=v$score+1 WHERE id = ?", $id);
		}

		$db->prepare_query("INSERT IGNORE INTO votehist SET ip = ?, userid = ?, picid = ?, vote = ?, time = ?, blocked = ?", $ip, $userData['userid'], $id, $score, time(), $blocked);
	}
}

function getTopPics($sex, $minage, $maxage){
	global $db;

//	$db->prepare_query("SELECT pics.id,username FROM pics USE INDEX (top),users WHERE pics.itemid=users.userid && pics.sex='Male' && pics.age IN (?) && votes >= '$config[minVotesTop10]' && vote='y' && top='y' ORDER BY score DESC LIMIT 5", range($minage,$maxage));

	$db->prepare_query("SELECT id, username FROM picstop WHERE sex = ? && age IN (?) ORDER BY score DESC LIMIT 5", $sex, range($minage, $maxage));

	$rows = array();
	while($line = $db->fetchrow())
		$rows[$line['id']] = $line['username'];

	return $rows;
}


