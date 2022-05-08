#!/usr/local/php/bin/php
<?

	set_time_limit(0);
	$forceserver=true;
	$errorLogging=true;
	
	
	// grab command line args and script name
	$scriptName = $_SERVER['SCRIPT_NAME'] = array_shift($argv);
	
	//change the working directory of this script to public_html relative to the path of the script
	if(basename(getcwd()) == 'public_html') //already in public_html
		$newCwd = getcwd();
	elseif(!file_exists(getcwd() . "/" . basename($scriptName))) //somewhere else weird
		$newCwd = dirname($scriptName) . '/public_html';
	else //in the root, public_html
		$newCwd = 'public_html';
	
	chdir($newCwd);



	require_once("include/general.lib.php");

	echo "test\n";

	$dbusername = "nexopia";

	print_r($databases);

	


function optimizeandswap($dbname, $dbnum){
/*

disable replication on current master
optimize on slave
send swap command to monitor daemon
enable replication on old master
optimize on old master - automatic 	

*/
}

function swapmastersclean($dbname, $dbnum){
	global $databases, $cache, $$dbname, $dbusername;
	
	if($dbnum){
		$dbloc = & $databases[$dbname]['sources'][$dbnum];
		$split = ${$dbname}->getSplitDBs();
		$db = & $split[$dbnum];
	}else{
		$dbloc = & $databases[$dbname];
		$db = & $$dbname;
	}
	
	$oldmaster = $dbloc['master'];
	$newmaster = ($oldmaster == 'host1' ? 'host2' : 'host1');

	$memcachekey = $dbloc['memcache'];

	//set it in memcache so the cutoff is cleaner
	$cache->put("db-$memcachekey", $oldmaster, 3600);

	//push a config that says to ask memcache for the config
	$dbloc['master'] = '';
	pushdbconfig($databases);

	//let everything using the old config finish
	sleep(15);


	//wait for slave to catch up. This is just to make the wait later quicker
	$res = $db->$oldmaster->query("SHOW MASTER STATUS");
	$pos = $res->fetchrow();

	$db->$newmaster->prepare_query("SELECT MASTER_WAIT_POS(#,#)", $pos['File'], $pos['Position']);


	//tell the new connections to wait
	$cache->put("db-$memcachekey", '', 3600);

	sleep(1); //let pages that were already connected before memcache finish

	//wait for all processes to disconnect
	while(1){
		$found = 0;

		$res = $db->$oldmaster->query("SHOW PROCESSLIST");
		while($line = $res->fetchrow())
			if($line['User'] == $dbusername && $line['Info'] != "SHOW PROCESSLIST")
				$found++;
		
		if(!$found)
			break;
		
		usleep(10000); //10ms
	}

	//find the binlog position and wait for slave to catch up
	$res = $db->$oldmaster->query("SHOW MASTER STATUS");
	$pos = $res->fetchrow();

	$db->$newmaster->prepare_query("SELECT MASTER_WAIT_POS(#,#)", $pos['File'], $pos['Position']);


	//swap over finished


	//push the new config, memcache first
	$cache->put("db-$memcachekey", $newmaster, 3600);

	$memcachekey['master'] = $newmaster;
	pushdbconfig($databases);
}

function swapmastersfailover($dbname, $dbnum = false){
	global $databases;
	
	if($dbnum)
		$dbloc = & $databases[$dbname]['sources'][$dbnum];
	else
		$dbloc = & $databases[$dbname];
	
	
	$oldmaster = $dbloc['master'];

	$newmaster = ($oldmaster == 'host1' ? 'host2' : 'host1');

	$dbloc['master'] = $newmaster;
	pushdbconfig($databases);
}


