<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"polls"))
		die("Permission denied");

	switch($action){
		case "Add":
			$polls->addPoll($question,$answers,true);

			$mods->adminlog("add poll","Add poll: $question");

			$question="";
			$answers = array();
			$numAnswers=2;

			break;
		case "Delete":
			foreach($check as $id){
				$polls->deletePoll($id);
				$mods->adminlog("delete poll","delete poll: $id");
			}
			break;
	}

	$polls->db->query("SELECT * FROM polls WHERE official='y' && moded='y' ORDER BY date DESC");

	$rows = array();
	while($line = $polls->db->fetchrow())
		$rows[] = $line;

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
		echo "<td class=body><a class=body href=poll.php?pollid=$line[id]>$line[question]</a></td>";
		echo "<td class=body>" . userDate("M j, y, G:i", $line['date']) . "</td>";
		echo "</tr>";
	}

	echo "<tr><td class=header colspan=3><input class=body type=submit name=action value=Delete></td></tr>";
	echo "</form></table>";


	if(!isset($question))		$question = "";
	if(!isset($numAnswers))		$numAnswers=2;
	if(!isset($answers))		$answers=array();
	$answers = array_pad($answers, $numAnswers, "");
	if(count($answers) > $numAnswers)
		$numAnswers = count($answers);

	echo "<table align=center><form action=$_SERVER[PHP_SELF] method=post>";
	echo "<tr><td colspan=2 class=header align=center>Add Poll</td></tr>";
	echo "<tr><td class=body align=right>Question:</td><td class=body><input class=body type=text size=40 name=question value=\"" . htmlentities($question) . "\"></td></tr>";

	for($i=0;$i<$numAnswers;$i++)
		echo "<tr><td class=body align=right>" . ($i+1) . ".</td><td class=body><input class=body type=text size=40 name=answers[] value=\"" . htmlentities($answers[$i]) . "\"></td></tr>";

	echo "<tr><td class=body>Number of Answers</td><td class=body><input type=text class=body name=numAnswers size=3 value=$numAnswers><input class=body type=submit name=action value=Update></td></tr>";

	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value=Add></td></tr>";
	echo "</form></table>";

	incFooter();

