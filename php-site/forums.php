<?

	$login=0;

	require_once("include/general.lib.php");

	$cache->prime("fo0");

	if($userData['loggedIn'])
		$db->prepare_query("SELECT forums.*, IF( forumupdated.time IS NULL, 1, forums.time > forumupdated.time ) AS new FROM forums LEFT JOIN forumupdated on forums.id = forumupdated.forumid && forumupdated.userid = ? WHERE official='y'", $userData['userid']);
	else
		$db->query("SELECT *, 0 as new FROM forums WHERE official='y'");

	$parentdata = array();
	$forums = array();
	$priorities = array();

	$rows = array();
	while($line = $db->fetchrow()){
		$rows[] = $line;
		$priorities[] = $line['priority'];
	}

	array_multisort($priorities, SORT_ASC, $rows);

	foreach($rows as $line){
		$forums[$line['id']] = $line;
		$parentdata[$line['parent']][]=$line['id'];
	}

	incHeader(false);

	echo "<table width=100% border=0 cellspacing=1 cellpadding=2>";//bordercolor=#666666 style=\"border-collapse: collapse\">\n";
	echo "<tr><td class=header2>Forum</td><td class=header2 align=center>Topics</td><td class=header2 align=center>Posts</td><td class=header2 align=right>Last Post</td></tr>";

	foreach($parentdata[0] as $realm){
		echo "<tr><td class=header colspan=4><b>" . $forums[$realm]['name'] . "</b></td></tr>\n";

		foreach($parentdata[$realm] as $id){
			$line = $forums[$id];
			echo "<tr>";
			echo "<td class=body>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a class=forumlst$line[new] href=\"forumthreads.php?fid=$line[id]\"><b>$line[name]</b></a><br>";
			echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$line[description]</td>";
			echo "<td class=body align=center>$line[threads]</td><td class=body align=center>$line[posts]</td>";
			echo "<td class=body nowrap align=right>" . ($line['time'] > 0 ? userdate("M j, y g:i a",$line['time']) : "Never") . "</td>";
			echo "</tr>\n";

		}
	}

	$numonline = forumsNumOnline();

	echo "<tr><td class=header colspan=4>Users in all forums: $numonline</td></tr>";

	echo "</table>";

	incFooter(false);

