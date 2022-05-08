<?

set_time_limit(10);
//ob_start();


$boxsize = 3;
$tokens = array('1','2','3','4','5','6','7','8','9');
/*
$boxsize = 4;
$tokens = array('0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F');

$boxsize = 5;
$tokens = array('0','1','2','3','4','5','6','7','8','9',
				'A','B','C','D','E','F','G','H','I','J',
				'K','L','M','N','O');
*/
////////////
$debug = isset($_REQUEST['debug']);
$size = $boxsize * $boxsize;

?><style>
td		{ width: 20px; height: 20px; text-align: center }
td.a	{ font-weight: bold }
td.vb	{ width: 1px; background-color: #000000 }
td.hb	{ height: 3px; background-color: #000000 }
</style><?

$time = gettime();
$sets = 0;

$grid = array();
init($grid);				//find initial possibilities
if($debug)	output($grid);	//output initial possibilities
echo (run($grid) ? "done" : "failed") . " in $sets moves in " . number_format((gettime() - $time)/10000, 3) . " secs\n";
output($grid);				//final output

function init(& $grid){ //init, set all possibilities
	global $size, $tokens;

	for($r = 0; $r < $size; $r++)
		for($c = 0; $c < $size; $c++)
			for($i = 0; $i < $size; $i++)
				$grid[$r][$c][$tokens[$i]] = $tokens[$i];
}

function run(& $grid){
	global $size, $boxsize;

//purely guess, find best guess
	$least = $lr = $lc = 0;
	for($r = 0; $r < $size; $r++)
		for($c = 0; $c < $size; $c++)
			if(is_array($grid[$r][$c]) && (!$least || count($grid[$r][$c]) < $least)){
				$lr = $r; $lc = $c;
				$least = count($grid[$r][$c]);
			}

	if(!$least)	return true; //no possibilities, must be done

	$possibilities = $grid[$lr][$lc];
	shuffle($possibilities);

	foreach($possibilities as $k){ //try all possibilities for best guess
		if($GLOBALS['debug']) echo "$lc,$lr: guess $k<br>";

		$copy = $grid; //play with a copy
		if(!set($copy, $lr, $lc, $k)){
			if($GLOBALS['debug']) echo "$lc,$lr: unguess $k<br>";
			continue;
		}

		if(run($copy)){
			$grid = $copy;
			return true;
		}elseif($GLOBALS['debug']) echo "$lc,$lr: unguess $k<br>";
	}
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

function set( & $grid, $r, $c, $v){
	global $size, $boxsize;
	$GLOBALS['sets']++;

	$grid[$r][$c] = $v;

	for($i = 0; $i < $size; $i++)
		if(is_array($grid[$r][$i]) && isset($grid[$r][$i][$v]))
			unset($grid[$r][$i][$v]);

	for($i = 0; $i < $size; $i++)
		if(is_array($grid[$i][$c]) && isset($grid[$i][$c][$v]))
			unset($grid[$i][$c][$v]);

	$baser = floor($r/$boxsize)*$boxsize;
	$basec = floor($c/$boxsize)*$boxsize;
	for($i = 0; $i < $boxsize; $i++)
		for($j = 0; $j < $boxsize; $j++)
			if(is_array($grid[$i+$baser][$j+$basec]) && isset($grid[$i+$baser][$j+$basec][$v]))
				unset($grid[$i+$baser][$j+$basec][$v]);

	if($GLOBALS['debug']) output($grid);

	for($i = 0; $i < $size; $i++)
		for($j = 0; $j < $size; $j++)
			if(is_array($grid[$i][$j]) && count($grid[$i][$j]) == 0)
				return false;

	for($i = 0; $i < $size; $i++){
		for($j = 0; $j < $size; $j++){
			if(is_array($grid[$i][$j]) && count($grid[$i][$j]) == 1){
				if($GLOBALS['debug']) echo "$j,$i: single<br>";
				if(set($grid, $i, $j, array_pop($grid[$i][$j])) === false)
					return false;
			}
		}
	}

	return true;
}

function gettime(){
	list($usec, $sec) = explode(" ",microtime());
	return (10000*((float)$usec + (float)$sec));
}

