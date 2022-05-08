<?

	$login=1;

	require_once("include/general.lib.php");

	$police = array();

	$police[223344] = array(179754 => "acalgaryguy");

	$police[1] = array(54000 => "boarder12");

/*	$users = array();
	$users[97904]  = "TallGuy182";
	$users[178567] = "Satellite";
	$users[179754] = "acalgaryguy";
*/

	if(!isset($police[$userData['userid']]))
		die("You do not have access to this page");

	if(!isset($uid) || !isset($police[$userData['userid']][$uid])){
		incHeader();

		echo "<form action=$_SERVER[PHP_SELF]>";
		echo "Choose a person: <select name=uid>" . make_select_list_key($police[$userData['userid']]) . "</select>";
		echo "<input type=submit value=Go></form>";

		incFooter();
		exit;
	}

	$page = getREQval('page', 'int');

	$db->prepare_query("SELECT SQL_CALC_FOUND_ROWS * FROM msgdump WHERE userid = ? ORDER BY date ASC LIMIT " . ($page*25) . ", 25", $uid);

	$rows = array();
	while($line = $db->fetchrow())
		$rows[] = $line;


	$db->query("SELECT FOUND_ROWS()");
	$numrows = $db->fetchfield();
	$numpages =  ceil($numrows / $config['linesPerPage']);

	incHeader();

	echo "<table width=100%>\n";

	foreach($rows as $data){
		echo "	<tr><td class=header>To:</td><td class=header>";

		if($data['to'])
			echo "<a class=header href=profile.php?uid=$data[to]>$data[toname]</a>";
		else
			echo "$data[toname]";

		echo "</td></tr>";

		echo "	<tr><td class=header>From:</td><td class=header>";

		if($data['from'])
			echo "<a class=header href=profile.php?uid=$data[from]>$data[fromname]</a>";
		else
			echo "$data[fromname]";

		echo "</td></tr>";
		echo "	<tr><td class=header>Date:</td><td class=header>" . userdate("D M j, Y g:i a ",$data['date']) . "</td></tr>";
		echo "	<tr><td class=header>Subject:</td><td class=header>$data[subject]</td></tr>";
		echo "	<tr><td class=body valign=top colspan=2>" . nl2br(parseHTML(smilies($data['msg']))) . "<br></td></tr>";
	}
	echo "<tr><td class=header colspan=2 align=right>Page: " . pageList("$_SERVER[PHP_SELF]?uid=$uid",$page,$numpages,'header') . "</td></tr>";

	echo "</table>";

	incFooter();

// to dump a users message
//	$db->prepare_query("INSERT INTO msgdump SELECT msgs.*,msgtext.msg FROM msgs,msgtext WHERE msgs.id=msgtext.id && msgs.userid = ?", $uid);


