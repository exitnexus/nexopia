<?

	$login=0;

	require_once("include/general.lib.php");

	if(!isset($uid))
		$uid = $userData['userid'];

	if(!isset($cat))
		listCats();
	if(empty($picid))
		$picid=0;
	listCat($cat,$picid);

function listCats(){
	global $uid,$userData,$config, $db;

	$isFriend = $userData['loggedIn'] && (isFriend($userData['userid'],$uid) || $userData['userid']==$uid);

	$perms = array('anyone');
	if($userData['loggedIn'])
		$perms[] = 'loggedin';
	if($isFriend)
		$perms[] = 'friends';

	$db->prepare_query("SELECT userid, enablecomments, journalentries, gallery, premiumexpiry FROM users WHERE userid = ?", $uid);
	$user = $db->fetchrow();

	if($user['premiumexpiry'] < time())
		die("this user's plus membership has expired");

	$db->prepare_query("SELECT id, name, firstpicture, description FROM gallerycats WHERE firstpicture != 0 && userid = ? && permission IN (?) ORDER BY name", $uid, $perms);

	$rows = array();
	while($line = $db->fetchrow())
		$rows[] = $line;


	incHeader(0,array('incTextAdBlock','incSortBlock','incTopGirls','incTopGuys','incNewestMembersBlock'));

	echo "<table width=100%>";

	$cols=2;
	if($user['enablecomments']=='y')
		$cols++;
	if(	$user['journalentries'] == WEBLOG_PUBLIC ||
		($user['journalentries'] == WEBLOG_LOGGEDIN && $userData['loggedIn']) ||
		($user['journalentries'] == WEBLOG_FRIENDS && $isFriend))
		$cols++;
	if($user['gallery']=='anyone' || ($user['gallery']=='loggedin' && $userData['loggedIn']) || ($user['gallery']=='friends' && $isFriend))
		$cols++;

	$width = 100.0/$cols;

	echo "<tr>";
	echo "<td class=header2>";
	echo "<table width=100%>";
	echo "<td class=header align=center width=$width%><a class=header href=\"profile.php?uid=$user[userid]\"><b>Profile</b></a></td>";
	if($user['enablecomments']=='y')
		echo "<td class=header align=center width=$width%><a class=header href=\"usercomments.php?id=$user[userid]\"><b>Comments</b></a></td>";
	if($user['gallery']=='anyone' || ($user['gallery']=='loggedin' && $userData['loggedIn']) || ($user['gallery']=='friends' && $isFriend))
		echo "<td class=header align=center width=$width%><a class=header href=\"gallery.php?uid=$user[userid]\"><b>Gallery</b></a></td>";
	if(	$user['journalentries'] == WEBLOG_PUBLIC ||
		($user['journalentries'] == WEBLOG_LOGGEDIN && $userData['loggedIn']) ||
		($user['journalentries'] == WEBLOG_FRIENDS && $isFriend))
		echo "<td class=header align=center width=$width%><a class=header href=weblog.php?uid=$user[userid]><b>Blog</a></td>";
	echo "<td class=header align=center width=$width%><a class=header href=\"friends.php?uid=$user[userid]\"><b>Friends</b></a></td></tr>";
	echo "</table>";
	echo "</td></tr>";

	echo "<tr><td class=body>";

	echo "<table width=100%>";
	foreach($rows as $line){
		echo "<tr>";
		echo "<td class=body><a class=body href=gallery.php?uid=$uid&cat=$line[id]><img src=http://" . chooseImageServer($line['firstpicture']) . $config['gallerythumbdir'] . floor($line['firstpicture']/1000) . "/$line[firstpicture].jpg border=0></a></td>";
		echo "<td class=body valign=top><a class=body href=gallery.php?uid=$uid&cat=$line[id]><b>$line[name]</b></a><br>$line[description]</td></tr>";
		echo "</tr>";
	}
	echo "</table>";

	echo "</td></tr></table>";

	incFooter();

	exit;
}

function listCat($cat,$picid=0){
	global $uid,$userData, $db, $config, $skindir;

	$isFriend = $userData['loggedIn'] && (isFriend($userData['userid'],$uid) || $userData['userid']==$uid);

	$perms = array('anyone');
	if($userData['loggedIn'])
		$perms[] = 'loggedin';
	if($isFriend)
		$perms[] = 'friends';

	$db->prepare_query("SELECT id,name FROM gallerycats WHERE id = ? && userid = ? && permission IN (?)", $cat, $uid, $perms);

	if($db->numrows() == 0)
		return;

	$gallery = $db->fetchrow();

	$db->prepare_query("SELECT userid,enablecomments,journalentries,gallery,premiumexpiry FROM users WHERE userid = ?", $uid);
	$user = $db->fetchrow();

	if($user['premiumexpiry'] < time())
		return;

	incHeader(0);

	echo "<table width=680 align=center>";
	$cols=2;
	if($user['enablecomments']=='y')
		$cols++;
	if($user['journalentries'] == 'public' || ($user['journalentries']=='friends' && $isFriend))
		$cols++;
	if($user['gallery']=='anyone' || ($user['gallery']=='loggedin' && $userData['loggedIn']) || ($user['gallery']=='friends' && $isFriend))
		$cols++;

	$width = 100.0/$cols;

	echo "<tr>";
	echo "<td class=header2>";
	echo "<table width=100%>";
	echo "<td class=header align=center width=$width%><a class=header href=\"profile.php?uid=$user[userid]\"><b>Profile</b></a></td>";
	if($user['enablecomments']=='y')
		echo "<td class=header align=center width=$width%><a class=header href=\"usercomments.php?id=$user[userid]\"><b>Comments</b></a></td>";
	if($user['gallery']=='anyone' || ($user['gallery']=='loggedin' && $userData['loggedIn']) || ($user['gallery']=='friends' && $isFriend))
		echo "<td class=header align=center width=$width%><a class=header href=\"gallery.php?uid=$user[userid]\"><b>Gallery</b></a></td>";
	if($user['journalentries'] == 'public' || ($user['journalentries']=='friends' && $isFriend))
		echo "<td class=header align=center width=$width%><a class=header href=weblog.php?id=$user[userid]><b>Blog</a></td>";
	echo "<td class=header align=center width=$width%><a class=header href=\"friends.php?uid=$user[userid]\"><b>Friends</b></a></td></tr>";
	echo "</table>";
	echo "</td></tr>";

	echo "<tr><td class=body>";

	echo "<table width=100%>\n";

	echo "<script src=$config[imgserver]/skins/gallery.js></script>";

	echo "<div id=outerdiv name=outerdiv></div>";


	echo "<script>";

	echo "setGalleryTitle('" . addslashes($gallery['name']) . "');";
	echo "setUserid($user[userid]);";

	$db->prepare_query("SELECT id,description FROM gallery WHERE userid = ? && category = ? ORDER BY priority", $uid, $cat);

	$ids = array();
	$i=0;
	while($line = $db->fetchrow()){
		echo "addPic('$line[id]','http://" . chooseImageServer($line['id']) . $config['gallerypicdir'] . floor($line['id']/1000) . "/$line[id].jpg','http://" . chooseImageServer($line['id']) . $config['gallerythumbdir'] . floor($line['id']/1000) . "/$line[id].jpg','" . addslashes($line['description']) . "');";
		$ids[$line['id']] = $i;
		$i++;
	}

	if(isset($ids[$picid]))
		echo "showthumb(" . $ids[$picid] . ");";
	else
		echo "showthumbs();";

	echo "</script><br>";

	echo "</td></tr></table>";
	echo "</td></tr></table>";

	incFooter();

	exit;
}

