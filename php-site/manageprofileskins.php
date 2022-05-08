<?

	$login=1;

	require_once("include/general.lib.php");

	$uid = $userData['userid'];

	switch($action){
		case "add":
			addSkin();
			//exit

		case "edit":
			if($id = getREQval('id', 'int'))
				addSkin($id);//exit
			break;

		case "Add":
			if($data = getREQval('data', 'array'))
				insertSkin($data);
			break;

		case "Update":
			$id = getPOSTval('id', 'int');
			$data = getPOSTval('data', 'array');
			if($id && $data)
				updateSkin($id, $data);
			break;

		case "Test":
			$id = getPOSTval('id', 'int', 0);
			$data = getPOSTval('data', 'array');
			addSkin($id, $data);
			break;

		case "delete":
			if(($id = getREQval('id', 'int')) && ($k = getREQval('k')) && checkKey($id, $k))
				deleteSkin($id);
			break;
	}

	listSkins();

///////////////////////

function listSkins(){
	global $uid, $db;

	$res = $db->prepare_query("SELECT id, name FROM profileskins WHERE userid = ? ORDER BY name", $uid);

	$rows = array();
	while($line = $res->fetchrow())
		$rows[$line['id']] = $line['name'];

	$template = new template('profiles/manageprofileskins/listSkins');
	$template->set('rows', $rows);

	foreach($rows as $id => $name){
		$key[$id] = makekey($id);
	}
	$template->set('key', $key);
	$template->display();
	exit;
}


function addSkin($id = 0, $skindata = array()){
	global $data, $uid, $db, $config;

	$headerbg = "#";
	$headertext = "#";
	$headerlink = "#";
	$headerhover = "#";
	$bodybg = "#";
	$bodybg2 = "#";
	$bodytext = "#";
	$bodylink = "#";
	$bodyhover = "#";
//	$votelink = "#";
//	$votehover = "#";
	$online = "#";
	$offline = "#";
	$name = "";

	if($id && !count($skindata)){
		$res = $db->prepare_query("SELECT name, data FROM profileskins WHERE id = ? && userid = ?", $id, $uid);
		$line = $res->fetchrow();
		if($line){
			$skindata = decodeSkin($line['data']);
			extract($skindata);
			$name = $line['name'];
		}else{
			$data = array();
		}
	}elseif(is_array($skindata)){
		extract($skindata);
		foreach($skindata as $n => $v)
			if(substr($v, 0, 1) == '#')
				$skindata[$n] = substr($v, 1);

	}

	$template = new template('profiles/manageprofileskins/addSkin');
	$template->set('showStyle', count($skindata));
	$template->set('skindata', $skindata);
	$template->set('config', $config);
	$template->set('id', $id);
	$template->set('name', $name);
	$template->set('headerbg', $headerbg);
	$template->set('headertext', $headertext);
	$template->set('headerlink', $headerlink);
	$template->set('headerhover', $headerhover);
	$template->set('bodybg', $bodybg);
	$template->set('bodybg2', $bodybg2);
	$template->set('bodytext', $bodytext);
	$template->set('bodylink', $bodylink);
	$template->set('bodyhover', $bodyhover);
	$template->set('online', $online);
	$template->set('offline', $offline);
	$template->display();
	exit;
}

function insertSkin($data){
	global $uid, $db, $msgs;

	$data2 = encodeSkin($data);

	if(!$data2){
		$msgs->addMsg("You must fill out all boxes with valid RGB HEX colors");
		addSkin(0,$data); //exit
	}

	if(empty($data['name'])){
		$msgs->addMsg("You must give it a name");
		addSkin(0,$data); //exit
	}

	$db->prepare_query("INSERT INTO profileskins SET userid = ?, name = ?, data = ?", $uid, $data['name'], $data2);
}

function updateSkin($id, $data){
	global $uid, $db, $msgs, $cache;

	$data2 = encodeSkin($data);

	if(!$data2){
		$msgs->addMsg("You must fill out all boxes with valid RGB HEX colors");
		addSkin($id,$data); //exit
	}

	if(empty($data['name'])){
		$msgs->addMsg("You must give it a name");
		addSkin($id,$data); //exit
	}

	$db->prepare_query("UPDATE profileskins SET name = ?, data = ? WHERE id = ? && userid = ?", $data['name'], $data2, $id, $uid);
	$cache->remove("profileskin-$id");
}

function deleteSkin($id){
	global $db, $uid, $cache;

	$db->prepare_query("DELETE FROM profileskins WHERE id = ? && userid = ?", $id, $uid);
	$cache->remove("profileskin-$id");
}

