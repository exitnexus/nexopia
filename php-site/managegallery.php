<?

	$login=2;

	require_once("include/general.lib.php");

	$perms = array("anyone" => "Visible to Anyone", "loggedin" => "Logged In Users Only", "friends" => "Friends Only");

	$isAdmin = $mods->isAdmin($userData['userid'],'editgallery');

	if(!isset($uid) || !$isAdmin)
		$uid = $userData['userid'];


	switch($action){
		case "moveup":
			if($userData['userid']==$uid && isset($cat)){
				increasepriority($db, "gallery", $id, $db->prepare("userid = # && category = #", $uid, $cat), true);
				setFirstGalleryPic($uid, $cat);
			}
			break;
		case "movedown":
			if($userData['userid']==$uid && isset($cat)){
				decreasepriority($db, "gallery", $id, $db->prepare("userid = # && category = #", $uid, $cat), true);
				setFirstGalleryPic($uid, $cat);
			}
			break;
		case "deletepic":
			$db->prepare_query("SELECT userid FROM gallery WHERE id = #", $id);
			if($db->numrows()){
				$line = $db->fetchrow();

				removeGalleryPic($id);

				setFirstGalleryPic($uid,$cat);
				if($uid != $userData['userid'])
					$mods->adminlog("delete gallery picture", "Delete Gallery picture: userid $uid, gallery $cat");
			}
			break;
		case "Upload":
			if($uid == $userData['userid'] && isset($cat)){
				$db->prepare_query("SELECT count(*) FROM gallery WHERE userid = #", $uid);
				$numpics = $db->fetchfield();

				for($i=0; $i<5; $i++){
					if(empty($userfile[$i]))
						continue;
					if(!isset($description[$i]))
						$description[$i]="";
					if($numpics++ < $config['maxgallerypics'])
						addGalleryPic($userfile[$i], $cat, $description[$i]);
				}
				setFirstGalleryPic($uid,$cat);
			}
			break;
		case "editpic":
			if($uid != $userData['userid'])
				$mods->adminlog("edit gallery picture", "Edit user Gallery: userid $uid");
			editpic($id); //exit
		case "Update Picture":
			if($uid != $userData['userid'])
				$mods->adminlog("update gallery picture", "Update user Gallery: userid $uid");
			updatepic($id,$description);
			break;

		case "Create Gallery":
			if($uid == $userData['userid'])
			createGalleryCat($name, $description, $permission);
			setGalleryVisibility($uid);
			break;
		case "editcat":
			if($uid != $userData['userid'])
				$mods->adminlog("edit gallery category", "Update user Gallery: userid $uid");
			editgallery($id);
			break;
		case "Update Gallery":
			if($uid != $userData['userid'])
				$mods->adminlog("update gallery category", "Update user Gallery: userid $uid");
			updategallery($id, $name, $description, $permission);
			setGalleryVisibility($uid);
			break;
		case "deletecat":
			if($uid != $userData['userid'])
				$mods->adminlog("delete gallery category", "Delete user Gallery: userid $uid");
			deletegallery($id);
			setGalleryVisibility($uid);
			break;
	}

	if(!empty($cat))
		listPictures($cat); //exit

	listCategories(); //exit

/////////////////////////////



function listCategories(){
	global $uid, $userData, $perms, $db, $config, $perms;

	$db->prepare_query("SELECT id, name, firstpicture, description, permission FROM gallerycats WHERE userid = ? ORDER BY name", $uid);

	$rows = array();
	while($line = $db->fetchrow())
		$rows[] = $line;

	incHeader();

	echo "<table width=100%>";

	echo "<tr>";
	echo "<td class=header>Pictures</td>";
	echo "<td class=header>Description</td>";
	echo "<td class=header>Functions</td>";
	echo "</tr>";

	foreach($rows as $line){
		echo "<tr>";
		echo "<td class=body>";
		if($line['firstpicture'] > 0)
			echo "<a class=body href=managegallery.php?cat=$line[id]><img src=http://" . chooseImageServer($line['firstpicture']) . $config['gallerythumbdir'] . floor($line['firstpicture']/1000) . "/$line[firstpicture].jpg border=0></a>";
		else
			echo "No pictures";
		echo "</td>";
		echo "<td class=body valign=top><a class=body href=managegallery.php?uid=$uid&cat=$line[id]><b>$line[name]</b></a> - " . $perms[$line['permission']] . "<br>$line[description]</td>";

		echo "<td class=body><a class=body href=\"$_SERVER[PHP_SELF]?uid=$uid&action=editcat&id=$line[id]\"><img src=$config[imageloc]edit.gif border=0 alt='Edit'></a>";
		echo " <a class=body href=\"javascript:confirmLink('$_SERVER[PHP_SELF]?uid=$uid&action=deletecat&id=$line[id]','delete this gallery')\"><img src=$config[imageloc]delete.gif border=0 alt='Delete'></a></td>";

		echo "</tr>";
	}
	echo "</table>";

	echo "<table align=center>";

	echo "<tr><td class=header colspan=2>Create a New Gallery</td></tr>";

	echo "<form action=$_SERVER[PHP_SELF] method=post enctype=\"multipart/form-data\">\n";
	echo "<tr><td class=body>Gallery Name:</td><td class=body><input class=body type=test name=name maxlenght=32 size=30></td></tr>";
	echo "<tr><td class=body>Description:</td><td class=body><input class=body type=text name=description maxlength=255 size=40></td></tr>";
	echo "<tr><td class=body>Permissions:</td><td class=body><select class=body name=permission>" . make_select_list_key($perms) . "</select></td></tr>";
	echo "<tr><td class=body></td><td><input class=body type=submit name=action value=\"Create Gallery\"></td></tr>\n";
	echo "</form>";

	echo "</table>";

	incFooter();

	exit;
}

function listPictures($cat){
	global $db, $uid, $userData, $config;

	$db->prepare_query("SELECT name FROM gallerycats WHERE id = ? && userid = ?", $cat, $uid);
	if($db->numrows() == 0)
		return;
	$galleryname = $db->fetchfield();

	$db->prepare_query("SELECT count(*) FROM gallery WHERE userid = ?", $uid);
	$numpics = $db->fetchfield();

	$db->prepare_query("SELECT id,description,priority FROM gallery WHERE userid = ? && category = ? ORDER BY priority", $uid, $cat);

	$rows = array();
	while($line = $db->fetchrow())
		$rows[] = $line;

	incHeader();

	echo "<table width=100%>";

	echo "<tr><td class=body colspan=2><a class=body href=$_SERVER[PHP_SELF]>Gallery</a> > <a class=body href=$_SERVER[PHP_SELF]?cat=$cat>$galleryname</a></td></tr>";

	echo "<tr>";
	echo "<td class=header>Pictures</td>";
	echo "<td class=header>Description</td>";
	echo "<td class=header>Functions</td>";
	echo "</tr>";

	$i=1;
	$ids = array();
	foreach($rows as $line){
		if($line['priority'] != $i){
			$ids[$line['id']] = $i;
			$line['priority'] = $i;
		}
		$i++;

		echo "<tr>";
		echo "<td class=body><a href=gallery.php?uid=$uid&cat=$cat&picid=$line[id]><img src=http://" . chooseImageServer($line['id']) . $config['gallerythumbdir'] . floor($line['id']/1000) . "/$line[id].jpg border=0></a></td>";
		echo "<td class=body>$line[description]</td>";

		echo "<td class=body><a class=body href=\"$_SERVER[PHP_SELF]?uid=$uid&action=editpic&cat=$cat&id=$line[id]\"><img src=$config[imageloc]edit.gif border=0 alt='Edit'></a>";
		if($userData['userid']==$uid){
			echo " <a class=body href=\"$_SERVER[PHP_SELF]?action=moveup&cat=$cat&id=$line[id]\"><img src=$config[imageloc]up.png border=0 alt='Move Up'></a>";
			echo " <a class=body href=\"$_SERVER[PHP_SELF]?action=movedown&cat=$cat&id=$line[id]\"><img src=$config[imageloc]down.png border=0 alt='Move Down'></a>";
		}
		echo " <a class=body href=\"javascript:confirmLink('$_SERVER[PHP_SELF]?uid=$uid&cat=$cat&action=deletepic&id=$line[id]','delete this picture')\"><img src=$config[imageloc]delete.gif border=0 alt='Delete'></a></td>";

		echo "</tr>";
	}
	echo "</table><br><br>";

	if(count($ids)){
		foreach($ids as $id => $i)
			$db->prepare_query("UPDATE gallery SET priority = ? WHERE id = ?", $i, $id);
	}

	if($userData['userid']==$uid && $numpics < $config['maxgallerypics']){
		echo "<table align=center>";

		echo "<tr><td class=header colspan=2>Upload Pictures</td></tr>";

		echo "<form action=$_SERVER[PHP_SELF] method=post enctype=\"multipart/form-data\">\n";
		echo "<input type=hidden name=cat value=$cat>";

		for($i = 0; $i < 5; $i++){
			echo "<tr><td class=body>Select a Picture:</td><td class=body><input class=body type=file name=userfile[$i] size=30></td></tr>";
			echo "<tr><td class=body>Enter a Description:</td><td class=body><input class=body type=text name=description[$i] maxlength=64 size=45></td></tr>";
			echo "<tr><td class=body colspan=2>&nbsp;</td></tr>";
		}

		echo "<tr><td class=body colspan=2>Uploaded pictures must be legal, clean (ie no porn or excessive violence),<br>and you must have permission from the copyright holder to use it.<br>If the files you are uploading are large, you may want to upload them individually.</td></tr>";
		echo "<tr><td class=body></td><td><input class=body type=submit name=action value=Upload></td></tr>\n";
		echo "</form>";

		echo "</table>";
	}

	incFooter();
	exit;
}

function createGalleryCat($name, $description, $permission){
	global $db, $msgs, $uid;

	$db->prepare_query("INSERT INTO gallerycats SET name = ?, description = ?, permission = ?, userid = ?", removeHTML($name), removeHTML($description), $permission, $uid);

	$msgs->addMsg("Gallery Created");
}

function editpic($id){
	global $uid, $config, $db, $cat;

	$db->prepare_query("SELECT description FROM gallery WHERE id = ? && userid = ?", $id, $uid);
	$data = $db->fetchrow();

	if(!$data)
		return;

	incHeader();

	echo "<table width=100%>\n";
	echo "<form action=$_SERVER[PHP_SELF] method=post>\n";

	echo "<tr><td class=body colspan=2><img src=\"http://" . chooseImageServer($id) . $config['gallerypicdir'] . floor($id/1000) . "/$id.jpg\"></td></tr>";

	echo "<input type=hidden name=id value=$id>";
	echo "<input type=hidden name=uid value=$uid>";
	echo "<input type=hidden name=cat value=$cat>";


	echo "<tr><td class=body>Description:</td><td class=body><input class=body type=text name=description value=\"" . htmlentities($data['description']) . "\" size=40 maxlength=64></td></tr>";
	echo "<tr><td class=body></td><td class=body>";
	echo "<input class=body type=submit name=action value='Update Picture'>";
	echo "<input class=body type=submit name=action value=Cancel>";
	echo "</td></tr>\n";

	echo "</table>\n";

	incFooter();
	exit;
}

function updatepic($id,$description){
	global $uid,$msgs,$isAdmin, $db;

	$db->prepare_query("UPDATE gallery SET description = ? WHERE id = ? && userid = ?", removeHTML($description), $id, $uid);

	$msgs->addMsg("Update Complete");
}


function editgallery($id){
	global $uid, $db, $perms;

	$db->prepare_query("SELECT name,description, permission FROM gallerycats WHERE id = ? && userid = ?", $id, $uid);
	$data = $db->fetchrow();

	if(!$data)
		return;

	incHeader();

	echo "<table width=100%>\n";
	echo "<form action=$_SERVER[PHP_SELF] method=post>\n";

	echo "<input type=hidden name=id value=$id>";
	echo "<input type=hidden name=uid value=$uid>";

	echo "<tr><td class=body>Name:</td><td class=body><input class=body type=text name=name value=\"" . htmlentities($data['name']) . "\" size=30 maxlength=32></td></tr>";
	echo "<tr><td class=body>Description:</td><td class=body><input class=body type=text name=description value=\"" . htmlentities($data['description']) . "\" size=40 maxlength=255></td></tr>";
	echo "<tr><td class=body>Permissions:</td><td class=body><select class=body name=permission>" . make_select_list_key($perms,$data['permission']) . "</select></td></tr>";
	echo "<tr><td class=body></td><td class=body>";
	echo "<input class=body type=submit name=action value='Update Gallery'>";
	echo "<input class=body type=submit name=action value=Cancel>";
	echo "</td></tr>\n";

	echo "</table>\n";

	incFooter();
	exit;
}

function updategallery($id, $name, $description, $permission){
	global $uid,$msgs,$isAdmin, $db;

	$db->prepare_query("UPDATE gallerycats SET name = ?, description = ?, permission = ? WHERE id = ? && userid = ?", removeHTML($name), removeHTML($description), $permission, $id, $uid);

	$msgs->addMsg("Update Complete");
}

function deletegallery($id){
	global $uid,$msgs,$isAdmin, $db;
/*
	$result = $db->prepare_query("SELECT id FROM gallery WHERE userid = ? && category = ?", $uid, $id);

	while($line = $db->fetchrow($result))
		removeGalleryPic($id);
*/
	$db->prepare_query("DELETE FROM gallery WHERE userid = ? && category = ?", $uid, $id);
	$db->prepare_query("DELETE FROM gallerycats WHERE userid = ? && id = ?", $uid, $id);

	$msgs->addMsg("Gallery Deleted");
}

function setGalleryVisibility($uid){
	global $db;

	$db->begin();

	$db->prepare_query("SELECT permission FROM gallerycats WHERE userid = ?", $uid);

	$permission = "none";
	while($line = $db->fetchrow()){
		switch($line['permission']){
			case "anyone":
				$permission = "anyone";
				break 2;
			case "loggedin":
				$permission = "loggedin";
				break;
			case "friends":
				if($permission != "loggedin")
					$permission = "friends";
		}
	}

	$db->prepare_query("UPDATE users SET gallery = ? WHERE userid = ?", $permission, $uid);
	$db->commit();
}

