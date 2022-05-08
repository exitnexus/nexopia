<?

$grid = array(	array(0,0,8,0,0,0,1,5,0),
				array(0,0,0,0,0,1,8,0,0),
				array(3,0,5,4,0,0,0,0,9),
				array(5,0,0,0,0,9,0,0,0),
				array(0,9,0,2,3,4,0,7,0),
				array(0,0,0,1,0,0,0,0,8),
				array(4,0,0,0,0,5,9,0,1),
				array(0,0,6,7,0,0,0,0,0),
				array(0,5,3,0,0,0,2,0,0) );

$debug = isset($_REQUEST['debug']);

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
//////////////////////////////

function init(& $grid){ //init, set all possibilities
	for($r = 0; $r < 9; $r++)
		for($c = 0; $c < 9; $c++)
			if($grid[$r][$c] == 0){
				$grid[$r][$c] = $used = array();

				$locs = getLocs($r, $c);
				foreach($locs as $type => $locs1)
					foreach($locs1 as $i => $locs2)
						foreach($locs2 as $j => $blah)
							if(!is_array($grid[$i][$j]))
								$used[$grid[$i][$j]] = $grid[$i][$j];

				for($i = 1; $i <= 9; $i++)
					if(!isset($used[$i]))
						$grid[$r][$c][$i] = $i;
			}
}

function run(& $grid){
	do{
		$sets = $GLOBALS['sets'];

		for($r = 0; $r < 9; $r++)
			for($c = 0; $c < 9; $c++){
				if(!is_array($grid[$r][$c]))
					continue;

				$locs = getLocs($r, $c);
				foreach($locs as $type => $locs1){
					$possible = $grid[$r][$c];
					foreach($locs1 as $i => $locs2)
						foreach($locs2 as $j => $blah)
							if(is_array($grid[$i][$j]) && ($i != $r || $j != $c))
								foreach($grid[$i][$j] as $k)
									if(isset($possible[$k]))
										unset($possible[$k]);

					if(count($possible) == 1){ //ie all except 1, this must be that last 1
						if($GLOBALS['debug']) echo "$c,$r: $type<br>";
						if(!set($grid, $r, $c, array_pop($possible)))
							return false;
						continue 2;
					}
				}
			}
	}while($sets != $GLOBALS['sets']);

//purely guess, find best guess
	$least = $lr = $lc = 0;
	for($r = 0; $r < 9; $r++)
		for($c = 0; $c < 9; $c++)
			if(is_array($grid[$r][$c]) && (!$least || count($grid[$r][$c]) < $least)){
				$lr = $r; $lc = $c;
				$least = count($grid[$r][$c]);
			}

	if(!$least)	return true; //no possibilities, must be done

	foreach($grid[$lr][$lc] as $k){ //try all possibilities for best guess
		if($GLOBALS['debug']) echo "$lc,$lr: guess $k<br>";

		$copy = $grid; //play with a copy
		if(!set($copy, $lr, $lc, $k))
			continue;

		if(run($copy)){
			$grid = $copy;
			return true;
		}elseif($GLOBALS['debug']) echo "$lc,$lr: unguess $k<br>";
	}
}

function output( & $grid){
	echo "<table border=1 cellspacing=0 cellpadding=0>\n";
	for($i = 0; $i < 9; $i++){
		echo "<tr>";
		for($j = 0; $j < 9; $j++){
			$output = ">"; //unknown, no possibilities
			if(is_array($grid[$i][$j]))	$output = ">" . implode(",", $grid[$i][$j]);
			elseif($grid[$i][$j])		$output = " class=a>" . $grid[$i][$j];

			echo "<td$output</td>";
			if($j == 2 || $j == 5)
				echo "<td class=vb></td>";
		}
		echo "</tr>\n";

		if($i == 2 || $i == 5)
			echo "<tr><td colspan=11 class=hb></td></tr>\n";
	}
	echo "</table><br>\n";
}

function set( & $grid, $r, $c, $v){
	$GLOBALS['sets']++;

	$grid[$r][$c] = $v;

	$locs = getLocs($r, $c);
	foreach($locs as $type => $locs1)
		foreach($locs1 as $i => $locs2)
			foreach($locs2 as $j => $blah)
				if(is_array($grid[$i][$j]) && isset($grid[$i][$j][$v]))
					unset($grid[$i][$j][$v]);

	if($GLOBALS['debug']) output($grid);

	for($i = 0; $i < 9; $i++)
		for($j = 0; $j < 9; $j++)
			if(is_array($grid[$i][$j]) && count($grid[$i][$j]) == 0)
				return false;

	for($i = 0; $i < 9; $i++)
		for($j = 0; $j < 9; $j++)
			if(is_array($grid[$i][$j]) && count($grid[$i][$j]) == 1){
				if($GLOBALS['debug']) echo "$j,$i: single<br>";
				if(!set($grid, $i, $j, array_pop($grid[$i][$j])))
					return false;
			}

	return true;
}

function getLocs($r, $c){
	static $ret = array();

	if(isset($ret[$r][$c]))
		return $ret[$r][$c];

	for($i = 0; $i < 9; $i++)
		$ret[$r][$c]['row'][$r][$i] = 1;

	for($i = 0; $i < 9; $i++)
		$ret[$r][$c]['col'][$i][$c] = 1;

	$baser = floor($r/3)*3;
	$basec = floor($c/3)*3;
	for($i = 0; $i < 3; $i++)
		for($j = 0; $j < 3; $j++)
			$ret[$r][$c]['box'][$i+$baser][$j+$basec] = 1;

	return $ret[$r][$c];
}

function gettime(){
	list($usec, $sec) = explode(" ",microtime());
	return (10000*((float)$usec + (float)$sec));
}
