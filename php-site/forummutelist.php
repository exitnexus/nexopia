<?

	$login=1;

	require_once("include/general.lib.php");

	$perms = getForumPerms(26);

	if(!$perms['view'])
		die("You don't have permission to see this");

	$db->prepare_query("SELECT forummute.id, forummute.userid, forumid, forums.name, muteuser.username, mutetime, unmutetime, forummutereason.modid, moduser.username as modname, reason, (unmutetime - mutetime) as mutelength FROM forummute, forummutereason, forums, users as muteuser LEFT JOIN users as moduser ON moduser.userid = forummutereason.modid WHERE forummute.id = forummutereason.id &&  muteuser.userid = forummute.userid &&  forummute.forumid = forums.id && forums.official = 'y' ORDER BY forumid, mutetime");

	$rows = array();
	while($line = $db->fetchrow())
		$rows[] = $line;

	incHeader();

	echo "<table>";

	echo "<tr>";
	echo "<td class=header>Forum</td>";
	echo "<td class=header>Username</td>";
	echo "<td class=header>Mute Time</td>";
	echo "<td class=header>UnMute Time</td>";
	echo "<td class=header>Length</td>";
	echo "<td class=header>Moderator</td>";
	echo "<td class=header>Reason</td>";
	echo "</tr>";

	foreach($rows as $line){
		echo "<tr>";
		echo "<td class=body nowrap><a class=body href=forumsthreads.php?fid=$line[forumid]>$line[name]</a></td>";
		echo "<td class=body><a class=body href=profile.php?uid=$line[userid]>$line[username]</a></td>";
		echo "<td class=body nowrap>" . ($line['mutetime']==0 ? "Unknown" : userdate("F j, Y, g:i a",$line['mutetime'])) . "</td>";
		echo "<td class=body nowrap>" . ($line['unmutetime']==0 ? "Indefinately" : userdate("F j, Y, g:i a",$line['unmutetime'])) . "</td>";
		echo "<td class=body nowrap>" . ($line['unmutetime']==0 ? "Indefinately" : $mutelength[$line['mutelength']] ) . "</td>";
		echo "<td class=body><a class=body href=profile.php?uid=$line[modid]>$line[modname]</a></td>";
		echo "<td class=body>$line[reason]</td>";
		echo "</tr>";
	}

	echo "</table>";

	incFooter();
