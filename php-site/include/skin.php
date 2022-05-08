<?

//directory length max of 24 chars (since db size is 24 char), short is good though

	$skins = array(
		"newblue"   => "New Blue",
		"rockstar"  => "Rockstar by -YONEZ-",
		"nextacular"=> "Nextacular by aannddrreeww",
		"abacus"    => "Abacus by spacemonkey",

		"friends"   => "Friends by bettycrocker",
		"somber"    => "Somber by JaZzy04",
		"earth"     => "Earth by ~saunders",
		"bigmusic"  => "Big Music by wysiwyg",

		"schematic" => "Schematic",
		"cabin"     => "Cabin",
		"candy"     => "Candy",
		"newflowers"=> "New Flowers",

		"wireframe" => "Wire Frame",
		"black"     => "Black",
		"splatter"  => "Splatter",
		"verypink"  => "Very Pink",

		"twilight"  => "Twilight",
		"megaleet"  => "Megaleet",
		"newyears"  => "New Years",
		"pink"      => "Pink",

		"vagrant"   => "Vagrant",
		"azure"     => "Azure",
		"halloween" => "Halloween",
		"flowers"   => "Pink Flowers",

		"rushhour"  => "Rush Hour",
		"carbon"    => "Carbon Fiber",
		"solar"     => "Solar Flare",
		"aurora"    => "Aurora Borealis",

		"crush"     => "Crush",
		"winter"    => "Winter",
		"orange"    => "Tangerine",
		"greenx"    => "X-Factor",

	);


initSkins();

function initSkins(){
	global $skins, $skintype, $userData, $usersdb, $cache, $msgs, $cwd, $config;

//these are written back to the global scope
	global $skin, $skindir, $skinloc, $skindata;

	$newskin = getPOSTval('newskin');
	$newskinframes = getPOSTval('newskinframes', 'bool');

	if($newskin && isset($skins[$newskin])){
		$skin = $newskin;

		if($userData['loggedIn']){
			$framesclause = "";
			if($userData['premium']){
				$userData['skintype'] = ($newskinframes ? 'frames' : 'normal');
				$framesclause = $usersdb->prepare(", skintype = ?", $userData['skintype']);
			}

			$usersdb->prepare_query("UPDATE users SET skin = ? $framesclause WHERE userid = %", $skin, $userData['userid']);
			$cache->remove("userprefs-$userData[userid]");
		}else{
			$msgs->addMsg("You must be logged in to keep your skin choice.");
		}
	}elseif($userData['loggedIn'] && isset($skins[$userData['skin']])){
		$skin = $userData['skin'];
	}else{
		$skin = key($skins); //choose first
	}

	$skindir = $config['skindir'] . "$skin/";
	$skinloc = $config['skinloc'] . "$skin/";
	
	include("$cwd/include/skins/$skin.php");


	if(!isset($skintype) || ($skintype != 'normal' && $skintype != 'frames')){
		if($userData['premium'] && $userData['skintype'] == 'normal')
			$skintype = 'normal';
		else
			$skintype = 'frames';
	}
	global $rap_pagehandler;
	if (isset($rap_pagehandler) && $rap_pagehandler->request()->skeleton()->send("respond_to?", "php_force_skin")){
		$skintype = $rap_pagehandler->request()->skeleton()->php_force_skin();
	}

	include("$cwd/include/skin$skintype.php");
}
