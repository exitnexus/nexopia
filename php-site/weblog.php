<?

	$login=0;

	require_once("include/general.lib.php");

	$possibleScope = array('public'=>'Public','friends'=>'Friends only','private'=>'Private');

	if(!isset($id) || $id=="" || $id==0){
		if(!$userData['loggedIn'])
			die("Bad User id");
		else
			$id=$userData['userid'];
	}

	if(!isset($page) || $page<0) $page=0;


	$db->prepare_query("SELECT username,enablecomments,journalentries,gallery FROM users WHERE userid = ?", $id);
	$user = $db->fetchrow();

	$isFriend = $userData['loggedIn'] && (isFriend($userData['userid'],$id) || $userData['userid']==$id);

	$perms = array('public');
	if($isFriend)
		$perms[] = 'friends';
	if($userData['loggedIn'] && $id == $userData['userid'])
		$perms[] = 'private';

	$result = $db->prepare_query("SELECT SQL_CALC_FOUND_ROWS * FROM weblog WHERE userid='$id' && scope IN (?) ORDER BY time DESC LIMIT " . ($page*$config['linesPerPage']) . ", $config[linesPerPage]", $perms);

	$db->query("SELECT FOUND_ROWS()");
	$numrows = $db->fetchfield();
	$numpages =  ceil($numrows / $config['linesPerPage']);


	incHeader(0,array('incTextAdBlock','incSortBlock','incTopGirls','incTopGuys','incNewestMembersBlock'));

	echo "<table width=100% cellpadding=3>";

	$cols=2;
	if($user['enablecomments']=='y')
		$cols++;
	if($user['journalentries'] == 'public' || ($user['journalentries']=='friends' && $isFriend))
		$cols++;
	if($user['gallery']=='anyone' || ($user['gallery']=='loggedin' && $userData['loggedIn']) || ($user['gallery']=='friends' && $isFriend))
		$cols++;

	$width = 100.0/$cols;

	echo "<tr>";
	echo "<td class=header2 colspan=3>";
	echo "<table width=100%>";
	echo "<td class=header align=center width=$width%><a class=header href=\"profile.php?uid=$id\"><b>Profile</b></a></td>";
	if($user['enablecomments']=='y')
		echo "<td class=header align=center width=$width%><a class=header href=\"usercomments.php?id=$id\"><b>Comments</b></a></td>";
	if($user['gallery']=='anyone' || ($user['gallery']=='loggedin' && $userData['loggedIn']) || ($user['gallery']=='friends' && $isFriend))
		echo "<td class=header align=center width=$width%><a class=header href=\"gallery.php?uid=$id\"><b>Gallery</b></a></td>";
	if($user['journalentries'] == 'public' || ($user['journalentries']=='friends' && $isFriend))
		echo "<td class=header align=center width=$width%><a class=header href=weblog.php?id=$id><b>Journal</a></td>";
	echo "<td class=header align=center width=$width%><a class=header href=\"friends.php?uid=$id\"><b>Friends</b></a></td></tr>";
	echo "</table>";
	echo "</td></tr>";


	echo "<tr><td class=header colspan=$cols>";
	echo "<table width=100%><tr><td class=header>";

	echo "<a class=header href=profile.php?uid=$id>$user[username]'s Profile</a> > <a class=header href=$PHP_SELF?id=$id>$user[username]'s Journal</a>";

	echo "</td>";

	echo "<form>";
	echo "<td class=header align=right>";

	echo "Page:";
	echo pageList("$PHP_SELF?id=$id",$page,$numpages,'header');

	echo "</td></tr></table></td></tr>";

	if($db->numrows($result)==0)
		echo "<tr><td class=body colspan=$cols align=center>No Entries</td>";

	while($line = $db->fetchrow($result)){
		echo "<tr><td class=header>";

		echo $line['title'];

		echo "</td><td class=header>Date: " . userdate("M j, Y G:i:s",$line['time']) . "</td>";
		echo "<td class=header>" . $possibleScope[$line['scope']] . "</td>";
		echo "</tr>";

		echo "<td class=body colspan=3>";

		echo $line['nmsg'] . "&nbsp;";

		echo "</td></tr>";
//		echo "<tr><td colspan=3 class=header2>&nbsp;</td></tr>";
	}
	echo "<tr><td class=header colspan=3>";
	echo "<table width=100%><tr>";

	echo "<form>";
	echo "<td class=header align=right>";

	echo "Page:";

	echo pageList("$PHP_SELF?id=$id",$page,$numpages,'header');

	echo "</td>";
	echo "</form>";
	echo "</tr></table>";

	echo "</td></tr>";


	echo "</table>\n";

	incFooter();
