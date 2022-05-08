<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"wordfilter"))
		die("Permission denied");

	switch($action){
		case "Add Variable":
			$mods->adminlog('add wordfilter',"Add Word '$word' of type $type to the word filter");
			if(isset($word) && isset($type))
				$db->prepare_query("INSERT INTO bannedwords SET word = ?, type = ?", $word, $type);
			break;
		case "Delete":
			$mods->adminlog('delete wordfilter',"Delete Word filter: $check");
			if(isset($check) && is_array($check))
				$db->prepare_query("DELETE FROM bannedwords WHERE id = ?", $check);
			break;
		case "edit":
			if(isset($id)){
				$res = $db->prepare_query("SELECT * FROM bannedwords WHERE id = ?", $id);
				$line = $res->fetchrow();

				incHeader();

				echo "<form action=$_SERVER[PHP_SELF] method=POST>\n";
				echo "<input type=hidden name=id value=\"$id\">";
				echo "<table>";
				echo "<tr><td class=body>Word: </td><td class=body><input class=body type=text name=\"word\" value=\"$line[word]\"></td></tr>\n";
				echo "<tr><td class=body>Type: </td><td class=body><select name=type>" . make_select_list(getEnumValues($db, "bannedwords","type"),$line['type']) ."</select></td></tr>\n";
				echo "<tr><td class=body></td><td class=body><INPUT class=body TYPE=submit name=action VALUE=\"Update\"><input class=body type=submit value=Cancel></td></tr>\n";
				echo "</table></form>\n";


				incFooter();
				exit();
			}
			break;
		case "Update":
			$mods->adminlog('update wordfilter',"Update word filter: $word, $type");
			if(isset($word) && isset($type) && isset($id) && $id > 0)
				$db->prepare_query("UPDATE bannedwords SET word = ?, type = ? WHERE id = ?", $word, $type, $id);
			break;
	}

	$mods->adminlog('list wordfilter',"List wordfilter");

	$result = $db->prepare_query("SELECT * FROM bannedwords ORDER BY type, word");

	incHeader();


	echo "<table align=center><form action=$_SERVER[PHP_SELF] method=post>\n";
	echo "<tr>\n";
	echo "  <td class=header></td>\n";
	echo "  <td class=header>Word</td>\n";
	echo "  <td class=header>Type</td>\n";
	echo "</tr>\n";
	while($line = $result->fetchrow())
		echo "<tr><td class=body><input type=checkbox name=check[] value=\"$line[id]\"></td><td class=body><a class=body href=\"$_SERVER[PHP_SELF]?id=$line[id]&action=edit\">$line[word]</a></td><td class=body>$line[type]</td></tr>\n";
	echo "<tr><td class=header colspan=6>";
	echo "<input class=body name=selectall type=checkbox value='Check All' onClick=\"this.value=check(this.form,'check')\">";
	echo "<input class=body type=submit name=action value=Delete></td></tr>\n";
	echo "</form></table>\n";


	echo "<form action=$_SERVER[PHP_SELF] method=POST>\n";
	echo "<table>";
	echo "<tr><td class=header colspan=2 align=center>Add Filter</td></tr>";
	echo "<tr><td class=body>Word: </td><td class=body><input class=body type=text name=\"word\"></td></tr>\n";
	echo "<tr><td class=body>Type: </td><td class=body><select class=body name=type>".make_select_list(getEnumValues($db, "bannedwords","type"))."</select></td></tr>\n";
	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action VALUE=\"Add Variable\"></td><td></td></tr>\n";
	echo "</table></form>\n";


	incFooter();
