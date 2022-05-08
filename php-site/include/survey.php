<?

function getProfileQuestions(){
	return array(
		array(	'question'=> "Weight",
				'answers' => array(	
					'0' =>	"No Comment",
					'1' =>	"Less than 41 Kg (less than   90 lbs)",
					'2' =>	"41 Kg - 45 Kg  (  90 lbs - 100 lbs)",
					'3' =>	"46 Kg - 50 Kg  ( 101 lbs - 110 lbs)",
					'4' =>	"51 Kg - 55 Kg  ( 111 lbs - 120 lbs)",
					'5' =>	"56 Kg - 59 Kg  ( 121 lbs - 130 lbs)",
					'6' =>	"60 Kg - 64 Kg  ( 131 lbs - 140 lbs)",
					'7' =>	"65 Kg - 68 Kg  ( 141 lbs - 150 lbs)",
					'8' =>	"69 Kg - 73 Kg  ( 151 lbs - 160 lbs)",
					'9' =>	"74 Kg - 77 Kg  ( 161 lbs - 170 lbs)",
					'a' =>	"78 Kg - 82 Kg  ( 171 lbs - 180 lbs)",
					'b' =>	"83 Kg - 86 Kg  ( 181 lbs - 190 lbs)",
					'c' =>	"87 Kg - 91 Kg  ( 191 lbs - 200 lbs)",
					'd' =>	"92 Kg - 95 Kg  ( 201 lbs - 210 lbs)",
					'e' =>	"96 Kg - 100 Kg ( 211 lbs - 220 lbs)",
					'f' =>	"Over 100 Kg    (over 221 lbs)",
				)
			),
		array(	'question'=> "Height",
				'answers' => array(
					'0' =>	"No Comment",
					'1' =>	"Under 152 cm      (under 5')",
					'2' =>	"152 cm - 158 cm   (5'    - 5'2\")",
					'3' =>	"159 cm - 163 cm   (5'3\"  - 5'4\")",
					'4' =>	"164 cm - 168 cm   (5'5\"  - 5'6\")",
					'5' =>	"169 cm - 173 cm   (5'7\"  - 5'8\")",
					'6' =>	"174 cm - 178 cm   (5'9\"  - 5'10\")",
					'7' =>	"179 cm - 183 cm   (5'11\" - 6')",
					'8' =>	"184 cm - 188 cm   (6'1\"  - 6'2\")",
					'9' =>	"189 cm - 193 cm   (6'3\"  - 6'4\")",
					'a' =>	"Over 194 cm   (over 6'5\")",
				)
			),
		array(	'question'=> "Sexual Orientation",
				'answers' => array(
					'0' =>	"No Comment",
					'1' =>	"Heterosexual",
					'2' =>	"Homosexual",
					'3' =>	"Bisexual/Open-Minded",
				)
			),
		array(	'question'=> "Dating Situation",
				'answers' => array(
					'0' =>	"No Comment",
					'2' =>	"Single and not looking",
					'6' =>	"Single",
					'1' =>	"Single and looking",
					'3' =>	"Dating",
					'4' =>	"Long term",
					'7' =>  "Engaged",
					'5' =>	"Married",
				)
			),
		array(	'question'=> "Living Situation",
				'answers' => array(
					'0' =>	"No Comment",
					'1' =>	"Living alone",
					'2' =>	"Living with spouse",
					'3' =>	"Living with kid(s)",
					'4' =>	"Living with roommate(s)",
					'5' =>	"Living with parents/relatives",
					'6' =>	"Living with significant other",
				)
			)
		);
}


function decodeProfile($str){
	$profile = getProfileQuestions();

	$str = str_pad($str,count($profile),"0");

	$arr=array();
	for($i = 0; $i < strlen($str); $i++)
		$arr[] = $str[$i];

	return $arr;
}

function encodeProfile($ar){
	return implode("", $ar);
}

