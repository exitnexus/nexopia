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


