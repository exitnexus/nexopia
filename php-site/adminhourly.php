#!/usr/local/bin/php
<?

$forceserver=true;
$errorLogging=false;

//*
	$HTTP_HOST="www.nexopia.com";
	$SERVER_NAME="www.nexopia.com";
	$DOCUMENT_ROOT = "/home/nexopia/public_html";
/*/
	$HTTP_HOST="enternexus.fobax.sytes.net";
	$SERVER_NAME="enternexus.fobax.sytes.net";
	$DOCUMENT_ROOT = "/htdocs/enternexus/public_html";
//*/
	require_once($DOCUMENT_ROOT."/include/general.lib.php");
	require_once($DOCUMENT_ROOT."/include/backup.php");

set_time_limit(0);

hourlyStat(2);

/*
mode = 1: normal, run hourly stats update, if 5am, run daily
mode = 2: don't run hourly stats, run daily

*/

function hourlyStat($mode = 1){
	global $config,$db, $fastdb,$disp,$sitebasedir;

	$newtime=gmmktime(gmdate("H"),0,0,gmdate("n"),gmdate("j"),gmdate("Y"));

	$db->begin();

	if($mode == 1){


	//echo __LINE__ . "\n";
	//update hourly hits
		$lasthour = $newtime - 3600;

		$fastdb->query("SELECT count FROM stats WHERE type='hits' && var='total'");
		$totalhits = $fastdb->fetchfield();

		$db->query("SELECT total,time FROM hithist ORDER BY time DESC LIMIT 1");
		$line = $db->fetchrow();

		$lasttotal = $line['total'];
		$lasttime = $line['time'];

		$hours = ($lasthour - $lasttime)/3600;

		$hitsdif = $totalhits - $lasttotal;

		$query = "INSERT INTO hithist SET time='$lasthour', hits='$hitsdif', total='$totalhits'";
		$db->query($query);
	//echo __LINE__ . "\n";
	//max hits in an hour
		if($hours==1){
			$fastdb->query("SELECT count FROM stats WHERE type='hits' && var='maxhour'");
			$maxhour = $fastdb->fetchfield();

			if($hitsdif>$maxhour){
				$fastdb->prepare_query("UPDATE stats SET count = ? WHERE type='hits' && var='maxhour'", $hitsdif);
			}
		}
	//echo __LINE__ . "\n";
	//max hits in a day
		$query = "SELECT total FROM hithist WHERE time>='" . ($newtime - 86400) . "' ORDER BY time ASC LIMIT 1";
		$result = $db->query($query);
		$total = $totalhits - $db->fetchfield();

		$fastdb->query("SELECT count FROM stats WHERE type='hits' && var='maxday'");
		$maxday = $db->fetchfield();

		if($total>$maxday){
			$fastdb->prepare_query("UPDATE stats SET count = ? WHERE type='hits' && var='maxday'", $total);
		}
	//echo __LINE__ . "\n";
	//active ips per hour
		$fastdb->prepare_query("SELECT count(*) FROM iplog WHERE time > ?", (time() - 3600));
		$ips = $fastdb->fetchfield();

		$fastdb->query("SELECT count FROM stats WHERE type='ips' && var='maxhour'");
		$maxhour = $fastdb->fetchfield();

		if($ips>$maxhour){
			$fastdb->prepare_query("UPDATE stats SET count = ? WHERE type='ips' && var='maxhour'", $ips);
		}
	//echo __LINE__ . "\n";
	//active ips per day
		$query = "SELECT count(*) FROM iplog WHERE time > " . (time() - 86400);
		$result = $fastdb->query($query);
		$ips = $fastdb->fetchfield();

		$query = "SELECT count FROM stats WHERE type='ips' && var='maxday'";
		$result = $fastdb->query($query);
		$maxhour = $fastdb->fetchfield();

		if($ips>$maxhour){
			$query = "UPDATE stats SET count='$ips' WHERE type='ips' && var='maxday'";
			$fastdb->query($query);
		}
	}

//clean up newest users list
	$sexes = array("Male","Female");
	$ages = range(14,60);
	$ids = array();

	foreach($ages as $age){
		foreach($sexes as $sex){
			$db->prepare_query("SELECT id FROM newestusers WHERE age = ? && sex = ? ORDER BY id LIMIT 10,1000", $age, $sex);

			while($line = $db->fetchrow())
				$ids[] = $line['id'];
		}
	}
	$db->prepare_query("DELETE FROM newestusers WHERE id IN (?)", $ids);


//clean up recently update profile list
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
	$db->prepare_query("DELETE FROM newestprofile WHERE id IN (?)", $ids);

//echo __LINE__ . "\n";
//delete old uncached sessions (1 hour)
	$query = "DELETE FROM sessions WHERE cachedlogin='n' && activetime <= '" . (time()-3600) . "'";
	$fastdb->repeatquery($query);

//echo __LINE__ . "\n";
//clear cache
	$fastdb->prepare_query("DELETE FROM cache WHERE time <= ?", (time() - 3600));


//echo __LINE__ . "\n";
//set minScoreTop10Female and minScoreTop10Male
	setTopLists();

//echo __LINE__ . "\n";
//update picgroups
	updatePicGroups();


//update stats[totalusers]
	$db->query("SELECT count(*) FROM users");

	$totalusers = $db->fetchfield();

	$fastdb->query("UPDATE stats SET count = $totalusers WHERE type='users' && var='totalusers'");

//update stats[userswithpics]
	$db->query("SELECT count(*) FROM users WHERE firstpic > 0");

	$userswithpics = $db->fetchfield();

	$fastdb->query("UPDATE stats SET count = $userswithpics WHERE type='users' && var='userswithpics'");


///////////////////////////////////////////////
////////////// start daily stuff //////////////
///////////////////////////////////////////////

	if( $mode == 2 || gmdate("H") == 12 ){ //so about 5am here

//echo __LINE__ . "\n";
//delete old cached sessions (30 days)
		$fastdb->prepare_query("DELETE FROM sessions WHERE cachedlogin='y' && activetime <= ?", time()-86400*30);


//echo __LINE__ . "\n";
//delete old unactivated accounts (1 week)
		$result = $db->prepare_query("SELECT userid FROM users WHERE activated='n' && jointime < ? && activetime < ?", (time()-86400*7), (time()-86400*7));
		while($line = $db->fetchrow($result))
			deleteAccount($line['userid'],"Account not activated");

//update userclusters, agegroups
		updateUserGroups();

//update users/loc
		updateUserLocs();

		if($mode == 1){
			$fastdb->backup("$sitebasedir/backup/fastdb");
			$fastdb->optimize();
		}

//set firstpic for everyone
		$db->query("UPDATE users LEFT JOIN pics ON users.userid=pics.itemid && pics.priority=1 SET users.firstpic = pics.id");

//set age/sex for all pics
		$db->query("UPDATE pics,users SET pics.age=users.age, pics.sex=users.sex WHERE pics.itemid=users.userid");

//clear profile hit history after a week
		$db->prepare_query("DELETE FROM profileviews WHERE time <= ?", $newtime - 86400*7);

//echo __LINE__ . "\n";
//clear vote history
		$db->prepare_query("DELETE FROM votehist WHERE time <= ?", $newtime-$config['voteHistLength']);

//echo __LINE__ . "\n";
//clear vote history
		$db->prepare_query("DELETE FROM pollvotes WHERE time <= ?", $newtime-$config['voteHistLength']);

//echo __LINE__ . "\n";
//delete old forum reads (14 days)
		$db->prepare_query("DELETE FROM forumread WHERE readtime <= ?", $newtime-86400*14);

//delete gallery pics associated with deleted galleries
		$db->query("DELETE gallery FROM gallery LEFT JOIN gallerycats ON gallery.category = gallerycats.id WHERE gallerycats.id IS NULL");


//echo __LINE__ . "\n";
///////////delete old messages////////////

		$db->prepare_query("DELETE FROM msgheader WHERE date <= ?", $newtime - 86400*35);

		$db->prepare_query("DELETE msgs FROM msgs, msgheader WHERE msgs.msgheaderid = msgheader.id && date <= ? && folder IN (?)", $newtime - 86400*21, array(MSG_INBOX, MSG_SENT));
		$db->prepare_query("DELETE msgs FROM msgs, msgheader WHERE msgs.msgheaderid = msgheader.id && date <= ? && folder = ?", $newtime - 86400, MSG_TRASH);

		$db->query("DELETE msgheader FROM msgheader LEFT JOIN msgs ON msgs.msgheaderid = msgheader.id WHERE msgs.msgheaderid IS NULL"); //deleted by both users

		$db->query("DELETE msgs FROM msgs LEFT JOIN msgheader ON msgs.msgheaderid = msgheader.id WHERE msgheader.id IS NULL");
		$db->query("DELETE msgtext FROM msgtext LEFT JOIN msgheader ON msgtext.id = msgheader.msgtextid WHERE msgheader.msgtextid IS NULL");


//echo __LINE__ . "\n";
//delete old comments (3 months)
		$db->prepare_query("DELETE FROM comments WHERE time <= ?", $newtime-86400*90 );
		$db->query("DELETE commentstext FROM commentstext LEFT JOIN comments ON comments.id=commentstext.id WHERE comments.id IS NULL"); //untested

//delete old user comments (1 month)
		$db->prepare_query("DELETE FROM usercomments WHERE time <= ?", $newtime-86400*30 );
		$db->query("DELETE usercommentstext FROM usercommentstext LEFT JOIN usercomments ON usercomments.id=usercommentstext.id WHERE usercomments.id IS NULL"); //untested


//prune the forums (inactive forums: 4 weeks, inactive threads: 7*sorttime)
		pruneforums();

//remove expired bans
		$db->prepare_query("DELETE FROM forummute WHERE unmutetime <= ?", $newtime );


//add moditems for all pending pics that got lost
	$db->query("INSERT IGNORE INTO moditems (itemid,type) SELECT picspending.id," . MOD_PICS . " FROM picspending LEFT JOIN moditems ON picspending.id=moditems.itemid && moditems.type = " . MOD_PICS . " WHERE moditems.id IS NULL");

//add moditems for all pending forumranks that got lost
	$db->query("INSERT IGNORE INTO moditems (itemid,type) SELECT forumrankspending.id," . MOD_FORUMRANK . " FROM forumrankspending LEFT JOIN moditems ON forumrankspending.id=moditems.itemid && moditems.type = " . MOD_FORUMRANK . " WHERE moditems.id IS NULL");

//echo __LINE__ . "\n";
//delete bad moditems
		$db->query("DELETE FROM moditems WHERE points < -5");

		$deleted = $db->affectedrows();
		if($deleted > 0)
			trigger_error("Deleted $deleted bad rows from the moditems table", E_USER_NOTICE);

//delete old mod votes log items
		$db->prepare_query("DELETE FROM modvoteslog WHERE time <= ?", $newtime-86400*30 );

//recalculate all vote totals/scores. Get rid of scores > 10
		$db->query("UPDATE pics SET votes = v1+v2+v3+v4+v5+v6+v7+v8+v9+v10, score = (v1 + 2*v2 + 3*v3 + 4*v4 + 5*v5 + 6*v6 + 7*v7 + 8*v8 + 9*v9 + 10*v10)/votes");


//echo __LINE__ . "\n";
		deleteUserCleanup();

//echo __LINE__ . "\n";

//backup db and files

		if($mode == 1){
			$db->insertdb->backup("$sitebasedir/backup/db");
			$db->insertdb->optimize();
		}

//		$db->selectdb->optimize();


//end daily
	}

	if(isset($disp))
		echo "done\n";
}


