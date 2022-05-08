<?

function getMirrors(){
	global $db;

	$res = $db->prepare_query("SELECT * FROM mirrors");

	$mirrors = array();
	while($line = $res->fetchrow())
		$mirrors[$line['type']][] = $line;

	return $mirrors;
}

/*
$mirrors[] = array(	'plus' => 'y'/'n',
					'weight' => 1,
					'cols'...);
*/

function chooseRandomServer($mirrors, $plus = false, $col = false, $default = false, $force = false){
	global $db, $cache;

	if(!count($mirrors))
		return "";

	if($plus){ //are there any plus servers?
		$plus = false;
		foreach($mirrors as $server){
			if($server['plus'] == 'y' && $server['weight'] > 0){
				$plus = true;
				break;
			}
		}
	}
	$plus = ($plus ? 'y' : 'n');

	if($default && substr($default,0,7) == 'http://') //get rid of http://
		$default = substr($default,7);

	$choices = array();
	foreach($mirrors as $id => $server){
		if(!$force && ($server['weight'] <= 0 || $plus != $server['plus']))
			continue;

		if($default && $server['domain'] == $default) // auto chose the cached choice
			return ($col ? $server[$col] : $server);

		for($i=0; $i < $server['weight']; $i++)
			$choices[] = $id;
	}

	if(count($choices)){
		randomize();
		$id = $choices[rand(0,count($choices)-1)];

		return ($col ? $mirrors[$id][$col] : $mirrors[$id]);
	}

	return "";
}

function chooseImageServer($key){ //and always type=image
	global $mirrors, $userData, $imgServerHardCoded;

	// this is to allow the hard coded override to function, however the IMG server in the global scope
	// ends up with http:// infront of it, however we don't want to return that since its expected that this fucntion
	// will just simply return the host, thus it is removed
	if (isset($imgServerHardCoded) === true && $imgServerHardCoded === true) {
		global $imgserver;
		eregi("^http://(.+)$", $imgserver, $match);
		if ($match) {
			$imgserver = $match[1];
		}

		return $imgserver;
	}
	
	$plus = false;
	if($userData['loggedIn'] && $userData['premium']){ //are there any plus servers?
		$plus = false;
		foreach($mirrors['image'] as $server){
			if($server['plus'] == 'y' && $server['weight'] > 0){
				$plus = true;
				break;
			}
		}
	}
	$plus = ($plus ? 'y' : 'n');

	$choices = array();
	foreach($mirrors['image'] as $id => $server){
		if($server['weight'] <= 0 || $plus != $server['plus'])
			continue;

		for($i=0; $i < $server['weight']; $i++)
			$choices[] = $id;
	}

	if(count($choices))
		return $mirrors['image'][$choices[$key % count($choices)]]['domain'];

    return "";
}

