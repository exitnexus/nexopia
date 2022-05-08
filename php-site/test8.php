<?
	require_once("include/general.lib.php");

	$userData=auth($userid,$key);

	randomize();

	$ar = array(1 => 0,2 => 0,3 => 0,4 => 0,5 => 0,6 => 0);

	$total = 0;

	for($i=0;$i<10000;$i++){
		$c = rand(1,6);
		
		$ar[$c]++;
		$total += $c;
	}
	
	print_r($ar);
	
	
	echo "average: " . $total / 10000;
