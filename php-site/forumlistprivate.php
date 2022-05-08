<?

	$login=1;

	require_once("include/general.lib.php");

	$perms = getForumPerms(26); // must specify a forum, choose admin forum

	if(!$perms['view'])
		die("You don't have permission to see this");

	$isAdmin = $mods->isAdmin($userData['userid'], "forums");

	if($isAdmin && $action == 'Delete' && !empty($checkid) && is_array($checkid)){
		foreach($checkid as $id)
			deleteForum($id);
	}

	$db->query("SELECT id,name,description,threads,forums.posts,time,ownerid,username, 0 as invited,public FROM forums LEFT JOIN users ON forums.ownerid=users.userid WHERE official='n' ORDER BY public, posts DESC, id ASC");

	$forums = array();
	$forumids = array();
	while($line = $db->fetchrow()){
		$forums[$line['id']] = $line;
		$forumids[] = $line['id'];
	}

	$db->prepare_query("SELECT forumid,count(*) as count FROM foruminvite WHERE forumid IN (?) GROUP BY forumid", $forumids);

	while($line = $db->fetchrow())
		$forums[$line['forumid']]['invited'] = $line['count'];

	incHeader();

	echo "<table>";
	echo "<form action=$PHP_SELF>";
	echo "<tr>";
		if($isAdmin)
			echo "<td class=header></td>";
		echo "<td class=header>Name</td>";
		echo "<td class=header>Description</td>";
		echo "<td class=header>Threads</td>";
		echo "<td class=header>Posts</td>";
		echo "<td class=header>Last Post Time</td>";
		echo "<td class=header>Owner</td>";
		echo "<td class=header>Invited</td>";
		echo "<td class=header>Public</td>";
	echo "</tr>\n";

	foreach($forums as $forum){
		echo "<tr>";
			if($isAdmin)
				echo "<td class=body><input class=body type=checkbox name=checkid[] value=$forum[id]></td>";
			echo "<td class=body><a class=body href=forumthreads.php?fid=$forum[id]>$forum[name]</a></td>";
			echo "<td class=body>$forum[description]</td>";
			echo "<td class=body align=right>$forum[threads]</td>";
			echo "<td class=body align=right>$forum[posts]</td>";
			echo "<td class=body nowrap>" . ($forum['time']==0 ? "Never" : userdate("M j, y g:i a",$forum['time']) ) . "</td>";
			echo "<td class=body><a class=body href=profile.php?uid=$forum[ownerid]>$forum[username]</a></td>";
			echo "<td class=body align=right>$forum[invited]</td>";
			echo "<td class=body align=right>" . ($forum['public']=='y' ? "Yes" : "No") . "</td>";
		echo "</tr>\n";
	}
	if($isAdmin)
		echo "<tr><td class=header colspan=9><input class=body type=submit name=action value=Delete></td></tr>";
	echo "</form>";
	echo "</table>";

	incFooter();


