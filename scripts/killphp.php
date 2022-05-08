#!/usr/local/php/bin/php
<?

$input = exec("ps auxw --sort=rss | grep php-cgi | grep -v grep");

$inputs = explode("\n", $input);

$pids = array();
foreach($inputs as $line)
	if(preg_match("/(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(.*)/", $line, $matches))
		if($matches[6] > 100000)
			$pids[] = $matches[2];

if($pids){
	$cmd = "kill -9 " . implode(" ", $pids);
	echo "$cmd\n";
	exec($cmd);
}else{
	echo "none to kill\n";
}


