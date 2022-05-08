#!/usr/local/php/bin/php
<?

	$lasttime = time();

	$matches = array( "Memcache broken pipe" => "/memcached-client.php:.*fwrite\(\) : send of .* bytes failed with errno=32 Broken pipe/",
					  "Memcache parse error" => "Error parsing memcached response from",
					  "Query doesn't map" => "Query doesn't map to a server:",
					  "Slow query" => "SQL warning: took",
					  "Banner Connect" => "banner6.php:1535",
					  "PECL reference" => "/PEAR.php:.* Assigning the return value of new by reference is deprecated/",
					  "Banner Disconnect" => "/bannerserver.php:.* socket_read\(\): unable to read from socket .*: Connection reset by peer/",
					  "Database Connect" => "Failed to Connect to Database:",
					  "Too Many Connections" => "mysql_connect() : Too many connections",
					  "Lost Connection" => "mysql_connect() : Lost connection",
					  "Can't Connect" => "mysql_connect() : Can't connect to MySQL",
					  "Undefined Index" => "Undefined index:",
					  "Undefined Offset" => "Undefined offset:",
					  "Undefined Variable" => "Undefined variable:",
					  "Invalid Foreach" => "Invalid argument supplied for foreach",
					  "Other" => " ",
					);

	$only = array_keys($matches);
	if(count($argv) > 1)
		$only = array_slice($argv, 1);


	$cur = array();
	foreach($matches as $error => $search)
		$cur[$error] = 0;

	$other = array();

	while($line = trim(fgets(STDIN))){
		$pos = 0;
		foreach($matches as $error => $search){
			if($search[0] == '/')
				$pos = preg_match($search, $line);
			else
				$pos = strpos($line, $search);
		
			if($pos){
				if(in_array($error, $only)){
					$cur[$error]++;
			
					if($error == "Other")
						$other[] = $line;
				}
				break;
			}
			
		}
		

		$time = time();

		if($time != $lasttime && array_sum($cur)){
			$lasttime = $time;
			
			foreach($cur as $error => $num){
				if($num)
					echo str_pad(number_format($num), 5, ' ', STR_PAD_LEFT) . " - $error\n";
				$cur[$error] = 0;
			}
			foreach($other as $line)
				echo substr($line, 0, 175) . "\n";
			$other = array();
			echo "-----------------------------------------\n";
		}
	}


