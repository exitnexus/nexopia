<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"config"))
		die("Permission denied");

	switch($action){
		case "Add":			add($funcname,$side,$enabled);			break;
		case "delete":		delete($id);							break;
		case "moveup":		increasepriority($db, "blocks", $id);	break;
		case "movedown":	decreasepriority($db, "blocks", $id);	break;
		case "moveright":	moveright($id);							break;
		case "moveleft":	moveleft($id);							break;
		case "disable":		disable($id);							break;
		case "enable":		enable($id);							break;
		case "edit":		edit($id);								break;
		case "Update":		update($id,$funcname,$side,$enabled);	break;
	}

	listBlocks();
	exit;

////////////////////

function add($funcname,$side,$enabled){
	global $msgs,$db;

    $priority = getMaxPriority($db, "blocks");

	$db->prepare_query("INSERT INTO blocks SET priority = ?, funcname = ?, side = ?,enabled = ?", $priority, $funcname, $side, (isset($enabled)? "y":"n") );

	$msgs->addMsg("Block Added");
}

function delete($id){
	global $msgs, $db;
	if(!isset($id)) return false;

	setMaxPriority($db, "blocks", $id);

	$db->prepare_query("DELETE FROM blocks WHERE id = ?", $id);

	$msgs->addMsg("Block Deleted");
}

function moveright($id){
	global $msgs, $db;
	if(!isset($id)) return false;

	$db->prepare_query("UPDATE blocks SET side='r' WHERE id = ?", $id);

	$msgs->addMsg("Block now on right");
}

function moveleft($id){
	global $msgs, $db;
	if(!isset($id)) return false;

	$db->prepare_query("UPDATE blocks SET side='l' WHERE id = ?", $id);

	$msgs->addMsg("Block now on left");

}

function disable($id){
	global $msgs, $db;
	if(!isset($id)) return false;

	$db->prepare_query("UPDATE blocks SET enabled='n' WHERE id = ?", $id);

	$msgs->addMsg("Block Disabled");
}

function enable($id){
	global $msgs, $db;
	if(!isset($id)) return false;

	$db->prepare_query("UPDATE blocks SET enabled='y' WHERE id = ?", $id);

	$msgs->addMsg("Block enabled");
}

function edit($id){
	global $msgs, $db;
	if(!isset($id)) return false;

	$res = $db->prepare_query("SELECT side,enabled,funcname FROM blocks WHERE id = ?", $id);

	$line = $res->fetchrow();

	incHeader();

	echo "<table><form method=POST action=\"$_SERVER[PHP_SELF]\">\n";
	echo "<input type=hidden name=id value=\"$id\">";
	echo "<tr><td class=body>Function Name</td><td class=body><input class=body type=text name=funcname value=\"$line[funcname]\"></td></tr>\n";
	echo "<tr><td class=body>Side</td><td class=body><input class=body type=radio name=side value=l" . ($line['side']=='l'? " checked":"") . ">Left <input class=body type=radio name=side value=r" . ($line['side']=='r'? " checked":"") . ">Right</td></tr>";
	echo "<tr><td class=body>Enabled</td><td class=body><input class=body type=checkbox name=enabled " . ($line['enabled']=='y'? " checked":"") . "></td></tr>";
	echo "<tr><td class=body></td><td class=body><INPUT class=body TYPE=submit name=action VALUE=\"Update\"><input class=body type=submit value=Cancel></td></tr>\n";
	echo "</form></table>";

	incFooter();
	exit;
}

function update($id,$funcname,$side,$enabled){
	global $msgs, $db;
	if(!isset($id)) return false;

	$db->prepare_query("UPDATE blocks SET side = ?, enabled = ?, funcname = ? WHERE id = ?", $side, (isset($enabled) ? "y":"n"), $funcname, $id);

	$msgs->addMsg("Updated");
}

function listBlocks(){
	global $msgs, $db, $config;

	$db->query("SELECT * FROM blocks ORDER BY 'priority'");

	$data=array();
	while($line = $db->fetchrow())
		$data[]=$line;

	incHeader();

	echo "<table width=100%>\n";
	echo "<tr><td class=header></td><td class=header></td><td class=header></td><td class=header></td><td class=header></td><td class=header></td><td class=header>FuncName</td></tr>\n";
	foreach($data as $line){
		echo "<tr><td class=body><a class=body href=\"javascript:confirmLink('$_SERVER[PHP_SELF]?action=delete&id=$line[id]','delete this block')\"><img src=$config[imageloc]delete.gif border=0></a></td>";
		echo "<td class=body><a class=body href=\"$_SERVER[PHP_SELF]?action=moveup&id=$line[id]\"><img src=$config[imageloc]up.png border=0></a></td>";
		echo "<td class=body><a class=body href=\"$_SERVER[PHP_SELF]?action=movedown&id=$line[id]\"><img src=$config[imageloc]down.png border=0></a></td>";
		echo "<td class=body><a class=body href=\"$_SERVER[PHP_SELF]?action=" . ($line['side']=='l'?"moveright":"moveleft") ."&id=$line[id]\"><img src=$config[imageloc]".($line['side']=='l'?"right":"left").".png border=0></a></td>";
		echo "<td class=body><a class=body href=\"$_SERVER[PHP_SELF]?action=edit&id=$line[id]\"><img src=$config[imageloc]edit.gif border=0></a></td>";
		echo "<td class=body><a class=body href=\"$_SERVER[PHP_SELF]?action=" . ($line['enabled']=='y'?"disable":"enable") ."&id=$line[id]\">".($line['enabled']=='y'?"Disable":"Enable")."</a></td>";
		echo "<td class=body>$line[funcname]</td></tr>\n";
	}
	echo "</table><br>\n";

	echo "<table><form method=POST action=\"$_SERVER[PHP_SELF]\">\n";
	echo "<tr><td class=body>Function Name</td><td class=body><input class=body type=text name=funcname></td></tr>\n";
	echo "<tr><td class=body>Side</td><td class=body><input class=body type=radio name=side value=l>Left <input class=body type=radio name=side value=r>Right</td></tr>";
	echo "<tr><td class=body>Enabled</td><td class=body><input class=body type=checkbox name=enabled></td></tr>";
	echo "<tr><td class=body></td><td class=body><INPUT class=body TYPE=submit name=action VALUE=\"Add\"></td></tr>\n";
	echo "</form></table>";


	incFooter();
	exit;
}

