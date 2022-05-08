<?


//directory length max of 12 chars (since db size is 12 char)

/*
//old version
	$skins = array();

	$skins['azure'] = 	array(	'name' => "Azure",				'plus' => false);
	$skins["orange"] = 	array(	'name' => "Tangerine",			'plus' => false);
	$skins["solar"] = 	array(	'name' => "Solar Flare",		'plus' => false);
	$skins["aurora"] = 	array(	'name' => "Aurora Borealis",	'plus' => false);
	$skins["carbon"] =	array(	'name' => "Carbon Fiber",		'plus' => false);
	$skins["pink"] = 	array(	'name' => "Pink Nexopia",		'plus' => false);
	$skins["megaleet"]=	array(	'name' => "Megaleet",			'plus' => false);
	$skins["rushhour"]=	array(	'name' => "Rush Hour",			'plus' => false);
	$skins["greenx"]=	array(	'name' => "X-Factor",			'plus' => false);
	$skins["pink2"] = 	array(	'name' => "Pink Flowers",		'plus' => false);
	$skins["crush"]=	array(	'name' => "Crush",				'plus' => false);
*/

	$skins = array();

	$skins["azureframes"]=		array(	'name' => "Azure Frames",		'plus' => false);
	$skins['azure'] = 			array(	'name' => "Azure",				'plus' => true);
	$skins["orangeframes"]=		array(	'name' => "Tangerine Frames",	'plus' => false);
	$skins["orange"] = 			array(	'name' => "Tangerine",			'plus' => true);
	$skins["solarframes"] = 	array(	'name' => "Solar Flare Frames",	'plus' => false);
	$skins["solar"] = 			array(	'name' => "Solar Flare",		'plus' => true);
	$skins["auroraframes"] = 	array(	'name' => "Aurora Borealis Frames",	'plus' => false);
	$skins["aurora"] = 			array(	'name' => "Aurora Borealis",	'plus' => true);
	$skins["carbonframes"] =	array(	'name' => "Carbon Fiber Frames",'plus' => false);
	$skins["carbon"] =			array(	'name' => "Carbon Fiber",		'plus' => true);
	$skins["pinkframes"] = 		array(	'name' => "Pink Frames",		'plus' => false);
	$skins["pink"] = 			array(	'name' => "Pink",				'plus' => true);
	$skins["megaleetfr"]=		array(	'name' => "Megaleet Frames",	'plus' => false);
	$skins["megaleet"]=			array(	'name' => "Megaleet",			'plus' => true);
	$skins["rushhourfr"]=		array(	'name' => "Rush Hour Frames",	'plus' => false);
	$skins["rushhour"]=			array(	'name' => "Rush Hour",			'plus' => true);
	$skins["greenxframes"]=		array(	'name' => "X-Factor Frames",	'plus' => false);
	$skins["greenx"]=			array(	'name' => "X-Factor",			'plus' => true);
	$skins["flowerframes"] = 	array(	'name' => "Pink Flowers Frames",'plus' => false);
	$skins["flowers"] = 		array(	'name' => "Pink Flowers",		'plus' => true);
	$skins["crushframes"]=		array(	'name' => "Crush Frames",		'plus' => false);
	$skins["crush"]=			array(	'name' => "Crush",				'plus' => true);

/*
$skinuids = array(1, 5, 673, 87, 32002,2350,29536,176470,24552,18325,745,3698,8085,146539,538,673,24,162453,31104,180674, 9346, 262525, 369788);

if(isset($userid) && in_array($userid, $debuginfousers + $skinuids)){

}
*/
/*
	$skins["green"] = 	array(	'name' => "Green Nexus",		'plus' => false);
	$skins["red"] = 	array(	'name' => "Red Nexus",			'plus' => false);
	$skins["grey"] =	array(	'name' => "Catharsis",			'plus' => false);
	$skins["blue"] =	array(	'name' => "Pillz",				'plus' => false);
*/

	if(isset($_POST['newskin']) && isset($skins[$_POST['newskin']])){
		$skin = $_POST['newskin'];

		if($userData['loggedIn']){
			if($userData['premium'] || !$skins[$skin]['plus']){
				$db->prepare_query("UPDATE users SET skin = ? WHERE userid = #", $skin, $userData['userid']);
				$cache->remove(array($userid, "userprefs-$userid"));
			}else{
				$msgs->addMsg("This is a Plus only skin.");
			}
		}else{
			$msgs->addMsg("You must be logged in to keep your skin choice.");
		}
	}elseif($userData['loggedIn'] && isset($skins[$userData['skin']]) && (!$skins[$userData['skin']]['plus'] || $userData['premium'])){
		$skin = $userData['skin'];
	}else{
		$skin = key($skins);
	}

	$skindir = $imgserver . "/skins/$skin";
	include_once("skins/$skin/skin.php");


