<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"forums"))
		die("Permission denied");

	$action = getREQval('action', 'string', '');
	$id = getREQval('id', 'integer', 0);
	$key = getREQval('k', 'string', '');


	switch($action){
		case "addrealm":			addRealm();			break;
		case "Create Realm":		insertRealm();		break;
		case "editrealm":			editRealm($id);		break;
		case "Update Realm":		updateRealm($id);	break;

		case "deleterealm":
			if (!checkKey($id, $key))
				break;
			$mods->adminlog("delete realm", "Delete realm $id");
			$forums->deleteCategory($id);
			break;
		case "moveuprealm":
			$id = getREQval('id', 'integer', 0);
			if (!checkKey($id, $key))
				break;
			$mods->adminlog("moveup category", "Move up category $id");

			increasepriority($forums->db, "forumcats", $id, $forums->db->prepare("ownerid = 0"));
			$forums->invalidateCategory($id);

			break;
		case "movedownrealm":
			$id = getREQval('id', 'integer', 0);
			if (!checkKey($id, $key))
				break;

			$mods->adminlog("movedown category", "Move down category $id");

			decreasepriority($forums->db, "forumcats", $id, $forums->db->prepare("ownerid = 0"));
			$forums->invalidateCategory($id);

			break;
	}

	listForums();	//exit

function insertRealm(){
	global $msgs, $forums, $mods;

	$defaults = array(
		'name' => '',
		'special' => false,
		);

	$data = getPOSTval('data', 'array');

	extract(setDefaults($data, $defaults));

	$parent=0;

	$error=false;
	if($name==""){
		$msgs->addMsg("Realm needs a name");
		$error=true;
	}

	if($error)
		addRealm($data); //exit

	$mods->adminlog('create forum realm',"Create Forum Realm: $name");

	$priority = getMaxPriority($forums->db, "forumcats");

	$forums->createCategory($name, ($special ? 'y' : 'n'), 0, $priority);

	$msgs->addMsg("Realm Created.");
}

function editRealm($id){
	global $forums;

	$res = $forums->db->prepare_query("SELECT name, official FROM forumcats WHERE id = ?", $id);
	$data = $res->fetchrow();

	extract($data);

	$template = new template('forums/forumadmin/editRealm');
	
	$template->set('specialChecked', ($official=='y'? 'checked' : ''));
	$template->set('id', $id);
	$template->set('name', $name);
	$template->display();
	
	exit;
}

function addRealm(){
	$defaults = array(
		'name' => '',
		'special' => false,
		);

	$data = getPOSTval('data', 'array');

	extract(setDefaults($data, $defaults));
	
	$template = new template('forums/forumadmin/addRealm');
	
	$template->set('specialChecked', ($special ? 'checked' : ''));
	$template->set('name', $name);
	$template->display();
	
	exit;
}

function updateRealm($id){
	global $msgs, $forums, $mods;

	$defaults = array(
		'name' => '',
		'special' => false,
		);

	$data = getPOSTval('data', 'array');

	extract(setDefaults($data, $defaults));

	$parent=0;


	$error=false;
	if($name==""){
		$msgs->addMsg("Realm needs a name");
		$error=true;
	}
/*	if($parent!=0){
		$query = "SELECT id FROM forums WHERE id='$parent'";
		$result = $forums->db->query($query);
		if($forums->db->numrows($result)==0){
			$msgs->addMsg("Must have a valid parent forum");
			$error=true;
		}
	}
*/

	if($error)
		editRealm($id); //exit

	$mods->adminlog('update forum realm',"Create Forum Realm: $name");

	$forums->modifyCategory($id, array('name' => $name, 'official' => ($special ? 'y' : 'n')));

	$msgs->addMsg("Realm Updated.");
}


function listForums(){
	global $childdata, $mods, $config, $forums;

	$mods->adminlog('list forums',"List Forums");
	$template = new template('forums/forumadmin/listForums');
	
	$template->set('config', $config);
	
	$cats = $forums->getCategories(0);
	$template->set('cats', $cats);
	
	$officialforums = $forums->getOfficialForumList();
	$template->set('officialforums', $officialforums);
	
	$forumobjs = $forums->getForums($officialforums['forums']);
	
	$i = -1;
	foreach($cats as $cat){
		$i++;
		$key[$i] = makeKey($cat['id']);

		if($cat['id'] != 0)
			$editrealm[$i] = true;

		if($cat['priority'] > 1 && $cat['id'] != 0)
			$moveuprealm[$i] = true;
		
		if($cat['priority'] < (count($cats) - 1) && $cat['id'] != 0) // not including the uncategorized category.
			$movedownrealm[$i] = true;
		
		if(!isset($officialforums['categories'][$cat['id']]) && $cat['id'] != 0)
			$deleterealm[$i] = true;
		// now do the forums
		$displayForum[$i] = true;
		if (!isset($officialforums['categories'][$cat['id']])) {
			$displayForum[$i] = false;
			continue;
		}
		
		

		$forumlines[$i] = array_flip($officialforums['categories'][$cat['id']]);
		foreach ($forumlines[$i] as $fid => $fval)
		{
			$forumlines[$i][$fid] = $forumobjs[$fid];
		}

		$forums->sortForums($forumlines[$i], true);
	}

	
	$template->set('editrealm', $editrealm);
	$template->set('moveuprealm', $moveuprealm);
	$template->set('movedownrealm', $movedownrealm);
	$template->set('deleterealm', $deleterealm);
	$template->set('displayForum', $displayForum);
	
	$template->set('forumlines', $forumlines);
	$template->set('key', $key);
	$template->display();
}

