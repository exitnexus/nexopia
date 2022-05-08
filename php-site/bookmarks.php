<?

	$login=1;

	require_once("include/general.lib.php");

	switch($action){
		case "Add":
			sqlSafe(&$name,&$url);
			$query = "INSERT INTO bookmarks SET userid='$userData[userid]', name='$name', url='$url'";
			$db->query($query);
			$msgs->addMsg("Bookmark Added");
			break;
		case "Delete":
			foreach($delete as $deleteId){
				$query = "DELETE FROM bookmarks WHERE id='$deleteId' && userid='$userData[userid]'";
				$db->query($query);
			}
			$msgs->addMsg("Bookmark(s) Deleted");
			break;
	}

	$query = "SELECT id,name,url FROM bookmarks WHERE userid = '$userData[userid]' ORDER BY name";
    $result = $db->query($query);

	incHeader();

	echo "<table width=100%><form action=$_SERVER[PHP_SELF] method=post>\n";
	echo "<tr><td class=header></td><td class=header width=99%>Bookmarks</td></tr>";
	while($line = $db->fetchrow($result))
		echo "<tr><td class=body><input class=body type=checkbox name=delete[] value=$line[id]></td><td class=body><a class=body href=\"$line[url]\" target=_blank>$line[name]</a></td></tr>\n";
	echo "<tr><td class=header><input type='checkbox' value='Check All' onClick=\"this.value=check(this.form,'del')\"></td><td class=header><input class=body type=submit name=action value=Delete></td></tr>\n";
	echo "</form></table>\n";


	echo "<form action=$_SERVER[PHP_SELF] method=POST>\n";
	echo "<table>";

	echo "<tr><td class=body>Name:</td><td class=body><input type=text size=30 name=\"name\"></td></tr>";
	echo "<tr><td class=body>URL:</td><td class=body><input type=text size=30 name=\"url\" value=\"http://\"></td></tr>\n";

	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value=\"Add\"></td></tr>\n";
	echo "</table></form>\n";


	incFooter();
