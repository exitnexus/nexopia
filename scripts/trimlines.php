#!/usr/local/php/bin/php
<?
	

	$end = (isset($argv[1]) ? $argv[1] : 200);
	$start = (isset($argv[2]) ? $argv[2] : 0);

	while($line = trim(fgets(STDIN)))
		echo substr($line, $start, $end-$start) . "\n";
