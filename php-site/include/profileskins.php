<?

function getSkinFields(){
	return array(
		'headerbg',
		'headertext',
		'headerlink',
		'headerhover',

		'bodybg',
		'bodybg2',
		'bodytext',
		'bodylink',
		'bodyhover',

		'online',
		'offline');
}

function getSkinFieldsMapping() { 
	return array (
		'headerbg' => 'primary_block_background_color',
		'headertext' => 'primary_block_header_text_color',
		'headerlink' => 'primary_block_link_color',
		'headerhover' => 'primary_block_link_hover_color',

		'bodybg' => 'primary_block_background_color',
		'bodybg2' => 'secondary_block_background_color',
		'bodytext' => 'primary_block_text_color',
		'bodylink' => 'primary_block_link_color',
		'bodyhover' => 'primary_block_link_hover_color',

		'online' => 'utility_block_user_online_color',
		'offline' => 'utility_block_user_offline_color'
	);
}

function encodeSkin($data){
	$skinfields = getSkinFields();
	$mapping = getSkinFieldsMapping();
	$ruby_style_data = array();

	foreach($skinfields as $field){
		if(empty($data[$field]))
			return false;

		if(substr($data[$field],0,1) == "#")
			$data[$field] = substr($data[$field],1);

		if(strlen($data[$field]) != 6)
			return false;

		$data[$field] = strtoupper($data[$field]);

		if(!ereg("[0-9A-F]{6}", $data[$field]))
			return false;

		$ruby_style_data[$mapping[$field]] = $data[$field];
	}

	return $ruby_style_data;
}

function decodeSkin($data){
	
	$mapping = getSkinFieldsMapping();
	
	//grab each individual line of the YAML file
	$raw_split_arr = explode("\n", $data);
	$ruby_skin_arr = array();
	$ref_key = "";
	$filtered_skin_arr = array();
	
	//We want to ignore the first line as it's just a --- to indiciate a hash.
	for($i=1; $i<count($raw_split_arr); $i++)
	{
		if(preg_match("/^:.*:.*/", trim($raw_split_arr[$i])))
		{
			$filtered_skin_arr[] = $raw_split_arr[$i];
		}
		elseif(preg_match("/^value:.*/", trim($raw_split_arr[$i])))
		{
			$filtered_skin_arr[] = $raw_split_arr[$i];
		}
	}
	
	for($i=0; $i<count($filtered_skin_arr); $i++)
	{
		//Each even line will be the ruby key and on each odd line will be the value.
		$temp_arr = explode(" ", trim($filtered_skin_arr[$i]));
		if($i%2 == 0)
		{
			$ref_key = str_replace(":", "",$temp_arr[0]);
		}
		else
		{
			$ruby_skin_arr[$ref_key] = str_replace(array("\"", "#"), "", $temp_arr[1]);
		}
	}
	
	//build the style data array the php site is expecting
	$mapping = getSkinFieldsMapping();
	$php_style_data = array();
	foreach($mapping as $phpkey => $rubykey)
	{
		/*
			The key_exist check and the string length check are present because of
		 	inconsistent development data. On the live data it should be good, this
		 	has happened because of skin values being added after the running of the
		 	migration scripts.
		*/
		if(!array_key_exists($rubykey, $ruby_skin_arr))
		{
			$php_style_data[$phpkey] = "000000";
		}
		else
		{
			$php_style_data[$phpkey] = $ruby_skin_arr[$rubykey];
		}
		if(strlen($php_style_data[$phpkey]) == 0)
		{
			$php_style_data[$phpkey] = "000000";
		}
	}

	return $php_style_data;
}

function injectSkin($user, $skinfor){
	global $cache, $db, $usersdb;
	
	$skinid = $user["{$skinfor}skin"];
	
	if($user['plus'] && $skinid){
		$skindata = $cache->get("profileskin-".$user['userid']."/$skinid");

		if($skindata === false){
			$res = $usersdb->prepare_query("SELECT skindata FROM userskins WHERE userid = % AND skinid = #", $user['userid'], $skinid);
			$skin = $res->fetchrow();

			if($skin)
				$skindata = decodeSkin($skin['skindata']);
			else
				$skindata = "";

			$cache->put("profileskin-".$user['userid']."/$skinid", $skindata, 86400*7);
		}

		if($skindata)
			return "<style>\n" . getCSS("", $skindata) . "</style>\n";;
	}

	return "";
}

function formatRule($target, $parts){
	$str = "";
	foreach($parts as $k => $v)
		if($v)
			$str .= "$k: #$v; ";

	if($str)
		$str = " $target { $str }\n";

	return $str;
}

function formatRules($rules){
	$str = "";
	foreach($rules as $target => $parts)
		$str .= formatRule($target, $parts);
	return $str;
}

function getCSS($container, $skin){
	return formatRules(array(
"$container a.header:active,
 $container a.header:link,
 $container a.header:visited" => array('color' => $skin['headerlink']),
"$container a.header:hover  " => array('color' => $skin['headerhover']),
"$container a.header:hover  " => array('color' => $skin['headerhover']),
"$container td.header       " => array('background-color' => $skin['headerbg'], 'color' => $skin['headertext']),

"$container a.body:active,
 $container a.body:link,
 $container a.body:visited  " => array('color' => $skin['bodylink']),
"$container a.body:hover    " => array('color' => $skin['bodyhover']),
"$container td.body         " => array('background-color' => $skin['bodybg'], 'color' => $skin['bodytext']),
"$container td.body2        " => array('background-color' => $skin['bodybg2'], 'color' => $skin['bodytext']),

"$container td.online       " => array('background-color' => $skin['bodybg'], 'color' => $skin['online']),
"$container td.offline      " => array('background-color' => $skin['bodybg'], 'color' => $skin['offline']),
	));
}
