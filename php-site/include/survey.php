<?



$profile[] = array(	'question'=> "Weight",
					'answers' =>array("Less than 41 Kg (less than   90 lbs)",
									"41 Kg - 45 Kg  (  90 lbs - 100 lbs)",
									"46 Kg - 50 Kg  ( 101 lbs - 110 lbs)",
									"51 Kg - 55 Kg  ( 111 lbs - 120 lbs)",
									"56 Kg - 59 Kg  ( 121 lbs - 130 lbs)",
									"60 Kg - 64 Kg  ( 131 lbs - 140 lbs)",
									"65 Kg - 68 Kg  ( 141 lbs - 150 lbs)",
									"69 Kg - 73 Kg  ( 151 lbs - 160 lbs)",
									"74 Kg - 77 Kg  ( 161 lbs - 170 lbs)",
									"78 Kg - 82 Kg  ( 171 lbs - 180 lbs)",
									"83 Kg - 86 Kg  ( 181 lbs - 190 lbs)",
									"87 Kg - 91 Kg  ( 191 lbs - 200 lbs)",
									"92 Kg - 95 Kg  ( 201 lbs - 210 lbs)",
									"96 Kg - 100 Kg ( 211 lbs - 220 lbs)",
									"Over 100 Kg    (over 221 lbs)"
									)
					);

$profile[] = array(	'question'=> "Height",
					'answers' =>array("Under 152 cm      (under 5')",
									"152 cm - 158 cm   (5'    - 5'2\")",
									"159 cm - 163 cm   (5'3\"  - 5'4\")",
									"164 cm - 168 cm   (5'5\"  - 5'6\")",
									"169 cm - 173 cm   (5'7\"  - 5'8\")",
									"174 cm - 178 cm   (5'9\"  - 5'10\")",
									"179 cm - 183 cm   (5'11\" - 6')",
									"184 cm - 188 cm   (6'1\"  - 6'2\")",
									"189 cm - 193 cm   (6'3\"  - 6'4\")",
									"Over 194 cm   (over 6'5\")"
									)
					);


$profile[] = array( 'question'=> "Sexual Orientation",
					'answers' =>array("Heterosexual",
									"Homosexual",
									"Bisexual/Open-Minded"
									)

					);

$profile[] = array( 'question'=> "Dating Situation",
					'answers' =>array("Single and looking",
									"Single and not looking",
									"Dating",
									"Long term",
									"Married"
									)

					);

$profile[] = array( 'question'=> "Living Situation",
					'answers' =>array("Living alone",
									"Living with spouse",
									"Living with kid(s)",
									"Living with roommate(s)",
									"Living with parents/relatives",
									"Living with significant other"
									)
					);


function decodeProfile($str){
	global $profile;

	$str = str_pad($str,count($profile),"0");

	$arr=array();
	for($i = 0; $i < strlen($str); $i++)
		$arr[] = base_convert($str[$i],36,10);

	return $arr;
}

function encodeProfile($ar){
	$str="";
	foreach($ar as $val)
		$str .= base_convert($val,10,36);
	return $str;
}

