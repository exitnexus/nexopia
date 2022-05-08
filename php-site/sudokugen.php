<?


$boxsize = 3;
///////

$size = $boxsize * $boxsize;

?><style>
td		{ width: 20px; height: 20px; text-align: center }
td.a	{ font-weight: bold }
td.vb	{ width: 1px; background-color: #000000 }
td.hb	{ height: 3px; background-color: #000000 }
</style><?


$grid = array();

for($r = 0; $r < $size; $r++){
	$grid[$r] = range(1, $size);
	$grid[$r] = array_rotate_left($grid[$r], (($r%$boxsize)*$boxsize + floor($r/$boxsize)));
}

output($grid);


$nummoves = 50;

for($movenum = 0; $movenum < $nummoves; $movenum++){
	$move = rand(1,6);

	switch($move){
		case 1: //swap rows
		case 2:
			$block = rand(0, $boxsize-1);
			$r1 = rand(0, $boxsize-1);
			$r2 = rand(0, $boxsize-2);
			if($r2 >= $r1)
				$r2++;

			swapRows($grid, $boxsize*$block + $r1, $boxsize*$block + $r2);
			echo "swap rows $r1, $r2 in block $block<br>";

			break;

		case 3: //swap row blocks
			$r1 = rand(0, $boxsize-1);
			$r2 = rand(0, $boxsize-2);
			if($r2 >= $r1)
				$r2++;

			for($i = 0; $i < $boxsize; $i++)
				swapRows($grid, $boxsize*$r1 + $i, $boxsize*$r2 + $i);
			echo "swap row blocks: $r1, $r2<br>";

			break;

		case 4: //swap cols

			$block = rand(0, $boxsize-1);
			$c1 = rand(0, $boxsize-1);
			$c2 = rand(0, $boxsize-2);
			if($c2 >= $c1)
				$c2++;

			swapCols($grid, $boxsize*$block + $c1, $boxsize*$block + $c2);
			echo "swap cols: $c1, $c2 in block $block<br>";

			break;

		case 4: //swap col blocks
		case 5:
			$c1 = rand(0, $boxsize-1);
			$c2 = rand(0, $boxsize-2);
			if($c2 >= $c1)
				$c2++;

			for($i = 0; $i < $boxsize; $i++)
				swapRows($grid, $boxsize*$c1 + $i, $boxsize*$c2 + $i);
			echo "swap col blocks: $c1, $c2<br>";

			break;
	}

	output($grid);
}







function swapRows(& $grid, $r1, $r2){
	$tmp = $grid[$r1];
	$grid[$r1] = $grid[$r2];
	$grid[$r2] = $tmp;
}

function swapCols(& $grid, $c1, $c2){
	global $size;
	for($r = 0; $r < $size; $r++){
		$tmp = $grid[$r][$c1];
		$grid[$r][$c1] = $grid[$r][$c2];
		$grid[$r][$c2] = $tmp;
	}
}


function array_rotate_right($array, $num){ //rotates 1,2,3 -> 3,1,2
	for($i = 0; $i < $num; $i++)
		array_unshift($array, array_pop($array));

	return $array;
}

function array_rotate_left($array, $num){ //rotates 1,2,3 -> 3,1,2
	for($i = 0; $i < $num; $i++)
		array_push($array, array_shift($array));

	return $array;
}

function output( & $grid){
	global $size, $boxsize, $blank;
	echo "<table border=1 cellspacing=0 cellpadding=0>\n";
	for($i = 0; $i < $size; $i++){
		echo "<tr>";
		for($j = 0; $j < $size; $j++){
			$output = ">";
			if(is_array($grid[$i][$j]))
				$output = ">" . implode(",", $grid[$i][$j]);
			elseif($grid[$i][$j] != $blank)
				$output = " class=a>" . $grid[$i][$j];

			echo "<td$output</td>";
			if($j % $boxsize == $boxsize - 1 && $j + 1 < $size)
				echo "<td class=vb></td>";
		}
		echo "</tr>\n";

		if($i % $boxsize == $boxsize - 1 && $i + 1 < $size)
			echo "<tr><td colspan=" . ($size + $boxsize - 1) . " class=hb></td></tr>\n";
	}
	echo "</table><br>\n";
}
