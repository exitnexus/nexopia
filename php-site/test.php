<?php

function getColor(){
	$digits = "0123456789ABCDEF";

	$color = '';
	for($j = 0; $j < 6; $j++)
		$color .= $digits{rand(0,15)};
	return $color;
}

echo "<table>";
for($i = 0; $i < 5; $i++){
	echo "<style> td.body { background-color: #" . getColor() . "; color: #" . getColor() . "; font-family: arial; font-size: 8pt} </style>\n";

	echo "<tr><td class=body>" . str_repeat("Lorem Ipsum ", 100) . "</td></tr>\n";
}
echo "</table>";
