<?

	$login = 1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"staticpages"))
		die("Permission denied");

	switch($action){
		case "add":		edit(0);							break;
		case "edit":	edit(getREQval('id', 'int'));		break;

		case "Add":
			insert(	getPOSTval('name'),
					getPOSTval('content'),
					getPOSTval('restricted', 'bool'),
					!getPOSTval('parsecode', 'bool'),
					getPOSTval('autonewlines', 'bool'),
					getPOSTval('pagewidth', 'int')
					);
			break;
		case "Update":
		case "Save":
			update(	getPOSTval('id', 'int'),
					getPOSTval('name'),
					getPOSTval('content'),
					getPOSTval('restricted', 'bool'),
					!getPOSTval('parsecode', 'bool'),
					getPOSTval('autonewlines', 'bool'),
					getPOSTval('pagewidth', 'int')
					);
			if($action == "Save")
				edit(getREQval('id', 'int'));
			break;
		case "delete":	delete(getREQval('id', 'int'));		break;
	}

	listPages();

/////////////////////////////

function listPages(){
	global $db;

	$res = $db->query("SELECT id, name, restricted FROM staticpages ORDER BY restricted, name");

	$data = $res->fetchrowset();

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
			echo "<td class=body>" . ($line['restricted'] == 'y' ? "$line[name]" : "<a class=body href=/pages.php?id=$line[id]>$line[name]</a>" ) . "</td>";
			echo "<td class=body>" . ($line['restricted'] == 'y' ? "Yes" : "" ) . "</td>";
			echo "<td class=body><a class=body href=$_SERVER[PHP_SELF]?action=edit&id=$line[id]>Edit</a></td>";
			echo "<td class=body><a class=body href=\"javascript:confirmLink('$_SERVER[PHP_SELF]?action=delete&id=$line[id]','delete this item?')\">Delete</a></td>";
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
		$res = $db->prepare_query("SELECT id, name, content, restricted, html, autonewlines, pagewidth FROM staticpages WHERE id = #", $id);
		extract($res->fetchrow());

		$mods->adminlog('edit static page',"edit static page $id");
	}else{
		$name = "";
		$content = "";
		$forumcode = "n";
		$restricted = "n";
		$html = 'y';
		$autonewlines = 'n';
		$pagewidth = 0;
		$mods->adminlog('add static page',"add static page");
	}

	incHeader();

	echo "<table align=center>";
	echo "<form action=$_SERVER[PHP_SELF] method=post>";
	echo "<tr><td class=body width=70>Name:</td><td class=body><input class=body type=text name=name value=\"" . htmlentities($name) . "\" maxlength=32 size=30></td></tr>";
	echo "<tr><td class=body>Page width:</td><td class=body><input class=body type=text name=pagewidth value=$pagewidth maxlength=3 size=3> (0 for full width, otherwise in px)</td></tr>";
	echo "<tr><td class=body colspan=2>" . makeCheckBox('restricted', "Restricted from being shown by pages.php", ($restricted == 'y')) . "</td></tr>";
	echo "<tr><td class=body colspan=2>" . makeCheckBox('parsecode', "Parse as forumcode", ($html == 'n')) . " (doesn't remove html)</td></tr>";
	echo "<tr><td class=body colspan=2>" . makeCheckBox('autonewlines', "Automatically add newlines", ($autonewlines == 'y')) . "</td></tr>";
	echo "<tr><td class=body colspan=2><textarea class=body cols=100 rows=25 name=content style=\"width: 750px\">" . htmlentities($content) . "</textarea></td></tr>";
	echo "<tr><td class=body colspan=2 align=center>";

	if($id){
		echo "<input type=hidden name=id value=$id>";
		echo "<input class=body accesskey='s' type=submit name=action value=Save>";
		echo "<input class=body type=submit name=action value=Update>";
		echo "<input class=body type=submit name=action value=Cancel>";
	}else{
		echo "<input class=body accesskey='s' type=submit name=action value=Add>";
		echo "<input class=body type=submit name=action value=Cancel>";
	}

	echo "</td></tr>";
	echo "</form></table>";

	incFooter();
	exit;
}

function insert($name, $content, $restricted, $html, $autonl, $pagewidth){
	global $db, $mods;

	$db->prepare_query("INSERT INTO staticpages SET name = ?, content = ?, restricted = ?, html = ?, autonewlines = ?, pagewidth = #", $name, $content, ($restricted ? 'y' : 'n'), ($html ? 'y' : 'n'), ($autonl ? 'y' : 'n'), $pagewidth);

	$id = $db->insertid();

	$mods->adminlog('create static page',"create static page $id");
}

function update($id, $name, $content, $restricted, $html, $autonl, $pagewidth){
	global $db, $cache, $mods;

	$db->prepare_query("UPDATE staticpages SET name = ?, content = ?, restricted = ?, html = ?, autonewlines = ?, pagewidth = # WHERE id = #", $name, $content, ($restricted ? 'y' : 'n'), ($html ? 'y' : 'n'), ($autonl ? 'y' : 'n'), $pagewidth, $id);
	$cache->remove("staticpages-$id");

	$mods->adminlog('update static page',"update static page $id");
}

function delete($id){
	global $db, $cache, $mods;

	$db->prepare_query("DELETE FROM staticpages WHERE id = #", $id);
	$cache->remove("staticpages-$id");

	$mods->adminlog('delete static page',"delete static page $id");
}

