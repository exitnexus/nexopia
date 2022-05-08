<?

	$login = 1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"staticpages"))
		die("Permission denied");

	switch($action){
		case "add":		edit(0);							break;
		case "edit":	edit(getREQval('id', 'int'));		break;
		case "insert":
			insert(	getPOSTval('name'),
					getPOSTval('content'),
					getPOSTval('restricted'));
			break;
		case "update":
			update(	getPOSTval('id', 'int'),
					getPOSTval('name'),
					getPOSTval('content'),
					getPOSTval('restricted'));
			break;
		case "delete":	delete(getREQval('id', 'int'));		break;
	}

	listPages();

/////////////////////////////

function listPages(){
	global $db;

	$db->query("SELECT id, title, restricted FROM staticpages ORDER BY title");

	$data = $db->fetchrowset();

	incHeader();

	echo "<table align=center>";

	echo "<tr>";
		echo "<td class=header>Name</td>";
		echo "<td class=header>Restricted</td>";
		echo "<td class=header>Edit</td>";
		echo "<td class=header>Delete</td>";
	echo "</tr>";

	foreach($data as $line){
		echo "<tr>";
			echo "<td class=body><a class=body href=pages.php?id=$line[id]>$line[title]</a></td>";
			echo "<td class=body>" . ($line['restricted'] == 'y' ? "Yes" : "" ) . "</td>";
			echo "<td class=body><a class=body href=$_SERVER[PHP_SELF]?action=edit&id=$line[id]>Edit</a></td>";
			echo "<td class=body><a class=body href=$_SERVER[PHP_SELF]?action=delete&id=$line[id]>Delete</a></td>";
		echo "</tr>";
	}

	echo "<tr><td class=header colspan=4 align=right><a class=header href=$_SERVER[PHP_SELF]?action=add>Add Page</a></td></tr>";

	echo "</table>";
	incFooter();
	exit;
}

function edit($id = 0){
	global $db, $mods;

	if($id){
		$db->prepare_query("SELECT id, title, content, restricted FROM staticpages WHERE id = #", $id);
		extract($db->fetchrow());

		$mods->adminlog('edit static page',"edit static page $id");
	}else{
		$title = "";
		$content = "";
		$forumcode = "n";
		$restricted = "n";
		$mods->adminlog('add static page',"add static page");
	}

	incHeader();

	echo "<table align=center>";
	echo "<form action=$_SERVER[PHP_SELF] method=post>";
	echo "<tr><td class=body>Name:</td><td class=body><input class=body type=text name=name value=\"" . htmlentities($title) . "\" maxlength=32 size=30></td></tr>";
	echo "<tr><td class=body colspan=2>" . makeCheckBox('restricted', "Restricted from being shown by pages.php", ($restricted == 'y')) . "</td></tr>";
	echo "<tr><td class=body colspan=2><textarea class=body cols=85 rows=25 name=content>" . htmlentities($content) . "</textarea></td></tr>";
	echo "<tr><td class=body colspan=2 align=center>";

	if($id){
		echo "<input type=hidden name=action value=update>";
		echo "<input type=hidden name=id value=$id>";
		echo "<input class=body accesskey='s' type=submit value=Update>";
	}else{
		echo "<input type=hidden name=action value=insert>";
		echo "<input class=body accesskey='s' type=submit value=Add>";
	}

	echo "</td></tr>";
	echo "</form></table>";

	incFooter();
	exit;
}

function insert($name, $content, $restricted){
	global $db, $mods;

	$db->prepare_query("INSERT INTO staticpages SET title = ?, content = ?, restricted = ?", $name, $content, (empty($restricted) ? 'n' : 'y'));

	$id = $db->insertid();

	$mods->adminlog('create static page',"create static page $id");
}

function update($id, $name, $content, $restricted){
	global $db, $cache, $mods;

	$db->prepare_query("UPDATE staticpages SET title = ?, content = ?, restricted = ? WHERE id = #", $name, $content, (empty($restricted) ? 'n' : 'y'), $id);
	$cache->remove("staticpages-$id");

	$mods->adminlog('update static page',"update static page $id");
}

function delete($id){
	global $db, $cache, $mods;

	$db->prepare_query("DELETE FROM staticpages WHERE id = #", $id);
	$cache->remove("staticpages-$id");

	$mods->adminlog('delete static page',"delete static page $id");
}

