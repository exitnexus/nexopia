<?

	$login=2;

	require_once("include/general.lib.php");

	$isForumAdmin = $mods->isAdmin($userData['userid'],'forums');

	if(!$isForumAdmin){
		$res = $forums->db->prepare_query("SELECT * FROM forums WHERE ownerid = #", $userData['userid']);
		$owned = $res->fetchrowset();

		if($owned){

			$classes = array('body','body2');
			$i = -1;

			foreach($owned as $line){
				$i++;
				if ($i > 1)
					$classes[$i] = $classes[$i%2];
			}
			$template = new template('forums/forumcreateforum/forumOwned');
			$template->set('owned', $owned);
			$template->set('classes', $classes);
			$template->display();
			exit;
		}

		$res = $forums->db->prepare_query("SELECT unmutetime FROM forummute WHERE userid = # && forumid = 0", $userData['userid']);
		$mute = $res->fetchrow();

		if($mute && $mute['unmutetime'] > time()){
			$template = new template('forums/forumcreateforum/muted');
			$template->display();
			exit;
		}
	}


	if($action == "Create Forum")
		insertForum(getREQval('data', 'array', array()));

	addForum();	//exit


function addForum($data = array()){
	global $isForumAdmin, $userData, $forums;

	$cats = $forums->getCategories();
	$outputcats = array(-1 => 'Select a category');
	foreach ($cats as $catid => $cat)
	{
		if ($isForumAdmin || $cat['official'] != 'y')
			$outputcats[$catid] = $cat['name'];
	}

	$name="";
	$description="";
	$catid = getREQval('catid', 'integer', -1); // allow this one to be done by a GET as well.
	$autolock=0;
	$edit='n';
	$countposts='y';
	$official='n';
	$public='y';
	$mute='n';

	if (isset($_POST['data']))
		extract($_POST['data']);

	$selectCategory = make_select_list_key($outputcats, $catid);
	$radioAllowPostEdit = make_radio_key("data[edit]", $forums->editlengths, $edit);
	$radioOfficial = make_radio_key("data[official]", array('y'=>"Yes", 'n'=>"No"),$official);
	$radioPublic = make_radio_key("data[public]", array('y'=>"Yes", 'n'=>"No"),$public);
	$radioMute = make_radio_key("data[mute]", array('y'=>"Yes", 'n'=>"No"),$mute);

	$template = new template('forums/forumcreateforum/addForum');
	$template->set('name', $name);
	$template->set('description', $description);
	$template->set('selectCategory', $selectCategory);
	$template->set('radioAllowPostEdit', $radioAllowPostEdit);
	$template->set('radioOfficial', $radioOfficial);
	$template->set('radioPublic', $radioPublic);
	$template->set('radioMute', $radioMute);
	$template->set('isForumAdmin', $isForumAdmin);

	$template->display();
	exit;
}

function insertForum($data){
	global $msgs, $isForumAdmin, $userData, $forums, $userData, $cache;

	$name="";
	$description="";
	$catid=-1;
	$parent=0;
	$autolock=0;
	$edit='n';
	$countposts='y';
	$official='n';
	$public='y';
	$mute='n';

	extract($data);

	$error=false;
	if(trim($name)=="" || strlen(trim($name)) < 4){
		$msgs->addMsg("Forum needs a name");
		$error=true;
	}
	if($description==""){
		$msgs->addMsg("Forum needs a description");
		$error=true;
	}

	$cats = $forums->getCategories();
	if(!isset($cats[$catid])){
		$msgs->addMsg("Forum needs an initial category");
		$error=true;
	}

	if (!$isForumAdmin && $cats[$catid]['official'] == 'y')
	{
		$msgs->addMsg("You do not have permission to create a forum in '{$cats[$catid][name]}'");
		$catid = -1;
		$error=true;
	}
	if ($catid != -1 && $cats[$catid]['official'] == 'y')
	{
		$official = 'y'; // force official forums in official categories.
	}
	if (!$isForumAdmin && $official == 'y')
	{
		$msgs->addMsg("You do not have permission to create an official forum.");
		$error = true;
	}

	if($public != 'n')
		$public='y';

	$res = $forums->db->prepare_query("SELECT id FROM forums WHERE name = ?", $name);
	if($res->fetchrow()){
		$msgs->addMsg("Forum name already taken");
		$error=true;
	}

	if($error)
		addForum($data); //exit

	$unofficial = ($official=='y')? 'n' : 'y';

	$forums->db->prepare_query("INSERT INTO forums SET name = ?, description = ?, categoryid = #, autolock = ?, edit = ?, official = ?, unofficial = ?, public = ?, mute = ?, ownerid = ?",
							removehtml($name), removehtml($description), $catid, $autolock, $edit, $official, $unofficial, $public, $mute, $userData['userid']);

	$fid = $forums->db->insertid();

	$forums->db->prepare_query("INSERT IGNORE INTO foruminvite SET userid = ?, categoryid = 0, forumid = ?", $userData['userid'], $fid);

	$cache->remove("publicforumlist-mostactive");
	$cache->remove("subforumlist-" . $userData['userid']);

	header("location: forumthreads.php?fid=$fid");
	exit;
}
