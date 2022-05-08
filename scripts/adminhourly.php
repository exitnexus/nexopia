#!/usr/bin/php
<?

$forceserver=true;
$errorLogging=true;

//*
//#!/usr/bin/php
	$_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'] = ".www.nexopia.com";
	$_SERVER['DOCUMENT_ROOT'] = "/home/nexopia/public_html";
	$mode = 1; //normal
//	ob_start(); //dump all at once at the end
/*/
//#!/usr/local/bin/php -q
	$_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'] = "www.nexopia.sytes.net";
	$_SERVER['DOCUMENT_ROOT'] = "/htdocs/nexopia/public_html";
	$mode = 2; //daily
	$mode = 3; //daily and backups
//*/
	require_once($_SERVER['DOCUMENT_ROOT'] . "/include/general.lib.php");
	require_once($_SERVER['DOCUMENT_ROOT'] . "/include/backup.php");

set_time_limit(0);

hourlyStat($mode);

/*
mode = 1: normal, if 5am, run daily, including backups
mode = 2: run daily regardless, don't run backups
mode = 3: run daily and backups
*/

function hourlyStat($mode = 1){
	global $config, $dbs, $db, $statsdb, $disp, $sitebasedir, $mods, $banner, $debuginfousers, $messaging, $usercomments, $polls, $sessiondb, $forums, $logdb, $profviewsdb;

	$newtime=gmmktime(gmdate("H"),0,0,gmdate("n"),gmdate("j"),gmdate("Y"));

	foreach($dbs as $slowdb)
		$slowdb->setslowtime(6000000);	//600 secs

	$db->begin();

//	echo "<pre>\n";

	$timer = new timer();
	$timer->start("start admin hourly - " . gmdate("F j, g:i a T"));


echo $timer->lap("add hourly stats");
	$statsdb->prepare_query("INSERT IGNORE INTO statshist SELECT ? as time, stats.* FROM stats", $newtime);

/*	if($mode & 1  && $statsdb->affectedrows()){


	echo $timer->lap("update hourly hits");
		$lasthour = $newtime - 3600;

		$statsdb->query("SELECT hitstotal FROM stats");
		$totalhits = $statsdb->fetchfield();

		$db->query("SELECT total, time FROM hithist ORDER BY time DESC LIMIT 1");
		$line = $db->fetchrow();

		$lasttotal = $line['total'];
		$lasttime = $line['time'];

		$hours = ($lasthour - $lasttime)/3600;

		$hitsdif = $totalhits - $lasttotal;

		$db->prepare_query("INSERT INTO hithist SET time = ?, hits = ?, total = ?", $lasthour, $hitsdif, $totalhits);

	echo $timer->lap("max hits in an hour");
		if($hours==1){
			$statsdb->query("SELECT hitsmaxhour FROM stats");
			$maxhour = $statsdb->fetchfield();

			if($hitsdif>$maxhour){
				$statsdb->prepare_query("UPDATE stats SET hitsmaxhour = ?", $hitsdif);
			}
		}

	echo $timer->lap("max hits in a day");
		$db->prepare_query("SELECT total FROM hithist WHERE time >= ? ORDER BY time ASC LIMIT 1", $newtime - 86400);
		$total = $totalhits - $db->fetchfield();

		$statsdb->query("SELECT hitsmaxday FROM stats");
		$maxday = $statsdb->fetchfield();

		if($total>$maxday)
			$statsdb->prepare_query("UPDATE stats SET hitsmaxday = ?", $total);


	echo $timer->lap("active ips per hour");
		$logdb->prepare_query(false, "SELECT count(*) FROM iplog WHERE time >= ?", $newtime - 3600);
		$ips = $logdb->fetchfield();

		$statsdb->query("SELECT ipsmaxhour FROM stats");
		$maxhour = $statsdb->fetchfield();

		if($ips>$maxhour)
			$statsdb->prepare_query("UPDATE stats SET ipsmaxhour = ?", $ips);


	echo $timer->lap("active ips per day");
		$logdb->prepare_query(false, "SELECT count(*) FROM iplog WHERE time >= ?", $newtime - 86400);
		$ips = $logdb->fetchfield();

		$statsdb->query("SELECT ipsmaxday FROM stats");
		$maxhour = $statsdb->fetchfield();

		if($ips>$maxhour)
			$statsdb->prepare_query("UPDATE stats SET ipsmaxday = ?", $ips);
	}
*/

echo $timer->lap("clean up newest users list");
	$sexes = array("Male","Female");
	$ages = range(14,60);
	$ids = array();

	foreach($ages as $age){
		foreach($sexes as $sex){
			$db->prepare_query("SELECT id FROM newestusers WHERE age = ? && sex = ? ORDER BY id DESC LIMIT 10, 1000", $age, $sex);

			while($line = $db->fetchrow())
				$ids[] = $line['id'];
		}
	}
	if(count($ids))
		$db->prepare_query("DELETE FROM newestusers WHERE id IN (?)", $ids);


echo $timer->lap("clean up recently update profile list");
	$sexes = array("Male","Female");
	$ages = range(14,60);

	$ids = array();

	foreach($ages as $age){
		foreach($sexes as $sex){
			$db->prepare_query("SELECT id FROM newestprofile WHERE age = ? && sex = ? ORDER BY id LIMIT 10,10000", $age, $sex);

			while($line = $db->fetchrow())
				$ids[] = $line['id'];
		}
	}
	if(count($ids))
		$db->prepare_query("DELETE FROM newestprofile WHERE id IN (?)", $ids);


echo $timer->lap("delete old uncached sessions (1 hour)");
	$sessiondb->prepare_query(false, "DELETE FROM sessions WHERE cachedlogin='n' && activetime <= ?", $newtime-3600);



///////////////////////////////////////////////
////////////// start daily stuff //////////////
///////////////////////////////////////////////

	if( ($mode & 2) || gmdate("H") == 10 ){ //so about 5am here

echo $timer->lap("start admin daily - " . gmdate("F j, g:i a T") );

echo $timer->lap("update stats[totalusers]");
		$db->query("SELECT count(*) FROM users");
		$totalusers = $db->fetchfield();

		$statsdb->prepare_query("UPDATE stats SET userstotal = ?", $totalusers);

echo $timer->lap("update stats[userswithpics]");
		$db->query("SELECT count(*) FROM users WHERE firstpic > 0");

		$userswithpics = $db->fetchfield();

		$statsdb->prepare_query("UPDATE stats SET userswithpics = ?", $userswithpics);


echo $timer->lap("update stats[userswithsignpics]");
		$db->query("SELECT count(*) FROM users WHERE signpic = 'y'");

		$userswithsignpics = $db->fetchfield();

		$statsdb->prepare_query("UPDATE stats SET userswithsignpics = ?", $userswithsignpics);

echo $timer->lap("delete old cached sessions (30 days)");
		$sessiondb->prepare_query(false, "DELETE FROM sessions WHERE cachedlogin='y' && activetime <= ?", $newtime-86400*30);

echo $timer->lap("Prune filesystem fileupdates table");
		global $filesystem;
		$filesystem->prunedb();

echo $timer->lap("delete old unactivated accounts (1 week)");
		$db->prepare_query("SELECT userid FROM users WHERE activated='n' && frozen = 'n' && jointime < ? && activetime < ?", ($newtime-86400*7), ($newtime-86400*7));
		$uids = array();
		while($line = $db->fetchrow())
			$uids[] = $line['userid'];
		deleteAccount($uids,"Account not activated");


echo $timer->lap("update votable pics");
		updatePicsVotable();

echo $timer->lap("create the top lists");
		setTopLists();

echo $timer->lap("update spotlight list");
		updateSpotlightList();

echo $timer->lap("update everyones ages based on b-day");
		updateUserBirthdays();

echo $timer->lap("update all the search indexes");
		updateUserIndexes();

echo $timer->lap("set firstpic for everyone");
		$db->query("UPDATE users LEFT JOIN pics ON users.userid=pics.itemid && pics.priority=1 SET users.firstpic = pics.id");


echo $timer->lap("clear profile hit history after a week");
		$profviewsdb->repeatquery(false, $profviewsdb->prepare("DELETE FROM profileviews WHERE time <= ?", $newtime - 86400*7));


echo $timer->lap("clear pic vote history");
		$db->repeatquery($db->prepare("DELETE FROM votehist WHERE time <= ?", $newtime-$config['voteHistLength']));


echo $timer->lap("clear poll vote history");
		$polls->db->prepare_query("DELETE FROM pollvotes WHERE time <= ?", $newtime-$config['voteHistLength']);

echo $timer->lap("delete gallery pics associated with deleted galleries");
		$db->query("DELETE gallery FROM gallery LEFT JOIN gallerycats ON gallery.category = gallerycats.id WHERE gallerycats.id IS NULL");

echo $timer->lap("delete old article comments (3 months)");
		$db->prepare_query("DELETE FROM comments WHERE time <= ?", $newtime-86400*90 );
		$db->query("DELETE commentstext FROM commentstext LEFT JOIN comments ON comments.id=commentstext.id WHERE comments.id IS NULL"); //untested

echo $timer->lap("prune the forums (inactive forums: 4 weeks, inactive threads: 7*sorttime)");
		$forums->pruneforums();

echo $timer->lap("delete old forum reads (14 days)");
		$forums->db->repeatquery($forums->db->prepare("DELETE FROM forumread WHERE readtime <= ?", $newtime-86400*14));



//echo $timer->lap("remove expired bans");
//		$db->prepare_query("DELETE FROM forummute WHERE unmutetime > 0 && unmutetime <= ?", $newtime );

/*
//moditems is in different db than picspending, forumrankspending

echo $timer->lap("add moditems for all pending pics that got lost");
	$db->query("INSERT IGNORE INTO moditems (itemid,type) SELECT picspending.id," . MOD_PICS . " FROM picspending LEFT JOIN moditems ON picspending.id=moditems.itemid && moditems.type = " . MOD_PICS . " WHERE moditems.id IS NULL");

echo $timer->lap("add moditems for all pending forumranks that got lost");
	$db->query("INSERT IGNORE INTO moditems (itemid,type) SELECT forumrankspending.id," . MOD_FORUMRANK . " FROM forumrankspending LEFT JOIN moditems ON forumrankspending.id=moditems.itemid && moditems.type = " . MOD_FORUMRANK . " WHERE moditems.id IS NULL");
*/

echo $timer->lap("delete bad moditems");
		$mods->db->query("DELETE FROM moditems WHERE points < -5");

		$deleted = $mods->db->affectedrows();
		if($deleted > 0)
			trigger_error("Deleted $deleted bad rows from the moditems table", E_USER_NOTICE);




//*
echo $timer->lap("fix missing pending pic moditems");
		$mods->db->prepare_query("SELECT MIN(itemid) FROM moditems WHERE type = ?", MOD_PICS);
		$id = $mods->db->fetchfield();

		$result = $db->prepare_query("SELECT id FROM picspending WHERE id < ?", $id);

		while($line = $db->fetchrow($result))
			$mods->db->prepare_query("INSERT IGNORE INTO moditems SET itemid = ?, type = ?", $line['id'], MOD_PICS);

//*/


echo $timer->lap("delete old mod votes log items");
		$db->prepare_query("DELETE FROM modvoteslog WHERE time <= ?", $newtime-86400*30 );


echo $timer->lap("dump mod stats");
		$mods->dumpModStats();
//		$moddb->prepare_query("INSERT INTO modhist SELECT ? as dumptime, userid, type, username, `right`, `wrong`, lenient, strict, level, time, creationtime FROM mods", gmmktime(0,0,0,gmdate("n"),gmdate("j"),gmdate("Y")));


echo $timer->lap("delete old login log items");
		$logdb->prepare_query(false, "DELETE FROM loginlog WHERE time <= ?",  $newtime-86400*21);

echo $timer->lap("do mod promotions");
		$mods->doPromotions($debuginfousers[0]);

echo $timer->lap("recalculate all vote totals/scores. Get rid of scores > 10");
		$db->query("UPDATE pics SET votes = v1+v2+v3+v4+v5+v6+v7+v8+v9+v10, score = (v1 + 2*v2 + 3*v3 + 4*v4 + 5*v5 + 6*v6 + 7*v7 + 8*v8 + 9*v9 + 10*v10)/votes");



echo $timer->lap("deleted user cleanup");
		deleteUserCleanup();

echo $timer->lap("delete old messages");
		$messaging->prune();

echo $timer->lap("delete old user comments (1 month)");
		$usercomments->prune();

echo $timer->lap("triggering user notifications");
		$usernotify->triggerNotifications();

//backups and optimizations

		if($mode & 1){

echo $timer->lap("start daily optimizations - " . gmdate("F j, g:i a T") );

//optimize/analyze dbs
			foreach($dbs as $name => $optdb){
				if(gmdate("w") == 0){
					echo $timer->lap("optimize db: $name");
					$dbs[$name]->optimize(0);
				}else{
					echo $timer->lap("analyze db: $name");
					$dbs[$name]->analyze(0);
				}
			}

echo $timer->lap("start daily backups - " . gmdate("F j, g:i a T") );

echo $timer->lap("backup code");

			$dailybackupbasedir = "$sitebasedir/backup/daily/" . gmdate("D");

			system("rm -rf $dailybackupbasedir/*");
			mkdirrecursive("$dailybackupbasedir/code");
			system("rm -rf $dailybackupbasedir/code/*");
			system("cp $sitebasedir/public_html/*.php $dailybackupbasedir/code/");
			system("cp -r $sitebasedir/public_html/include $dailybackupbasedir/code/");
			system("cp -r $sitebasedir/public_html/skins $dailybackupbasedir/code/");



//backup dbs
			foreach($dbs as $name => $backdb){
	echo $timer->lap("backup db: $name");
				mkdirrecursive("$dailybackupbasedir/$name");
				$dbs[$name]->backup($dailybackupbasedir . "/$name", 0);
			}
/*
//clear old master logs
			foreach($dbs as $name => $backdb){
				if(!isset($dbs[$name]->insertdb))
					continue;

				$dbs[$name]->insertdb->query("SHOW MASTER STATUS");
				$status = $dbs[$name]->fetchrow();

				$filename = substr($status['File'], 0, strpos($status['File'], '.') + 1); //up to and including the .
				$number = substr($status['File'], strpos($status['File'], '.') + 1); //only the number

				//keep last 10 files.
				if($number > 10){
					echo $timer->lap("clear db binary logs from $name");
					$dbs[$name]->insertdb->prepare_query("PURGE MASTER LOGS TO ?", $filename  . ($number - 10));
				}
			}
*/
//keep weekly backup
			if( gmdate("w") == 0){
				$weeklybackupbasedir = "$sitebasedir/backup/weekly/" . gmdate("Y.m.d");
				mkdirrecursive("$weeklybackupbasedir/");

echo $timer->lap("keep a weekly backup");
				system("cp -al $dailybackupbasedir/* $weeklybackupbasedir/");
				//system("tar cf - $dailybackupbasedir | gzip -c > $weeklybackupbasedir/backup.tar.gz
			}
		}

//end daily
	}
echo $timer->lap("done - " . gmdate("F j, g:i a T") );
echo $timer->stop();
//echo "</pre>\n";
//$db->insertdb->outputQueries("insert");
}


