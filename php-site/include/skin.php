<?


//directory length max of 12 chars
	$skins = array();

	$skins['azure'] = 	array(	'name' => "Azure",
								'plus' => false);
	$skins["orange"] = 	array(	'name' => "Tangerine",
								'plus' => false);
	$skins["solar"] = 	array(	'name' => "Solar Flare",
								'plus' => false);
	$skins["aurora"] = 	array(	'name' => "Aurora Borealis",
								'plus' => false);
	$skins["carbon"] =	array(	'name' => "Carbon Fiber",
								'plus' => false);
	$skins["pink"] = 	array(	'name' => "Pink Nexopia",
								'plus' => false);
	$skins["megaleet"]=	array(	'name' => "Megaleet",
								'plus' => false);

if(isset($userid) && ($userid==1 || $userid == 673 || $userid == 87)){
	$skins["crush"]=	array(	'name' => "Crush",	'plus' => false);
	$skins["pink2"]=	array(	'name' => "Pink Nexopia",'plus' => false);
}

/*
	$skins["green"] = 	array(	'name' => "Green Nexus",
								'plus' => false);
	$skins["red"] = 	array(	'name' => "Red Nexus",
								'plus' => false);
	$skins["grey"] =	array(	'name' => "Catharsis",
								'plus' => false);
	$skins["blue"] =	array(	'name' => "Pillz",
								'plus' => false);
*/
	if(isset($newskin) && in_array($newskin,array_keys($skins))){
		$skin = $newskin;

		if($userData['loggedIn']){
			if(($userData['premium'] || !$skins[$skin]['plus'])){
				$db->prepare_query("UPDATE users SET skin = ? WHERE userid = ?", $skin, $userData['userid']);
			}else{
				$msgs->addMsg("This is a Plus only skin.");
			}
		}else{
			$msgs->addMsg("You must be logged in to keep your skin choice");
		}
	}elseif($userData['loggedIn'] && in_array($userData['skin'],array_keys($skins))){
		$skin = $userData['skin'];
	}else{
		$skin = reset(array_keys($skins));
	}

	$skindir = $imgserver . "/skins/$skin";
	include_once("skins/$skin/skin.php");
	include_once("skins/skin.php");

