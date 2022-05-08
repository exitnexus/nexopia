<?

set_time_limit(10);
//ob_start();
//*
$grid = array(	array(0,0,8,0,0,0,1,5,0),
				array(0,0,0,0,0,1,8,0,0),
				array(3,0,5,4,0,0,0,0,9),
				array(5,0,0,0,0,9,0,0,0),
				array(0,9,0,2,3,4,0,7,0),
				array(0,0,0,1,0,0,0,0,8),
				array(4,0,0,0,0,5,9,0,1),
				array(0,0,6,7,0,0,0,0,0),
				array(0,5,3,0,0,0,2,0,0) );
$tokens = array('1','2','3','4','5','6','7','8','9');
$blank = 0;
//*/
/*
$grid = array(	array(6,0,0,0,0,0,1,7,5),
				array(0,0,0,0,0,5,0,0,0),
				array(0,0,0,3,0,1,2,0,6),
				array(0,0,2,0,3,6,0,0,0),
				array(4,0,0,0,0,0,0,0,2),
				array(0,0,0,4,9,0,8,0,0),
				array(2,0,4,8,0,3,0,0,0),
				array(0,0,0,7,0,0,0,0,0),
				array(5,3,1,0,0,0,0,0,7) );
$tokens = array('1','2','3','4','5','6','7','8','9');
$blank = 0;
//*/
/*
$grid = array(	array(0,9,8,0,1,2,0,4,0),
				array(5,6,2,3,0,0,0,0,0),
				array(0,0,0,0,0,9,0,0,0),
				array(0,0,0,0,0,0,6,0,1),
				array(0,3,6,0,0,0,5,9,0),
				array(1,0,7,0,0,0,0,0,0),
				array(0,0,0,2,0,0,0,0,0),
				array(0,0,0,0,0,6,4,5,3),
				array(0,4,0,5,7,0,8,2,0) );
$tokens = array('1','2','3','4','5','6','7','8','9');
$blank = 0;
//*/
/*
$grid = array(	array(-1, 3,10,-1, -1,-1, 2,-1, -1,12,14,-1,  6, 9,-1, 4),
				array( 1, 7,-1,-1, -1,-1,-1, 4, 13,-1,10,-1, -1, 5,-1, 8),
				array(-1, 8,12, 5, -1, 3,-1,-1, 15,-1,-1, 9, -1,11,-1, 1),
				array(-1, 4,-1,-1,  9,-1,-1,13,  2,-1, 5,-1, 12, 3,-1,-1),

				array(-1,-1,-1,-1, -1,-1,13,-1, -1,14,-1,-1,  3,15, 2,-1),
				array(-1,-1, 2,-1,  8,-1,-1,-1,  0, 7, 9,-1,  1,-1,-1,-1),
				array(15, 0,-1,-1, -1,-1,-1,-1, -1,-1,13,-1, -1,-1,-1,-1),
				array(-1,10,-1,-1,  1, 4,12,-1, -1,-1,-1,-1, -1,-1, 0, 9),

				array(12,14,-1,-1, -1,-1,-1,-1, -1, 5, 3,15, -1,-1, 4,-1),
				array(-1,-1,-1,-1, -1,13,-1,-1, -1,-1,-1,-1, -1,-1,14,15),
				array(-1,-1,-1,15, -1,12, 5, 1, -1,-1,-1, 2, -1, 8,-1,-1),
				array(-1, 5, 0, 9, -1,-1,10,-1, -1, 4,-1,-1, -1,-1,-1,-1),

				array(-1,-1, 5,12, -1,10,-1,15,  8,-1,-1, 3, -1,-1, 6,-1),
				array(10,-1, 8,-1,  3,-1,-1,12, -1,-1, 2,-1,  7, 4, 5,-1),
				array( 2,-1,13,-1, -1, 5,-1,11, 14,-1,-1,-1, -1,-1, 9,12),
				array( 0,-1, 4, 7, -1, 9,14,-1, -1,11,-1,-1, -1, 1, 3,-1),
			);
$tokens = array('0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F');
$blank = -1;
//*/

/*
$grid = array(	array(0,8,0,1,7,0,0,0,0),
				array(4,0,0,0,3,0,0,0,7),
				array(0,9,0,0,0,0,8,0,0),
				array(0,7,0,0,0,0,0,0,0),
				array(8,3,1,0,2,0,4,5,6),
				array(0,0,0,0,0,0,0,3,0),
				array(0,0,6,0,0,0,0,7,0),
				array(2,0,0,0,1,0,0,0,9),
				array(0,0,0,0,6,2,0,1,0) );
$tokens = array('1','2','3','4','5','6','7','8','9');
$blank = 0;
//*/

/*
$grid = array(	array(0,8,9,0,0,0,0,5,0),
				array(0,0,0,0,0,0,6,2,0),
				array(6,1,5,7,0,0,0,0,0),
				array(4,0,0,0,1,2,8,0,0),
				array(0,0,0,0,0,0,0,0,0),
				array(0,0,2,5,7,0,0,0,9),
				array(0,0,0,0,0,6,4,8,3),
				array(0,3,6,0,0,0,0,0,0),
				array(0,4,0,0,0,0,7,6,0) );
$tokens = array('1','2','3','4','5','6','7','8','9');
$blank = 0;
//*/


$debug = isset($_REQUEST['debug']);
$size = count($grid);
$boxsize = sqrt($size);

?><style>
td		{ width: 20px; height: 20px; text-align: center }
td.a	{ font-weight: bold }
td.vb	{ width: 1px; background-color: #000000 }
td.hb	{ height: 3px; background-color: #000000 }
</style><?

$time = gettime();
$sets = 0;

output($grid);				//initial setup
init($grid);				//find initial possibilities
if($debug)	output($grid);	//output initial possibilities
echo (run($grid) ? "done" : "failed") . " in $sets moves in " . number_format((gettime() - $time)/10000, 3) . " secs\n";
output($grid);				//final output

function init(& $grid){ //init, set all possibilities
	global $size, $boxsize, $tokens, $blank;

	if($blank != 0)
		for($r = 0; $r < $size; $r++)
			for($c = 0; $c < $size; $c++)
				if($grid[$r][$c] !== $blank)
					$grid[$r][$c] = $tokens[$grid[$r][$c]];

	for($r = 0; $r < $size; $r++){
		for($c = 0; $c < $size; $c++){
			if($grid[$r][$c] === $blank){
				$grid[$r][$c] = $used = array();

				for($i = 0; $i < $size; $i++)
					if(!is_array($grid[$r][$i]))
						$used[$grid[$r][$i]] = $grid[$r][$i];

				for($i = 0; $i < $size; $i++)
					if(!is_array($grid[$i][$c]))
						$used[$grid[$i][$c]] = $grid[$i][$c];

				$baser = floor($r/$boxsize)*$boxsize;
				$basec = floor($c/$boxsize)*$boxsize;
				for($i = 0; $i < $boxsize; $i++)
					for($j = 0; $j < $boxsize; $j++)
						if(!is_array($grid[$i+$baser][$j+$basec]))
							$used[$grid[$i+$baser][$j+$basec]] = $grid[$i+$baser][$j+$basec];

				for($i = 0; $i < $size; $i++)
					if(!isset($used[$tokens[$i]]))
						$grid[$r][$c][$tokens[$i]] = $tokens[$i];
			}
		}
	}
}

function run(& $grid){
	global $size, $boxsize;

	$knownpaircols = array();
	$knownpairrows = array();

	do{
		$sets = $GLOBALS['sets'];

		for($r = 0; $r < $size; $r++){
			for($c = 0; $c < $size; $c++){
				if(!is_array($grid[$r][$c]))
					continue;

				if(count($grid[$r][$c]) == 0) //error
					return false;

			//single
				if(count($grid[$r][$c]) == 1){ //ie all except 1, this must be that last 1
					if($GLOBALS['debug']) echo "$c,$r: single<br>";
					if(($set = set($grid, $r, $c, array_pop($grid[$r][$c]))) == false)
						return false;
					continue;
				}

			//rows
				$possible = $grid[$r][$c];
				for($i = 0; $i < $size; $i++)
					if(is_array($grid[$r][$i]) && $i != $c)
						foreach($grid[$r][$i] as $k)
							if(isset($possible[$k]))
								unset($possible[$k]);

				if(count($possible) == 1){ //ie all except 1, this must be that last 1
					if($GLOBALS['debug']) echo "$c,$r: row<br>";
					if(set($grid, $r, $c, array_pop($possible)) === false)
						return false;
					continue;
				}

			//cols
				$possible = $grid[$r][$c];
				for($i = 0; $i < $size; $i++)
					if(is_array($grid[$i][$c]) && $i != $r)
						foreach($grid[$i][$c] as $k)
							if(isset($possible[$k]))
								unset($possible[$k]);

				if(count($possible) == 1){ //ie all except 1, this must be that last 1
					if($GLOBALS['debug']) echo "$c,$r: col<br>";
					if(set($grid, $r, $c, array_pop($possible)) === false)
						return false;
					continue;
				}

			//boxes
				$possible = $grid[$r][$c];

				$baser = floor($r/$boxsize)*$boxsize;
				$basec = floor($c/$boxsize)*$boxsize;
				for($i = 0; $i < $boxsize; $i++)
					for($j = 0; $j < $boxsize; $j++)
						if(is_array($grid[$i+$baser][$j+$basec]) && ($i+$baser != $r || $j+$basec != $c))
							foreach($grid[$i+$baser][$j+$basec] as $k)
								if(isset($possible[$k]))
									unset($possible[$k]);

				if(count($possible) == 1){ //ie all except 1, this must be that last 1
					if($GLOBALS['debug']) echo "$c,$r: box<br>";
					if(set($grid, $r, $c, array_pop($possible)) === false)
						return false;
					continue;
				}
			}
		}

//*
//look for pairs in a row/col within a box
		for($i = 0; $i < $boxsize; $i++){
			for($j = 0; $j < $boxsize; $j++){
				$baser = $i*$boxsize;
				$basec = $j*$boxsize;


			//rows
				for($r = 0; $r < $boxsize; $r++){
					$possibilities = array();

					for($c = 0; $c < $boxsize; $c++){
						if(!is_array($grid[$baser+$r][$basec+$c]) || count($grid[$baser+$r][$basec+$c]) > $boxsize)
							continue;

						if(!isset($possibilities[implode(',', $grid[$baser+$r][$basec+$c])]))
							$possibilities[implode(',', $grid[$baser+$r][$basec+$c])] = 0;
						$possibilities[implode(',', $grid[$baser+$r][$basec+$c])]++;
					}

					foreach($possibilities as $possible => $count){
						$possible = explode(',', $possible);
						if($count == count($possible)){ //found a pair, remove those possibilities from other rows in that box
							if($GLOBALS['debug']) echo ($basec) . "," . ($baser+$r) . ": pair row<br>";

							$knownpairrows[$baser+$r] = $baser+$r;

							$possible = array_combine($possible, $possible);

						//remove all possibilities within the box
							for($r2 = 0; $r2 < $boxsize; $r2++)
								for($c = 0; $c < $boxsize; $c++)
									if($grid[$baser+$r2][$basec+$c] != $possible)
										foreach($possible as $k)
											if(isset($grid[$baser+$r2][$basec+$c][$k]))
												unset($grid[$baser+$r2][$basec+$c][$k]);

						//remove all within the row
							for($c = 0; $c < $size; $c++)
								if($grid[$baser+$r][$c] != $possible)
									foreach($possible as $k)
										if(isset($grid[$baser+$r][$c][$k]))
											unset($grid[$baser+$r][$c][$k]);
						}
					}
				}

			//cols
				for($c = 0; $c < $boxsize; $c++){
					if(isset($knownpaircols[$basec+$c]))
						continue;

					$possibilities = array();

					for($r = 0; $r < $boxsize; $r++){
						if(!is_array($grid[$baser+$r][$basec+$c]) || count($grid[$baser+$r][$basec+$c]) > $boxsize)
							continue;

						if(!isset($possibilities[implode(',', $grid[$baser+$r][$basec+$c])]))
							$possibilities[implode(',', $grid[$baser+$r][$basec+$c])] = 0;
						$possibilities[implode(',', $grid[$baser+$r][$basec+$c])]++;
					}

					foreach($possibilities as $possible => $count){
						$possible = explode(',', $possible);
						if($count == count($possible)){ //found a pair, remove those possibilities from other rows in that box
							if($GLOBALS['debug']) echo ($basec) . "," . ($baser+$r) . ": pair col<br>";

							$knownpaircols[$basec+$c] = $basec+$c;

							$possible = array_combine($possible, $possible);

						//remove all possibilities within the box
							for($c2 = 0; $c2 < $boxsize; $c2++)
								for($r = 0; $r < $boxsize; $r++)
									if($grid[$baser+$r][$basec+$c2] != $possible)
										foreach($possible as $k)
											if(isset($grid[$baser+$r][$basec+$c2][$k]))
												unset($grid[$baser+$r][$basec+$c2][$k]);

						//remove all within the column
							for($r = 0; $r < $size; $r++)
								if($grid[$r][$basec+$c] != $possible)
									foreach($possible as $k)
										if(isset($grid[$r][$basec+$c][$k]))
											unset($grid[$r][$basec+$c][$k]);
						}
					}
				}


			//box
				$possibilities = array();
				for($r = 0; $r < $boxsize; $r++){
					for($c = 0; $c < $boxsize; $c++){
						if(!is_array($grid[$baser+$r][$basec+$c]) || count($grid[$baser+$r][$basec+$c]) > $boxsize)
							continue;

						if(!isset($possibilities[implode(',', $grid[$baser+$r][$basec+$c])]))
							$possibilities[implode(',', $grid[$baser+$r][$basec+$c])] = 0;
						$possibilities[implode(',', $grid[$baser+$r][$basec+$c])]++;
					}
				}

				foreach($possibilities as $possible => $count){
					$possible = explode(',', $possible);
					if($count == count($possible)){ //found a pair, remove those possibilities from other spots in that box
						if($GLOBALS['debug']) echo ($basec) . "," . ($baser+$r) . ": pair box<br>";

						$possible = array_combine($possible, $possible);

					//remove all possibilities within the box
						for($r = 0; $r < $boxsize; $r++)
							for($c = 0; $c < $boxsize; $c++)
								if($grid[$baser+$r][$basec+$c] != $possible)
									foreach($possible as $k)
										if(isset($grid[$baser+$r][$basec+$c][$k]))
											unset($grid[$baser+$r][$basec+$c][$k]);

					}
				}

			}
		}
//*/
	}while($sets != $GLOBALS['sets']);


//purely guess, find best guess
	$least = $lr = $lc = 0;
	for($r = 0; $r < $size; $r++)
		for($c = 0; $c < $size; $c++)
			if(is_array($grid[$r][$c]) && (!$least || count($grid[$r][$c]) < $least)){
				$lr = $r; $lc = $c;
				$least = count($grid[$r][$c]);
			}

	if(!$least)	return true; //no possibilities, must be done

	foreach($grid[$lr][$lc] as $k){ //try all possibilities for best guess
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

//if(!function_exists('array_combine')){
function array_combine( $keys, $vals ) {
	$keys = array_values( (array) $keys );
	$vals = array_values( (array) $vals );
	$n = max( count( $keys ), count( $vals ) );
	$r = array();
	for( $i=0; $i<$n; $i++ ) {
		$r[ $keys[ $i ] ] = $vals[ $i ];
	}
	return $r;
}
//}
