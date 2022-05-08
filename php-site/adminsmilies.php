<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"smilies"))
		die("Permission denied");


	switch($action){
		case "Add":
			$db->prepare_query("INSERT INTO smilies SET code = ?, pic = ?", $code, $file);
			$mods->adminlog("add smiley","Add Smiley: $code");
			writeSmilies();
			break;
		case "remove":
			$db->prepare_query("DELETE FROM smilies WHERE id = ?",$id);
			$mods->adminlog("delete smiley","delete smiley $id");
			writeSmilies();
			break;
	}


	$result = $db->query("SELECT * FROM smilies ORDER BY code");

	incHeader();

	echo "<table align=center width=50%>";
	echo "<tr><td class=header>Picture</td><td class=header>Code</td><td class=header></td></tr>";
	while($line = $db->fetchrow($result)){
		echo "<tr><td class=body><img src=\"/images/smilies/$line[pic].gif\" alt=\"$line[code]\"></td><td class=body><font face=courier>$line[code]</font></td>";
		echo "<td><a class=body href=\"javascript:confirmLink('$PHP_SELF?action=remove&id=$line[id]','delete this picture')\"><img src=/images/delete.gif border=0 alt='Delete'></a></td></tr>";
	}
	echo "</table>";

	echo "<table align=center width=50%><form action=$PHP_SELF>";
	echo "<tr><td colspan=2 class=header align=center><b>Add Smily</b></td></tr>";
	echo "<tr><td class=body>Code:</td><td class=body><input type=text name=code></td></tr>";
	echo "<tr><td class=body>Pic:</td><td class=body><input type=text name=file></td></tr>";
	echo "<tr><td></td><td><input type=submit name=action value=Add></td></tr>";
	echo "</form></table>";


	incFooter(array('incAdminBlock'));
