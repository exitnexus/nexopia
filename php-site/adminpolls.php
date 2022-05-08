<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"polls"))
		die("Permission denied");
		
	switch($action){
		case "add":
			$questions = getGETval('question');
			$answers = getGETval('answers', 'array');
			if ($questions && $answers)
				addPoll($questions, $answers);
			else
				addPoll();
			break;

		case "Add Poll":

			$question = getPOSTval('question');
			$answers = getPOSTval('answers', 'array');

			if($question && $answers){
				if(!$polls->addPoll($question, $answers, true, true))
					addPoll($question, $answers);

				$mods->adminlog("add poll","Add poll: $question");
			}

			break;
		case "Delete":
			$check = getPOSTval('check', 'array');
			foreach($check as $id){
				$polls->deletePoll($id);
				$mods->adminlog("delete poll","delete poll: $id");
			}
			break;
	}

	listPolls(); //exit

///////

function listPolls(){
	global $polls, $mods, $config;

	$page = getREQval('page', 'int');

	$res = $polls->db->prepare_query("SELECT SQL_CALC_FOUND_ROWS * FROM polls WHERE official='y' && moded='y' ORDER BY date DESC LIMIT #, #", ($page*$config['linesPerPage']), $config['linesPerPage']);

	$rows = array();
	while($line = $res->fetchrow())
		$rows[] = $line;

	$numrows = $res->fetchfield();
	$numpages = ceil($numrows / $config['linesPerPage']);

	$mods->adminlog("list polls", "List polls");

	incHeader();

	echo "<table align=center><form action=$_SERVER[PHP_SELF] method=post>";

	echo "<tr>";
	echo "<td class=header></td>";
	echo "<td class=header>Poll Name</td>";
	echo "<td class=header>Date</td>";
	echo "</tr>";

	foreach($rows as $line){
		echo "<tr>";
		echo "<td class=body><input type=checkbox name=check[] value=$line[id]></td>";
		echo "<td class=body><a class=body href=/poll.php?pollid=$line[id]>$line[question]</a></td>";
		echo "<td class=body>" . userDate("M j, y, G:i", $line['date']) . "</td>";
		echo "</tr>";
	}

	echo "<tr><td class=header colspan=3>";
	echo "<table width=100%><tr><td class=header>";
	echo "<input class=body type=submit name=action value=Delete>";

	echo "</td><td class=header align=right>";
	echo "<a class=header href=$_SERVER[PHP_SELF]?action=add>Add Poll</a> | Page: " . pageList("$_SERVER[PHP_SELF]",$page,$numpages,'header');

	echo "</td></tr></table>";
	echo "</td></tr>";
	echo "</form></table>";

	incFooter();
	exit;
}

function addPoll($question = "", $answers = array(), $numAnswers = 4){

	incHeader();

	$answers = array_pad($answers, $numAnswers, "");
	if(count($answers) > $numAnswers)
		$numAnswers = count($answers);

	if($numAnswers > 10)
		$numAnswers = 10;

	echo "<form action=$_SERVER[PHP_SELF] method=post><table align=center id=mytable>";
	echo "<tr><td colspan=2 class=header align=center>Add a Poll</td></tr>";
	echo "<tr><td class=body>Question:</td><td class=body><input class=body type=text size=40 name=question maxlength=128 value=\"" . htmlentities($question) . "\"></td></tr>";

	echo "<tr><td class=body>Answers:</td><td class=body align=right><input class=body type=text size=30 name=answers[] maxlength=64 value=\"" . htmlentities($answers[0]) . "\"></td></tr>";
	for($i=1;$i<$numAnswers;$i++)
		echo "<tr><td class=body></td><td class=body align=right><input class=body type=text size=30 name=answers[] maxlength=64 value=\"" . htmlentities($answers[$i]) . "\"></td></tr>";

	echo "<tr id=before><td class=body colspan=2><a class=body href=# onClick=\"copyInputRow('mytable','before')\">Add a row</a>. Blank rows will be ignored.</td></tr>";

	echo "<tr><td class=body colspan=2 align=center><input class=body type=submit name=action value='Add Poll'></td></tr>";
	echo "</form></table>";

	incFooter();
	exit;
}
