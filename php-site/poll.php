<?

	$login=1;

	require_once("include/general.lib.php");

	if(!isset($pollid))
		$pollid=0;

	switch($action){
		case "Vote":
			if(isset($ans))
				votePoll($pollid,$ans);
			break;
		case "list":
			viewPollList();
			break;

		case "add":
			suggestPoll();
		case "Update":
			suggestPoll($question, $answers, $numAnswers); //exit

		case "Suggest Poll":
			if(addPoll($question, $answers, true)){
				incHeader();
				echo "Thanks for submitting your poll. It will be checked and might go up in the near future";
				incFooter();
				exit;
			}else
				suggestPoll($question, $answers, $numAnswers); //exit
	}

	dispPoll($pollid);


function dispPoll($pollid){
	global $PHP_SELF,$config,$db,$userData;

	$poll = getPoll($pollid, true);

	if(!$poll)
		die("Bad Poll id");

	if($userData['loggedIn']){
		$query = "SELECT id FROM pollvotes WHERE userid='$userData[userid]' && pollid='$poll[id]'";
		$result = $db->query($query);
	}

	incHeader();

	if(!$userData['loggedIn'] || $db->numrows($result)==0){
		echo "<table><form action=$PHP_SELF method=get>";
		echo "<input type=hidden name=pollid value=$poll[id]>";
		echo "<tr><td colspan=2 class=header>$poll[question]</td></tr>";
		foreach($poll['answers'] as $ans){
			echo "<tr>";
			if($userData['loggedIn'])
				echo "<td class=body><input type=radio name='ans' value='$ans[id]' id='ans$ans[id]'></td>";
			echo "<td class=body><label for='ans$ans[id]'>$ans[answer]</label></td></tr>";
		}
		echo "<tr><td></td><td class=body><input class=body type=submit name=action value='Vote'></td></tr>";
		echo "<tr><td colspan=2 class=body><a class=body href=$PHP_SELF?pollid=$poll[id]&ans=0&action=Vote>View Results</a></td></tr>";
		echo "<tr><td colspan=2 class=body><a class=body href=$PHP_SELF?action=list>List of polls</a></td></tr>";
		echo "</form></table>";
	}else{
		echo "<table align=center>";
		echo "<tr><td class=header colspan=2>$poll[question]</td></tr>";

		$maxval=0;
		foreach($poll['answers'] as $ans)
			if($ans['votes']>$maxval)
				$maxval = $ans['votes'];

		foreach($poll['answers'] as $ans){
			$width = $poll['tvotes']==0 ? 0 : (int)$ans["votes"]*$config['maxpollwidth']/$maxval;
			$percent = number_format($poll['tvotes']==0 ? 1 : $ans["votes"]/$poll['tvotes']*100,1);
			echo "<tr><td class=body>$ans[answer]</td><td class=body><img src='/images/red.png' width='$width' height=10> $ans[votes] / $percent %</td></tr>";
		}
		echo "<tr><td class=body></td><td class=body>Total: $poll[tvotes] votes</td></tr>";
		echo "</table>";
		echo "<a class=body href=$PHP_SELF?action=list>List of polls</a>";
	}
	incFooter();
	exit;
}

function viewPollList(){
	global $PHP_SELF,$db,$config,$page;

	if(empty($page))
		$page=0;

	$db->query("SELECT SQL_CALC_FOUND_ROWS id,question, tvotes FROM polls WHERE official='y' && moded = 'y' ORDER BY date DESC LIMIT " . ($page*$config['linesPerPage']) . ", $config[linesPerPage]");

	$rows = array();
	while($line = $db->fetchrow())
		$rows[] = $line;

	$rowresult = $db->query("SELECT FOUND_ROWS()");
	$numrows = $db->fetchfield();
	$numpages =  ceil($numrows / $config['linesPerPage']);


	incHeader();

	echo "<table align=center>";
	echo "<tr><td class=header>Recent Polls</td><td class=header>Votes</td></tr>";

	foreach($rows as $line)
		echo "<tr><td><a class=body href=$PHP_SELF?pollid=$line[id]>$line[question]</a></td><td class=body align=right>$line[tvotes]</td></tr>";

	echo "<tr><td class=header colspan=2 align=right>";
	echo "Page: " . pageList("$PHP_SELF?action=list",$page,$numpages,'header');
	echo "</td></tr>";
	echo "</table>";

	incFooter();
	exit;
}

function suggestPoll($question = "", $answers = array(), $numAnswers = 4){
	global $PHP_SELF, $db;

	incHeader();

	$answers = array_pad($answers, $numAnswers, "");
	if(count($answers) > $numAnswers)
		$numAnswers = count($answers);

	if($numAnswers > 10)
		$numAnswers = 10;

	echo "<table align=center><form action=$PHP_SELF method=post>";
	echo "<tr><td colspan=2 class=header align=center>Add Poll</td></tr>";
	echo "<tr><td class=body align=right>Question:</td><td class=body><input class=body type=text size=40 name=question value=\"" . htmlentities($question) . "\"></td></tr>";

	for($i=0;$i<$numAnswers;$i++)
		echo "<tr><td class=body align=right>" . ($i+1) . ".</td><td class=body><input class=body type=text size=40 name=answers[] value=\"" . htmlentities($answers[$i]) . "\"></td></tr>";

	echo "<tr><td class=body>Number of Answers</td><td class=body><input type=text class=body name=numAnswers size=3 value=$numAnswers><input class=body type=submit name=action value=Update></td></tr>";

	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value='Suggest Poll'></td></tr>";
	echo "</form></table>";

	incFooter();
	exit;
}
