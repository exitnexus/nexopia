<?

	$login=0;

	require_once("include/general.lib.php");

	$default = 0;
	if($userData['loggedIn'])
		$default = 1;

	$forumdata = array();
	$forumids = array();
	$forumtypes = array('invite' => array(),
						'latest' => array(),
						'most' => array() 	);

//invite
	if($userData['loggedIn']){
		$forums->db->prepare_query("SELECT forums.*,$default as new FROM forums,foruminvite WHERE forums.id=foruminvite.forumid && foruminvite.userid = ?", $userData['userid']);

		while($line = $forums->db->fetchrow()){
			$forumdata[$line['id']] = $line;
			$forumids[$line['id']] = $line['id'];
			$forumtypes['invite'][$line['name']] = $line['id'];
		}
		uksort($forumtypes['invite'],'strcasecmp');
	}

//latest
	$forums->db->prepare_query("SELECT forums.*,$default as new FROM forums USE KEY (time) WHERE official='n' && public='y' && time >= ? ORDER BY time DESC LIMIT 10", time() - 10800);

	while($line = $forums->db->fetchrow()){
		$forumdata[$line['id']] = $line;
		$forumids[$line['id']] = $line['id'];
		$forumtypes['latest'][] = $line['id'];
	}

//most
	$forums->db->query("SELECT forums.*,$default as new FROM forums WHERE official='n' && public='y' ORDER BY posts DESC LIMIT 10");

	while($line = $forums->db->fetchrow()){
		$forumdata[$line['id']] = $line;
		$forumids[$line['id']] = $line['id'];
		$forumtypes['most'][] = $line['id'];
	}

	if($userData['loggedIn']){
		$forums->db->prepare_query("SELECT forumid,time FROM forumupdated WHERE userid = ? && forumid IN (?)", $userData['userid'], $forumids);

		while($line = $forums->db->fetchrow())
			$forumdata[$line['forumid']]['new'] = (int)($line['time'] < $forumdata[$line['forumid']]['time']);
	}

	incHeader(false);

	echo "<table width=100% border=0 cellspacing=1 cellpadding=2>";
	echo "<tr><td class=header2>Forum</td>";
	echo "<td class=header2 align=center>Topics</td>";
//	echo "<td class=header2>Owner</td>";
	echo "<td class=header2 align=center>Posts</td>";
	echo "<td class=header2 align=right>Last Post</td></tr>";

	if(count($forumtypes['invite']) > 0){
		echo "<tr><td class=header colspan=4><b>Invites</b></td></tr>\n";
		foreach($forumtypes['invite'] as $id){
			$line = $forumdata[$id];
			echo "<tr><td class=body><a class=forumlst$line[new] href=\"forumthreads.php?fid=$line[id]\"><b>$line[name]</b></a><br>&nbsp;&nbsp;&nbsp;&nbsp;$line[description]</td>";
//			echo "<td class=body>$line[owner]</td>";
			echo "<td class=body align=center>$line[threads]</td>";
			echo "<td class=body align=center>$line[posts]</td>";
			echo "<td class=body nowrap align=right>" . ($line['time']==0 ? "Never" : userdate("M j, y g:i a",$line['time']) ) . "</td></tr>\n";
		}
	}

	if(count($forumtypes['latest']) > 0){
		echo "<tr><td class=header colspan=4><b>Most Recent Posts</b></td></tr>\n";
		foreach($forumtypes['latest'] as $id){
			$line = $forumdata[$id];
			echo "<tr><td class=body><a class=forumlst$line[new] href=\"forumthreads.php?fid=$line[id]\"><b>$line[name]</b></a><br>&nbsp;&nbsp;&nbsp;&nbsp;$line[description]</td>";
//			echo "<td class=body>$line[owner]</td>";
			echo "<td class=body align=center>$line[threads]</td>";
			echo "<td class=body align=center>$line[posts]</td>";
			echo "<td class=body nowrap align=right>" . ($line['time']==0 ? "Never" : userdate("M j, y g:i a",$line['time']) ) . "</td></tr>\n";
		}
	}

	if(count($forumtypes['most']) > 0){
		echo "<tr><td class=header colspan=4><b>Most Active Overall</b></td></tr>\n";
		foreach($forumtypes['most'] as $id){
			$line = $forumdata[$id];
			echo "<tr><td class=body><a class=forumlst$line[new] href=\"forumthreads.php?fid=$line[id]\"><b>$line[name]</b></a><br>&nbsp;&nbsp;&nbsp;&nbsp;$line[description]</td>";
//			echo "<td class=body>$line[owner]</td>";
			echo "<td class=body align=center>$line[threads]</td>";
			echo "<td class=body align=center>$line[posts]</td>";
			echo "<td class=body nowrap align=right>" . ($line['time']==0 ? "Never" : userdate("M j, y g:i a",$line['time']) ) . "</td></tr>\n";
		}
	}
	echo "<tr><td class=header colspan=4>";

	$numonline = $forums->forumsNumOnline();

	echo "<table width=100%><tr><td class=header>Users in all forums: $numonline</td>";
	if($userData['loggedIn'] && $userData['premium'])
		echo "<td class=header align=right><a class=header href=forumcreateforum.php>Create Forum</a></td>";
	echo "</tr></table>";
	echo "</td></tr>";

	echo "</table>";

	incFooter();

