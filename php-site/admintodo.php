<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],'todo'))
		die("Permission denied");

	$assignees = array(
						0 => "Unassigned",
						146539 => "Matt",
						5 => "Melina",
						673 => "Rob",
						1 => "Timo",
					);

	$timereqs = array(
						0 => "N/A / Unknown",
						300 => "5 min",
						900 => "15 min",
						1800 => "30 min",
						3600 => "1 hour",
						3600*2 => "2 hours",
						3600*3 => "3 hours",
						3600*4 => "4 hours",
						3600*6 => "6 hours",
						3600*8 => "8 hours",
						86400*2 => "2 days",
						86400*3 => "3 days",
						86400*5 => "5 days",
						86400*7 => "1 week",
						86400*14 => "2 weeks",
						86400*21 => "3 weeks",
						86400*30 => "1 month",
					);

	$sections = array(
						0 => "Other",
						1 => "Abuse",
						2 => "Admin",
						3 => "Articles",
						4 => "Backups",
						5 => "Banners",
						6 => "Forums",
						7 => "Gallery",
						8 => "Blog",
						9 => "Marketing",
						10=> "Mod",
						11=> "Plus",
						12=> "Polls",
						13=> "Profile",
						14=> "Security",
						15=> "Servers",
						16=> "Speed",
						100=> "New Section",
					);

	$statuss = array(
						0 => "Not Looked At",
						1 => "Not Started",
						2 => "Waiting",
						3 => "Stuck",
						4 => "In process",
						5 => "Done",
					);


	switch($action){
		case "add":
			add();
			break;
		case "Add":
			if($data = getPOSTval('data', 'array'))
				insert($data);
			break;
		case "delete":
			if($id = getREQval('id', 'int'))
				delete($id);
			break;
		case "done":
			if($id = getREQval('id', 'int'))
				done($id);
			break;
		case "edit":
			if($id = getREQval('id', 'int'))
				edit($id);
			break;
		case "view":
			if($id = getREQval('id', 'int'))
				view($id);
			break;
		case "Update":
			if($id = getREQval('id', 'int'))
				update($id, getPOSTval('data', 'array'));
			break;
	}

	$user = getREQval('user', 'int');
	$scope = getREQval('scope', 'int', 1);

	listTodo($user, $scope); //exit

///////////////

function insert($data){
	global $msgs, $userData, $mods, $db;

	extract($data);

	if($priority < 1)
		$priority=1;
	if($priority > 10)
		$priority=10;

	$db->prepare_query("INSERT INTO todo SET authorid = ?, title = ?, description = ?, time = ?, priority = ?, assignee = ?, timereq = ?, status = ?, section = ?", $userData['userid'], $title, $description, time(), $priority, $assignee, $timereq, $status, $section);

	$mods->adminlog("add todo","Add Todo: $title");

	$msgs->addMsg("Item Added");
}

function delete($id){
	global $msgs,$userData, $db, $mods;

	if(empty($id)) return false;

	$db->prepare_query("DELETE FROM todo WHERE id IN (?)", $id);

	$mods->adminlog("delete todo", "Delete Todo $id");

	$msgs->addMsg("Item Deleted");
}

function done($id){
	global $msgs, $db, $mods;

	if(empty($id)) return false;

	$db->prepare_query("UPDATE todo SET priority = '0' WHERE id IN (?)", $id);

	$mods->adminlog("complete todo", "Compete Todo $id");

	$msgs->addMsg("Item Done");
}


function add(){
	global $assignees, $timereqs, $sections, $statuss;

	incHeader();

	echo "<form action=$_SERVER[PHP_SELF] method=post>\n";
	echo "<table align=center>";
	echo "<tr><td class=header colspan=2 align=center>Add new Item</td></tr>";
	echo "<tr><td class=body>Title: </td><td class=body><input class=body type=text size=50 name=data[title]></td></tr>\n";
	echo "<tr><td class=body>Priority:</td><td class=body><select class=body name=data[priority]>" . make_select_list(array_reverse(range(1,10))) . "</select></td></tr>";
	echo "<tr><td class=body>Assign To:</td><td class=body><select class=body name=data[assignee]>" . make_select_list_key($assignees) . "</select></td></tr>";
	echo "<tr><td class=body>Time Estimate:</td><td class=body><select class=body name=data[timereq]>" . make_select_list_key($timereqs) . "</select></td></tr>";
	echo "<tr><td class=body>Section:</td><td class=body><select class=body name=data[section]>" . make_select_list_key($sections) . "</select></td></tr>";
	echo "<tr><td class=body>Status:</td><td class=body><select class=body name=data[status]>" . make_select_list_key($statuss) . "</select></td></tr>";
	echo "<tr><td class=body colspan=2 valign=top>Description:<br><textarea class=body name=data[description] cols=100 rows=15></textarea></td></tr>\n";
	echo "<tr><td class=body></td><td class=body><INPUT class=body TYPE=submit name=action VALUE=\"Add\" accesskey='s'><input class=body type=submit value=Cancel></td><td></td></tr>\n";
	echo "</table></form>\n";

	incFooter();
	exit;
}

function edit($id){
	global $userData, $db, $assignees, $timereqs, $sections, $statuss;

	$res = $db->prepare_query("SELECT * FROM todo WHERE id = ?", $id);

	$line = $res->fetchrow();

	if(!$line)
		return;

	incHeader();

	echo "<form action=$_SERVER[PHP_SELF] method=post>\n";
	echo "<input type=hidden name=id value=$id>";
	echo "<table>";
	echo "<tr><td class=body>Title: </td><td class=body><input class=body type=text size=50 name=data[title] value='$line[title]'></td></tr>\n";
	echo "<tr><td class=body>Priority:</td><td class=body><select class=body name=data[priority]>".make_select_list(array_reverse(range(1,10)),$line['priority'])."</select></td></tr>";
	echo "<tr><td class=body>Assign To:</td><td class=body><select class=body name=data[assignee]>" . make_select_list_key($assignees, $line['assignee']) . "</select></td></tr>";
	echo "<tr><td class=body>Time Estimate:</td><td class=body><select class=body name=data[timereq]>" . make_select_list_key($timereqs, $line['timereq']) . "</select></td></tr>";
	echo "<tr><td class=body>Section:</td><td class=body><select class=body name=data[section]>" . make_select_list_key($sections, $line['section']) . "</select></td></tr>";
	echo "<tr><td class=body>Status:</td><td class=body><select class=body name=data[status]>" . make_select_list_key($statuss, $line['status']) . "</select></td></tr>";
	echo "<tr><td class=body colspan=2>Description:<br><textarea class=body name=data[description] cols=100 rows=20>".htmlentities($line["description"])."</textarea></td></tr>\n";
	echo "<tr><td class=body></td><td class=body>";
	echo "<input class=body type=submit name=action value=Update accesskey='s'>";
	echo "<input class=body type=submit value=Cancel></td></tr>\n";
	echo "</table></form>\n";


	incFooter();
	exit();
}

function update($id, $data){
	global $msgs,$userData, $db, $mods, $sections, $statuss;

	extract($data);

	if($priority<1)
		$priority=1;
	if($priority>10)
		$priority=10;

	$db->prepare_query("UPDATE todo SET authorid = ?, title = ?, description = ?, time = ?, priority = ?, assignee = ?, timereq = ?, status = ?, section = ? WHERE id = ?", $userData['userid'], $title, $description, time(), $priority, $assignee, $timereq, $status, $section, $id);

	$mods->adminlog("update todo", "Update todo: $id");

	$msgs->addMsg("Update Complete");
}

function view($id){
	global $userData, $db, $assignees, $timereqs, $sections, $statuss;

	$res = $db->prepare_query("SELECT title, description, priority, assignee, timereq, section, status FROM todo WHERE id = ?", $id);

	$line = $res->fetchrow();

 	if(!$line)
		return;

	incHeader(750);

	echo "<table width=100%>";
	echo "<tr><td class=body colspan=2>";
	echo "<a class=body href=$_SERVER[PHP_SELF]>List</a> | ";
	echo "<a class=body href=$_SERVER[PHP_SELF]?action=edit&id=$id>Edit</a> | ";
	echo "<a class=body href=$_SERVER[PHP_SELF]?action=done&id=$id>Done</a> | ";
	echo "<a class=body href=$_SERVER[PHP_SELF]?action=delete&id=$id>Delete</a>";
	echo "</td></tr>";

	echo "<tr><td class=header width=100>Title: </td><td class=header>$line[title]</td></tr>\n";
	echo "<tr><td class=header>Priority:</td><td class=header>$line[priority]</td></tr>";
	echo "<tr><td class=header>Assign To:</td><td class=header>" . $assignees[$line['assignee']] . "</td></tr>";
	echo "<tr><td class=header>Time Estimate:</td><td class=header>" . $timereqs[$line['timereq']] . "</td></tr>";
	echo "<tr><td class=header>Section:</td><td class=header>" . $sections[$line['section']] . "</td></tr>";
	echo "<tr><td class=header>Status:</td><td class=header>" . $statuss[$line['status']] . "</td></tr>";
	echo "<tr><td class=body colspan=2>" . nl2br(removeHTML($line['description'])) . "</td></tr>\n";
	echo "</table>\n";

	incFooter();
	exit();
}

function listTodo($user = 0, $scope = 1){
	global $userData, $db, $assignees, $mods, $timereqs, $sections, $statuss;

	$where = array();
	$params = array();

	if($user){
		$where[] = "assignee = ?";
		$params[] = $user;
	}

	$where[] = "priority >= ?";
	$params[] = $scope;

	$res = $db->prepare_array_query("SELECT id, title, time, priority, assignee, timereq, status, section, authorid FROM todo " . (count($where) ? "WHERE " . implode(" && ", $where) . " " : "") . "ORDER BY priority DESC", $params);

	$rows = array();
	while($line = $res->fetchrow())
		$rows[] = $line;

	$mods->adminlog("list todo", "List todo");

	incHeader();

	echo "<table align=center><form action=$_SERVER[PHP_SELF] method=post>\n";

	echo "<tr><td class=header colspan=8 align=center>";
	echo "<select class=body name=user>" . make_select_list_key($assignees, $user) . "</select>";
	echo "<select class=body name=scope><option value='-1'>Done" . make_select_list(range(1,10), $scope) . "</select>";
	echo "<input class=body type=submit value=Go>";
	echo "</td></tr>";
	echo "</form>";


	echo "<tr>\n";
//	echo "  <td class=header></td>\n";
	echo "  <td class=header>Title</td>\n";
	echo "  <td class=header>Priority</td>\n";
	echo "  <td class=header>Status</td>\n";
	echo "  <td class=header>Section</td>\n";
	echo "  <td class=header>Assigned To</td>\n";
	echo "  <td class=header>Time</td>\n";
	echo "  <td class=header>Author</td>\n";
	echo "  <td class=header>Date</td>\n";
	echo "</tr>\n";

	foreach($rows as $line){
		echo "<tr>";
//		echo "<td class=body><input type=checkbox name=check[] value=\"$line[id]\"></td>";
		echo "<td class=body><a class=body href=\"$_SERVER[PHP_SELF]?id=$line[id]&action=view\">$line[title]</a></td>";
		if($line['priority']==0)
			echo "<td class=body>Done</td>";
		else
			echo "<td class=body>$line[priority]</td>";
		echo "<td class=body>" . $statuss[$line['status']] . "</td>";
		echo "<td class=body>" . $sections[$line['section']] . "</td>";
		echo "<td class=body>" . $assignees[$line['assignee']] . "</td>";
		echo "<td class=body>" . $timereqs[$line['timereq']] . "</td>";
		echo "<td class=body>" . $assignees[$line['authorid']] . "</td>";
		echo "<td class=body nowrap>" . userdate("M j, y, g:i a",$line["time"]) . "</td></tr>\n";
	}
	echo "<tr>";
	echo "<td class=header>" . count($rows) . " Items</td>";
	echo "<td class=header colspan=7 align=right><a class=header href=$_SERVER[PHP_SELF]?action=add>Add Entry</a></td>";
	echo "</tr>";

	echo "</table>\n";

	incFooter();
	exit;
}

