#!/usr/local/php/bin/php -q
<?

	$forceserver=true;
	$errorLogging=true;
	$_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'] = ".www.nexopia.com";
	chdir("public_html");

	require_once("include/general.lib.php");


	$verbose = 2;       // verbose level? 0 - nothing, 1 - only final stats, 2 - little output, 3 - lots of output
	$movetime = 1500;   // target time (in milliseconds) to spend moving each group of users
	$maxmovecount = 20; // max amount of users to move at a time. Don't set too high, as it used along with the margin to decide if it's finished
	$margin = 5;        // % difference allowed between min and max server counts
	$sleep = 500;       // milliseconds between runs
	$exitdone = true;   // idle or exit when there's nothing to move?
	$timelimit = 5400;     // exit after this length of time (in seconds), 0 to disable
	$maxmoved = 0;      // max amount of users to move in total
	$allowservers = array(); // list of servers to balance between, useful to run multiple instances in parallel without overlap
	$drain = false;     // drain servers with a weight of 0. Not specifying the allowed servers will cause this to fail as a precaution.

//////////////////////////////////////////////

	$name = array_shift($argv);

	while(1){
		if(!count($argv))
			break;

		$arg = array_shift($argv);

		switch($arg){
			case '-q':
				$verbose--;
				break;

			case '-v':
				$verbose++;
				break;

			case '--drain':
				$drain = true;
				break;

			case '-t':
				$timelimit = array_shift($argv);
				break;

			case '-m':
				$maxmoved = array_shift($argv);
				break;

			case '--servers':
				$vars = explode(',', array_shift($argv));
				foreach($vars as $var){
					$var = explode('-', $var);
					if(count($var) == 1)
						$allowservers[] = $var[0];
					elseif(count($var) == 2)
						$allowservers = array_merge($allowservers, range($var[0], $var[1]));
					else
						die("Bad server list\n");
				}
				break;

			case '--help':
				die("$name [-q|-v] [-t timelimit] [-m movelimit] [--servers server-ids,to,balance] [--drain]\n");

			default:
				die("Unknown Argument $arg\n");
		}
	}

//////////////////////////////////////////////

	$nummoved = 0; //number of users moved in total
	$timemoving = 0;
	$timesleeping = 0;
	$servermoves = array();

//////////////////////////////////////////////

	if(!$lockSplitWriteOps)
		die("Enable \$lockSplitWriteOps in the config file to run. This is to reduce race conditions and insconsistencies.\n");

	if($drain && count($allowservers) == 0)
		die("You must specify servers to balance when using the drain option\n");

	$tables = include("../tableinfo.php");

	if(!$tables)
		die("Missing or bad list of tables");

	$schema = false;
	$errors = $usersdb->checkschema($schema);

	if($errors)
		die("Schemas have problems:\n$errors");

	foreach($schema as $table => $v)
		if(!isset($tables[$table]))
			die("Missing definition in tableinfo.php for $table\n");

//remove tables that are agregates instead of split or that don't exist in this database (probably in dev?)
	foreach($tables as $table => $splitcol)
		if(!$splitcol || !isset($schema[$table]))
			unset($tables[$table]);


	$dbobjs = $usersdb->getSplitDBs();

//make sure the tables have the primary key that tableinfo says they do
/*
//doesn't work because some tables have indexes instead of primary keys
//usersearch has it's primary key on 'id' instead of userid, but we still want to move the usersearch
	foreach($tables as $table => $pkey){
		$res = $dbobjs[0]->query("SHOW COLUMNS FROM `$table`");
		$firstcol = "";
		while($line = $res->fetchrow())
			if(!$firstcol && ($line['Key'] == 'PRI' || $line['Key'] == 'MUL'))
				$firstcol = $line['Field'];

		if($pkey != $firstcol)
			die("The first column with an index in $table doesn't seem to match tableinfo.php. It may be a badly formed table?\n");
	}
*/

	$start = time();
	$time1 = 0;
	$time2 = 0;
	$movecounts = array();

	while(1){ //main loop
	//timelimit
		if($timelimit && (time() - $start >= $timelimit)){
			myprint("Hit the timelimit, exiting.\n", 1);
			break;
		}

	//move limit
		if($maxmoved && $nummoved >= $maxmoved){
			myprint("Hit the move limit, exiting.\n", 1);
			break;
		}

	//decide how many to move at a time based on the past 10 cycles
		if($time1 && $time2){
			$movecounts[] = ($movecount * ($movetime / (($time2-$time1)/10))); //would've been optimal
			if(count($movecounts) > 10)
				array_shift($movecounts);

			$movecount = round(array_sum($movecounts) / count($movecounts));

		//don't go over the max
			if($movecount > $maxmovecount)
				$movecount = $maxmovecount;

		//don't stall
			if($movecount < 1)
				$movecount = 1;
		}else{
			$movecount = 1; //initial value of 1, growing from there
		}
		
	//possibly limit serverids
		$serverclause = "";
		if($allowservers)
			$serverclause .= $masterdb->prepare(" && serverid IN (#)", $allowservers);
		if(!$drain)
			$serverclause .= " && weight > 0";

	//figure out where to move from and to
		$res = $masterdb->query("SELECT serverid, weight, IF(weight, totalaccounts/weight, totalaccounts) as count FROM serverbalance WHERE type = 6 $serverclause");

		$min = 0;
		$max = 0;

		$servers = array();
		while($line = $res->fetchrow()){
			$servers[$line['serverid']] = $line['count'];

			if((!$drain || $line['weight'] > 0) && (!$min || $line['count'] < $servers[$min]))
				$min = $line['serverid'];

			if((!$drain || $line['weight'] == 0) && (!$max || $line['count'] > $servers[$max]))
				$max = $line['serverid'];
		}

	//nothing to move
		if($drain){
			if($min == 0 || $max == 0 || $servers[$max] == 0){
				myprint("Servers drained, exiting.\n", 1);
				break;
			}
		}elseif(($servers[$max] < $servers[$min]*(1.0 + $margin/100.0) || $servers[$max] - $servers[$min] < $maxmovecount*2)){
			if($exitdone){
				myprint("Servers balanced within $margin%, exiting.\n", 1);
				break;
			}else{
				myprint("Servers balanced within $margin%, sleeping.\n", 2);
			}

			sleep(60);
			$timesleeping += 60;
			continue;
		}

		$dbfrom = $max;
		$dbto = $min;

		$time1 = gettime();

	//find users to move, grab the newest users on the db, as they're least likely to have friends on this db
		$res = $masterdb->prepare_query("SELECT id, state FROM accounts WHERE serverid = # ORDER BY id DESC LIMIT #", $dbfrom, $movecount);

		$activeaccounts = 0;
		$uids = array();
		while($line = $res->fetchrow()){
			$uids[] = $line['id'];
			if($line['state'] != ACCOUNT_STATE_DELETED)
				$activeaccounts++;
		}

		$nummoved += count($uids);
		
		if(!isset($servermoves[$dbfrom]))
			$servermoves[$dbfrom] = 0;
		if(!isset($servermoves[$dbto]))
			$servermoves[$dbto] = 0;
		
		$servermoves[$dbfrom] -= count($uids);
		$servermoves[$dbto]   += count($uids);

		myprint("Moving " . count($uids) . " users from $dbfrom to $dbto\n", 2);
		if(count($uids) < 10)
			myprint("Moving userids: " . implode(', ', $uids) . "\n", 3);
		myprint("---------------------------------------\n", 3);

		myprint("Setting memcache locks.\n", 3);
		foreach($uids as $uid)
			$cache->put("serverid-user-$uid", -2, 86400); // serverid of -2 means move in progress

		myprint("Setting users as moving.\n", 3);
		$masterdb->begin();
		$masterdb->prepare_query("SELECT * FROM accounts WHERE id IN (#) FOR UPDATE", $uids);

		myprint("Reasserting memcache locks.\n", 3);
		foreach($uids as $uid)
			$cache->put("serverid-user-$uid", -2, 86400);

		usleep(50000); //sleep 50ms to reduce the likelyhood of a race condition (between page getting serverid and running the query)
//		$timesleeping += 0.05;

		myprint("---------------------------------------\n", 3);
		myprint("Copying user's data.\n", 3);
		myprint("Copying: ", ($verbose == 2));
		foreach($tables as $table => $uidcol){
			myprint("Copying table $table($uidcol) - ", 3);
			$dbobjs[$dbto]->prepare_query("DELETE FROM `$table` WHERE $uidcol IN (#)", $uids); // should this really be here?

			$result = $dbobjs[$dbfrom]->prepare_query("SELECT * FROM `$table` WHERE $uidcol IN (#)", $uids);

			$inserts = "";
			$cols = "";
			while($row = $result->fetchrow()){
				if($inserts)
					$inserts .= ',';

				if(!$cols)
					$cols = '(`' . implode('`,`', array_keys($row)) . '`)';

				$inserts .= $usersdb->prepare("(?)", $row);

				if(strlen($inserts) > 20000){ //insert ~20kb at a time.
					$dbobjs[$dbto]->query("INSERT INTO `$table` $cols VALUES " . $inserts);
					$inserts = "";
				}
			}

			if($inserts)
				$dbobjs[$dbto]->query("INSERT INTO `$table` $cols VALUES " . $inserts);

			myprint("Done\n", 3);
			myprint(".", ($verbose == 2));
		}
		myprint("\n", ($verbose == 2));


		myprint("Updating\n", ($verbose == 2));
		myprint("---------------------------------------\n", 3);
		myprint("Setting user's new server.\n", 3);

		$masterdb->prepare_query("UPDATE accounts SET serverid = # WHERE id IN (#)", $dbto, $uids);

		myprint("Updating servercounts.\n", 3);
		$masterdb->prepare_query("UPDATE serverbalance SET totalaccounts = totalaccounts - #, realaccounts = realaccounts - # WHERE serverid = #", count($uids), $activeaccounts, $dbfrom);
		$masterdb->prepare_query("UPDATE serverbalance SET totalaccounts = totalaccounts + #, realaccounts = realaccounts + # WHERE serverid = #", count($uids), $activeaccounts, $dbto);

		myprint("Removing memcache locks.\n", 3);
		foreach($uids as $uid)
			$cache->put("serverid-user-$uid", $dbto, 86400*7);

		$masterdb->commit();

		myprint("---------------------------------------\n", 3);
		myprint("Deleting user's data on old server.\n", 3);
		myprint("Deleting: ", ($verbose == 2));
		foreach($tables as $table => $uidcol){
			myprint("Deleting from table $table($uidcol) - ", 3);

			$dbobjs[$dbfrom]->prepare_query("DELETE FROM `$table` WHERE $uidcol IN (#)", $uids);

			myprint("Done\n", 3);
			myprint(".", ($verbose == 2));
		}
		myprint("\n", ($verbose == 2));


		$time2 = gettime();

		myprint("---------------------------------------\n", 3);
		myprint("Done in " . number_format(($time2 - $time1)/10) . " ms.\n\n", 2);

		$timemoving += ($time2-$time1)/10000;

		usleep($sleep*1000);          //convert from milliseconds to microseconds
		$timesleeping += $sleep/1000; //convert from milliseconds to seconds
	}

	ksort($servermoves);

	myprint("\n", 1);

	if(count($servermoves)){
		myprint("Move Histogram:\n", 1);
		foreach($servermoves as $id => $moved)
			myprint("    Server $id: " . ($moved > 0 ? '+' : '') . number_format($moved) . "\n", 1);
		myprint("\n", 1);
	}

	myprint("Total Moved:   " . number_format($nummoved) . " users\n", 1);
	
	myprint("Max Move Rate: " . number_format($nummoved/$timemoving, 2) . " users/second\n", 1);
	myprint("Eff Move Rate: " . number_format($nummoved/($timesleeping + $timemoving), 2) . " users/second\n", 1);
	
	myprint("Time Moving:   " . number_format($timemoving) . " seconds\n", 1);
	myprint("Time Sleeping: " . number_format($timesleeping) . " seconds\n", 1);
	myprint("Time Total:    " . number_format($timesleeping + $timemoving) . " seconds\n", 1);
	

function myprint($str, $level){
	global $verbose;

	if(($level === true) || ($level !== false && $level <= $verbose))
		echo $str;
}
