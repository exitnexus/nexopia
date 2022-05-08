<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"news"))
		die("Permission denied");

	if(!isset($sortd) || ($sortd!="ASC" && $sortd!="DESC"))
		$sortd="ASC";

	if(!isset($sortt) || ($sortt!="username" && $sortt!="title" && $sortt!="date" && $sortt!="type"))
		$sortt="date";

	switch($action){
		case "Add News":	add($title,$news,$type);
		case "Delete":		delete($check);
		case "edit":		edit($id);
		case "Update":		update($id,$title,$news,$type);
	}


function add($title,$news,$type){
	global $msgs,$userData, $db, $cache, $mods;

	$ntext = nl2br(parseHTML(smilies($news)));

	$db->prepare_query("INSERT INTO news SET userid = ?, title = ?, text = ?, ntext = ?, date = ?, type = ?", $userData['userid'], $title, $news, $ntext, time(), $type);

	$id = $db->insertid();

	$mods->adminlog('insert news',"insert news $id");

	$cache->remove("newsin");
	$cache->remove("newsout");

	$msgs->addMsg("News Added");
}

function delete($check){
	global $msgs, $db, $cache, $mods;

	if(!isset($check))
		return false;

	$db->prepare_query("DELETE FROM news WHERE id IN (?)", $check);

	$mods->adminlog('delete news',"delete news " . implode(",", $check));

	$cache->remove("newsin");
	$cache->remove("newsout");

	$msgs->addMsg("News Deleted");
}

function edit($id){
	global $db, $mods;

	if(!isset($id))
		return false;

	$mods->adminlog('edit news',"edit news $id");

	$db->prepare_query("SELECT title,text,type FROM news WHERE id = ?", $id);
	$line = $db->fetchrow();

	incHeader();

	echo "<form action=\"$_SERVER[PHP_SELF]\" method=POST>\n";
	echo "<input type=hidden name=id value=\"$id\">";
	echo "<table>";
	echo "<tr><td class=body>Title: </td><td class=body><input class=body type=text size=30 name=\"title\" value=\"".$line["title"]."\"></td></tr>\n";
	echo "<tr><td class=body>Viewability:</td><td class=body><select class=body name=type>" . make_select_list( getEnumValues($db, "news","type"),$line["type"])."</select></td></tr>";
	echo "<tr><td class=body>Text:  </td><td class=body><textarea class=body name=news cols=80 rows=20>" . htmlentities($line["text"])."</textarea></td></tr>\n";
	echo "<tr><td class=body></td><td class=body><INPUT class=body TYPE=submit name=action VALUE=\"Update\"><input class=body type=submit value=Cancel></td></tr>\n";
	echo "</table></form>\n";


	incFooter();
	exit();
}

function update($id,$title,$news,$type){
	global $msgs,$userData, $db, $cache, $mods;

	if(!isset($id))
		return false;

	$mods->adminlog('update news',"update news $id");

	$ntext = nl2br(parseHTML(smilies($news)));

	$db->prepare_query("UPDATE news SET userid = ?, title = ?, text = ?, ntext = ?, date = ?, type = ? WHERE id = ?", $userData['userid'], $title, $news, $ntext, time(), $type, $id);

	$cache->remove("newsin");
	$cache->remove("newsout");

	$msgs->addMsg("Update Complete");
}

	$mods->adminlog('list news',"list news");

	$result = $db->query("SELECT username,id,title,date,type FROM news,users WHERE news.userid = users.userid ORDER BY $sortt $sortd");

	incHeader();


	echo "<table width=100%><form action=\"$_SERVER[PHP_SELF]\" method=post>\n";
	echo "<tr>\n";
	echo "  <td class=header></td>\n";
	echo "  <td class=header><a class=header href=\"$_SERVER[PHP_SELF]?sortd=" . ($sortt=="title" ? ($sortd=="ASC" ? "DESC" : "ASC") : $sortd). "&sortt=title\">Title</a>". ($sortt=="title" ? "&nbsp<img src=images/$sortd.png>" : "") ."</td>\n";
	echo "  <td class=header><a class=header href=\"$_SERVER[PHP_SELF]?sortd=" . ($sortt=="type" ? ($sortd=="ASC" ? "DESC" : "ASC") : $sortd). "&sortt=type\">Type</a>". ($sortt=="type" ? "&nbsp<img src=images/$sortd.png>" : "") ."</td>\n";
	echo "  <td class=header><a class=header href=\"$_SERVER[PHP_SELF]?sortd=" . ($sortt=="username" ? ($sortd=="ASC" ? "DESC" : "ASC") : $sortd). "&sortt=username\">Author</a>". ($sortt=="username" ? "&nbsp<img src=images/$sortd.png>" : "") ."</td>\n";
	echo "  <td class=header><a class=header href=\"$_SERVER[PHP_SELF]?sortd=" . ($sortt=="date" ? ($sortd=="ASC" ? "DESC" : "ASC") : $sortd). "&sortt=date\">Date</a>". ($sortt=="date" ? "&nbsp<img src=images/$sortd.png>" : "") ."</td>\n";
	echo "</tr>\n";
	while($line = $db->fetchrow($result))
		echo "<tr><td class=body><input type=checkbox name=check[] value=\"$line[id]\"></td><td class=body><a class=body href=\"$_SERVER[PHP_SELF]?id=$line[id]&action=edit\">$line[title]</a></td><td class=body>$line[type]</td><td class=body>$line[username]</td><td class=body nowrap>" . userdate("m/d/y H:i",$line["date"]) . "</td></tr>\n";
	echo "<tr><td class=header colspan=6>";
	echo "<input class=body name=selectall type=checkbox value='Check All' onClick=\"this.value=check(this.form,'check')\">";
	echo "<input class=body type=submit name=action value=Delete></td></tr>\n";
	echo "</form></table>\n";


	echo "<form action=\"$_SERVER[PHP_SELF]\" method=POST>\n";
	echo "<table>";
	echo "<tr><td class=body>Title: </td><td class=body><input class=body type=text size=30 name=\"title\"></td></tr>\n";
	echo "<tr><td class=body>Viewability:</td><td class=body><select class=body name=type>".make_select_list( getEnumValues($db, "news","type"))."</select></td></tr>";
	echo "<tr><td class=body>Text:  </td><td class=body><textarea class=body name=news cols=80 rows=20></textarea></td></tr>\n";
	echo "<tr><td class=body></td><td class=body><INPUT class=body TYPE=submit name=action VALUE=\"Add News\"></td><td></td></tr>\n";
	echo "</table></form>\n";


	incFooter();
