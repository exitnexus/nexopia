<?

//directory length max of 24 chars (since db size is 24 char), short is good though

	global $configprofile;
	global $msgs, $wiki;
	if ($msgs && $wiki) {
		$text = $wiki->getPage("/SiteText/sitestripe");
		if(strstr($configprofile, "live") !== false && $text && strlen($text['output']) > 0)
		{
			$msgs->addMsg($text['output']);
		}
		else if(strstr($configprofile, "beta") !== false && $text && strlen($text['output']) > 0)
		{
			$msgs->addMsg($text['output']);
		}
	}

	$skins = array(
		"newblack"	=> "Erratic",
		"sharkethic"  => "Shark Ethic by beef.",
		"rocktopus"  => "Rocktopus by ROCKETBOTTLE",
		"fallingsky"  => "Falling Sky by heymountains",
		
		"orangesoda"  => "Orange Soda by peace",
		"toughlove"  => "Tough Love by .Pxy.",
		"nouveau"  => "Nouveau",
		"karma"  => "Karma",
		
		"supernova"  => "Supernova",
		"whitewash"  => "Whitewash",
		"nexmas"  => "Nexmas",
		"detention"  => "Detention",
		
		"add"	=> "A.D.D.",
		"mindfield"   => "Mindfield",
		"molotov"=> "Molotov",
		"newblue"   => "Retro Nex",
		
		"rockstar"  => "Rockstar by -YONEZ-",
		"nextacular"=> "Nextacular by aannddrreeww",
		"abacus"    => "Abacus by spacemonkey",
		"friends"   => "Friends by bettycrocker",
		
		"somber"    => "Somber by JaZzy04",
		"earth"     => "Earth by ~saunders",
		"bigmusic"  => "Big Music by wysiwyg",
		"schematic" => "Schematyk",
		
		"cabin"     => "CabinFeever",
		"candy"     => "CandyCoted",
		"newflowers"=> "FlwerPower",
		"wireframe" => "WyrFrame",
		
		"black"     => "PermanentDark",
		"splatter"  => "SplatterPunk",
		"verypink"  => "Very Pink",
		"twilight"  => "Twilight",
		
		"megaleet"  => "Megaleet",
		"newyears"  => "New Years",
		"pink"      => "Pink",
		"vagrant"   => "Chromeopathik",
		
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
	global $skins, $skintype, $userData, $usersdb, $cache, $msgs, $cwd, $config, $db;

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
			$res = $db->prepare_query("SELECT rubykey from keymap WHERE phpkey = # OR phpkey = #", "userinfo", "profileskin");
			while($row = $res->fetchrow() )
			{
				$cache->remove("{$row['rubykey']}-{$userData['userid']}");
			}
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
