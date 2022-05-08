<?

	$login=1;

	require_once("include/general.lib.php");
	
	$pollid = getREQval('pollid', 'int');


	switch($action){
		case "Vote":
			if(($ans = getREQval('ans', 'int', -1)) !== -1 && checkKey($pollid, getREQval('k')))
				$polls->votePoll($pollid,$ans);
			break;
		case "list":
			viewPollList();
			break;

		case "add":
			suggestPoll();

		case "Suggest Poll":
			$question = getPOSTval('question');
			$answers = getPOSTval('answers', 'array');
			if($polls->addPoll($question, $answers, true)){
				incHeader();
				echo "Thanks for submitting your poll. It will be checked and might go up in the near future";
				incFooter();
				exit;
			}else{
				$numAnswers = getPOSTval('numAnswers', 'int');
				suggestPoll($question, $answers, $numAnswers); //exit
			}
	}

	dispPoll($pollid);


function dispPoll($pollid){
	global $config, $userData, $polldb, $polls;

	$poll = $polls->getPoll($pollid, true);

	if(!$poll)
		die("Bad Poll id");

	if($userData['loggedIn'])
		$voted = $polls->pollVoted($poll['id']);

	$template = new template('polls/displaypoll');

	if(!$userData['loggedIn'] || !$voted){
		$template->setMultiple(array(
			'canVote'	=> 1,
			'loggedin'	=> $userData['loggedIn'],
			'key'		=> makeKey($poll['id']),
			'poll'		=> $poll
		));
	}
	else{
		$maxval=0;
	
		foreach($poll['answers'] as $ans)
			if($ans['votes']>$maxval)
				$maxval = $ans['votes'];

		foreach($poll['answers'] as $key => $ans){
			$width = $poll['tvotes']==0 ? 0 : (int)$ans["votes"]*$config['maxpollwidth']/$maxval;
			$percent = number_format($poll['tvotes']==0 ? 1 : $ans["votes"]/$poll['tvotes']*100,1);
			$poll['answers'][$key]['displayWidth'] = $width;
			$poll['answers'][$key]['displayPercent'] = $percent;
		}

		$template->setMultiple(array(
			'canVote'		=> 0,
			'poll'			=> $poll,
			'imageLocation'	=> $config['imageloc']
		));
	
	}
	$res = $polldb->prepare_query("SELECT authorid, time, nmsg FROM pollcomments, pollcommentstext WHERE itemid = ? && pollcomments.id = pollcommentstext.id ORDER BY pollcomments.id ASC LIMIT 5", $pollid);
	$comments = array();
	$uids = array();
	while($line = $res->fetchrow()){
		$comments[] = $line;
		$uids[$line['authorid']] = $line['authorid'];
	}

	if(count($uids)){
		$usernames = getUserName($uids);
		
		foreach($comments as $k => $v)
			$comments[$k]['author'] = $usernames[$v['authorid']];
	}
	
	$template->set('count_comments', count($comments));
	$template->set('comments', $comments);
	$template->set('id', $pollid);
	$template->display();
	exit;
}

function viewPollList(){
	global $config, $polls;

	$page = getREQval('page', 'int');

	$res = $polls->db->query("SELECT SQL_CALC_FOUND_ROWS id,question, tvotes, comments FROM polls WHERE official='y' && moded = 'y' ORDER BY date DESC LIMIT " . ($page*$config['linesPerPage']) . ", $config[linesPerPage]");

	$rows = array();
	while($line = $res->fetchrow())
		$rows[] = $line;

	$numrows = $res->totalrows();
	$numpages =  ceil($numrows / $config['linesPerPage']);

	$template = new template('polls/viewlist');
	$template->setMultiple(array(
		'pollList'	=> $rows,
		'pageList'	=> pageList("{$_SERVER['PHP_SELF']}?action=list", $page, $numpages, 'header')
	));
	$template->display();
	exit;
}

function suggestPoll($question = "", $answers = array(), $numAnswers = 4){
	$answers = array_pad($answers, $numAnswers, "");
	if(count($answers) > $numAnswers)
		$numAnswers = count($answers);

	if($numAnswers > 10)
		$numAnswers = 10;

	array_splice($answers, $numAnswers);

	$template = new template('polls/suggest');
	$template->setMultiple(array(
		'question'  => $question,
		'answers'	=> $answers
	));
	$template->display();
	exit;
}


