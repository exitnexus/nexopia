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
		'headerbg' => 'header_background_color',
		'headertext' => 'header_text_color',
		'headerlink' => 'header_link_color',
		'headerhover' => 'header_link_accent_color',

		'bodybg' => 'primary_background_color',
		'bodybg2' => 'secondary_background_color',
		'bodytext' => 'primary_text_color',
		'bodylink' => 'link_color',
		'bodyhover' => 'link_accent_color',

		'online' => 'user_online_color',
		'offline' => 'user_offline_color'
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
	$php_style_data = array();
	foreach ($mapping as $phpkey => $rubykey) {
		$php_style_data[$phpkey] = $data[$rubykey];
	}
	return $php_style_data;
}

function injectSkin($user, $skinfor){
	global $cache, $db;

	$skinid = $user[($skinfor=='profile'? 'skin' : "{$skinfor}skin")];

	if($user['plus'] && $skinid){
		$skindata = $cache->get("profileskin-".$user['userid']."/$skinid");

		if($skindata === false){
			$res = $db->prepare_query("SELECT data FROM profileskins WHERE id = #", $skinid);
			$skin = $res->fetchrow();

			if($skin)
				$skindata = decodeSkin($skin['data']);
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
