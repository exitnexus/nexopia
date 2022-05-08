<?

	$skinfields = array(
		'headerbg',
		'headertext',
		'headerlink',
		'headerhover',

		'bodybg',
		'bodybg2',
		'bodytext',
		'bodylink',
		'bodyhover',

//		'votelink',
//		'votehover',

		'online',
		'offline');


function encodeSkin($data){
	global $skinfields;

	$str = "";

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

		$str .= $data[$field];
	}

	return $str;
}

function decodeSkin($str){
	global $skinfields;

	return array_combine($skinfields, str_split($str, 6) );
}

function injectSkin($user, $skinfor)
{
	global $cache, $db;

	$skinprop = ($skinfor=='profile'? 'skin' : "{$skinfor}skin");

	if($user['plus'] && $user[$skinprop]){
/*
$skindata = array(
'headerbg' => "A1570E",
'headertext' => "FFFFFF",
'headerlink' => "FFFFFF",
'headerhover' => "CCCCCC",

'bodybg' => "666666",
'bodybg2' => "333333",
'bodytext' => "FFFFFF",
'bodylink' => "FE9800",
'bodyhover' => "CCCCCC",

'votelink' => "CCCCCC",
'votehover' => "FFFFFF",

'online' => "00AA00",
'offline' => "FF0000");*/

		$skindata = $cache->get("profileskin-{$user[$skinprop]}");

		if(!$skindata){
			$res = $db->prepare_query("SELECT data FROM profileskins WHERE id = #", $user[$skinprop]);
			$skin = $res->fetchrow();

			if(!$skin)
				return;

			$skindata = decodeSkin($skin['data']);

			$cache->put("profileskin-{$user[$skinprop]}", $skindata, 86400*7);
		}


echo <<<END
<style>

td.body			{ background-color: #$skindata[bodybg]; color: #$skindata[bodytext]; font-family: arial; font-size: 8pt}
a.body:active,
a.body:link,
a.body:visited	{ color: #$skindata[bodylink]; font-family: arial; font-size: 8pt }
a.body:hover	{ color: #$skindata[bodyhover]; font-family: arial; font-size: 8pt }

td.body2		{ background-color: #$skindata[bodybg2]; color: #$skindata[bodytext]; font-family: arial; font-size: 8pt}

td.header			{ background-color: #$skindata[headerbg]; color: #$skindata[headertext]; font-family: arial; font-size: 8pt}
a.header:active,
a.header:link,
a.header:visited	{ color: #$skindata[headerlink]; font-family: arial; font-size: 8pt }
a.header:hover		{ color: #$skindata[headerhover]; font-family: arial; font-size: 8pt }

td.online		{ background-color: #$skindata[bodybg]; color: #$skindata[online]; font-family: arial; font-size: 16pt; font-weight: bolder}
td.offline		{ background-color: #$skindata[bodybg]; color: #$skindata[offline]; font-family: arial; font-size: 16pt; font-weight: bolder}

</style>
END;

	}
}

