<?

	$ips = array('10.0.', '192.168.');

	foreach($ips as $ip){
		if(strncmp($_SERVER['REMOTE_ADDR'], $ip, strlen($ip)) == 0){
			apc_clear_cache('user');
//			apc_clear_cache('files'); //doesn't work too well... crashes apc
			die("cleared\n");
		}
	}

	echo "failed\n";
