<?

function getMirrors(){
	global $db;

	$db->prepare_query("SELECT * FROM mirrors");

	$mirrors = array();
	while($line = $db->fetchrow())
		$mirrors[$line['type']][] = $line;

	return $mirrors;
}

function chooseServer($type, $plus = false, $default=false){
	global $db, $cache;

	$mirrors = $cache->hdget("mirrors", 'getMirrors');

	if(!count($mirrors[$type]))
		return "";

	if($plus){ //are there any plus servers?
		$plus = false;
		foreach($mirrors[$type] as $server){
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
	foreach($mirrors[$type] as $server){
		if($server['weight'] <= 0 || $plus != $server['plus'])
			continue;

		if($server['domain']==$default) // auto chose the cached choice
			return $default;

		for($i=0;$i<$server['weight'];$i++)
			$choices[] = $server['domain'];
	}

	if(count($choices)){
		randomize();
		return $choices[rand(0,count($choices)-1)];
	}

	return "";
}


