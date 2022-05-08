<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],'todo'))
		die("Permission denied");

	if(!isset($sortd) || ($sortd!="ASC" && $sortd!="DESC"))
		$sortd="DESC";

	if(!isset($sortt) || ($sortt!="username" && $sortt!="title" && $sortt!="time" && $sortt!="priority"))
		$sortt="priority";

	switch($action){
		case "Add":			add($title,$description,$priority);			break;
		case "Delete":		delete($check);								break;
		case "Done":		done($check);								break;
		case "edit":		edit($id);									break;
		case "Update":		update($id,$title,$description,$priority);	break;
	}



function add($title,$description,$priority){
	global $msgs,$userData, $mods;
	if($priority<1)
		$priority=1;
	if($priority>10)
		$priority=10;

	$db->prepare_query("INSERT INTO todo SET authorid = ?, title = ?, description = ?, time = ?, priority = ?", $userData['userid'], $title, $description, time(), $priority);

	$mods->adminlog("add todo","Add Todo: $title");

	$msgs->addMsg("Item Added");
}

function delete($check){
	global $msgs,$userData, $db, $mods;

	if(empty($check)) return false;

	$db->prepare_query("DELETE FROM todo WHERE id IN (?)", $check);

	$mods->adminlog("delete todo", "Delete Todo " . implode(",", $check));

	$msgs->addMsg("Item Deleted");
}

function done($check){
	global $msgs, $db, $mods;

	if(empty($check)) return false;

	$db->prepare_query("UPDATE todo SET priority='0' WHERE id IN (?)", $check);

	$mods->adminlog("complete todo", "Compete Todo " . implode(",", $check));

	$msgs->addMsg("Item Done");
}


function edit($id){
	global $PHP_SELF,$userData, $db;

	if(empty($id)) return false;

	$db->prepare_query("SELECT title,description,priority FROM todo WHERE id = ?", $id);
    $line = $db->fetchrow();

	incHeader();

	echo "<form action=\"$PHP_SELF\" method=POST>\n";
	echo "<input type=hidden name=id value=\"$id\">";
	echo "<table>";
	echo "<tr><td class=body>Title: </td><td class=body><input class=body type=text size=30 name=title value='$line[title]'></td></tr>\n";
	echo "<tr><td class=body>Priority:</td><td class=body><select class=body name=priority>".make_select_list(array_reverse(range(1,10)),$line['priority'])."</select></td></tr>";
	echo "<tr><td class=body>Description:  </td><td class=body><textarea class=body name=description cols=80 rows=12>".htmlentities($line["description"])."</textarea></td></tr>\n";
	echo "<tr><td class=body></td><td class=body>";
	echo "<input class=body type=submit name=action value=Update accesskey='s'>";
	echo "<input class=body type=submit value=Cancel></td></tr>\n";
	echo "</table></form>\n";


	incFooter();
	exit();
}

function update($id,$title,$description,$priority){
	global $msgs,$userData, $db, $mods;

	if(empty($id)) return false;

	if($priority<1)
		$priority=1;
	if($priority>10)
		$priority=10;

	$db->prepare_query("UPDATE todo SET authorid = ?, title = ?, description = ?, time = ?, priority = ? WHERE id = ?", $userData['userid'], $title, $description, time(), $priority, $id);

	$mods->adminlog("update todo", "Update todo: $id");

	$msgs->addMsg("Update Complete");
}

	$db->query("SELECT username,id,title,time,priority FROM todo,users WHERE authorid = userid ORDER BY $sortt $sortd");

	$rows = array();
	while($line = $db->fetchrow())
		$rows[] = $line;

	$mods->adminlog("list todo", "List todo");

	incHeader();

	echo "<table width=100%><form action=\"$PHP_SELF\" method=post>\n";
	echo "<tr>\n";
	echo "  <td class=header></td>\n";
	echo "  <td class=header><a class=header href=\"$PHP_SELF?sortd=" . ($sortt=="title" ? ($sortd=="ASC" ? "DESC" : "ASC") : $sortd). "&sortt=title\">Title</a>". ($sortt=="title" ? "&nbsp<img src=images/$sortd.png>" : "") ."</td>\n";
	echo "  <td class=header><a class=header href=\"$PHP_SELF?sortd=" . ($sortt=="priority" ? ($sortd=="ASC" ? "DESC" : "ASC") : $sortd). "&sortt=priority\">Priority</a>". ($sortt=="priority" ? "&nbsp<img src=images/$sortd.png>" : "") ."</td>\n";
	echo "  <td class=header><a class=header href=\"$PHP_SELF?sortd=" . ($sortt=="username" ? ($sortd=="ASC" ? "DESC" : "ASC") : $sortd). "&sortt=username\">Author</a>". ($sortt=="username" ? "&nbsp<img src=images/$sortd.png>" : "") ."</td>\n";
	echo "  <td class=header><a class=header href=\"$PHP_SELF?sortd=" . ($sortt=="time" ? ($sortd=="ASC" ? "DESC" : "ASC") : $sortd). "&sortt=time\">Date</a>". ($sortt=="time" ? "&nbsp<img src=images/$sortd.png>" : "") ."</td>\n";
	echo "</tr>\n";
	foreach($rows as $line){
		echo "<tr>";
		echo "<td class=body><input type=checkbox name=check[] value=\"$line[id]\"></td>";
		echo "<td class=body><a class=body href=\"$PHP_SELF?id=$line[id]&action=edit\">$line[title]</a></td>";
		if($line['priority']==0)
			echo "<td class=body>Done</td>";
		else
			echo "<td class=body>$line[priority]</td>";
		echo "<td class=body>$line[username]</td>";
		echo "<td class=body nowrap>" . userdate("m/d/y H:i",$line["time"]) . "</td></tr>\n";
	}
	echo "<tr><td class=header colspan=6><input class=body name=selectall type=checkbox value='Check All' onClick=\"this.value=check(this.form,'check')\"><input class=body type=submit name=action value=Delete><input class=body type=submit name=action value=Done></td></tr>\n";
	echo "</form></table>\n";


	echo "<form action=\"$PHP_SELF\" method=POST>\n";
	echo "<table>";
	echo "<tr><td class=header colspan=2 align=center>Add new Item</td></tr>";
	echo "<tr><td class=body>Title: </td><td class=body><input class=body type=text size=30 name=\"title\"></td></tr>\n";
	echo "<tr><td class=body>Priority:</td><td class=body><select class=body name=priority>".make_select_list(array_reverse(range(1,10)))."</select></td></tr>";
	echo "<tr><td class=body>Description:  </td><td class=body><textarea class=body name=description cols=33 rows=8></textarea></td></tr>\n";
	echo "<tr><td class=body></td><td class=body><INPUT class=body TYPE=submit name=action VALUE=\"Add\" accesskey='s'></td><td></td></tr>\n";
	echo "</table></form>\n";


	incFooter();
