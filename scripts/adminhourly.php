#!/usr/local/php/bin/php
<?
/* MODULE adminhourly.php */

/* SYNOPSIS
 * This module is designed to be ran from crontab atleast once hourly, it will run the hourly tasks upon every
 * non-interactive execution, it will also in addition at 4:00am run the hourly tasks, as well as nightly tasks
 * including optimization of the database and backups.
 *
 * This module may also be ran in interactive mode where you may on the command line specify the functions you wish
 * it to perform in a run.
 * END SYNOPSIS */

/* : grab command line args and script name/directory */
$scriptName = array_shift($argv);
$scriptDirectory = dirname($scriptName);

/* : okay first we need to define which flags this file has for running interactively */
$intFlags = array( "hourly",
                   "daily",
                   "truncate_stats",
                   "optimize",
                   );

/* : next we need to check the number of command line params and decide if we're running interactively or not */
/* : as well as validate the current command line options */
if (count($argv) > 0) {
	$interactive = true;
	foreach ($argv as $argument) {
		if(!in_array($argument, $intFlags)){
			usage();
			exit;
		}
	}
} else {
	$interactive = false;
}

/* : set script configuration options to keep functions in general.lib.php happy */
set_time_limit(0);
$forceserver=true;
$errorLogging=true;


/* : okay we need to change the working directory of this script to public_html reletive to the path*/
/* : of the script */
$_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'] = ".www.nexopia.com";
/*
$_SERVER['SCRIPT_NAME'] = $scriptName;
if (!file_exists(getcwd()."/".basename($scriptName))) {
	$newCwd = $scriptDirectory.'/public_html';
} else {
	$newCwd = 'public_html';
}
$newCwdPart = split("/", $newCwd);
foreach ($newCwdPart as $pathComponant) {
	chdir ($pathComponant);
}
*/

/* : include external deps */
require_once("include/general.lib.php");

/* : okay first deal with running in interactive mode */
if ($interactive) {
	echo "==> Starting the script in interactive mode\n";
	/* : start the timer */
	$timer = new timer();
	$timer->start("script start - " . gmdate("F j, g:i a T"));

	/* : run each specified command */
	foreach ($argv as $command)
		$command();

	/* : stop the timer */
	echo $timer->lap("done - " . gmdate("F j, g:i a T") );
	echo $timer->stop();
	echo "==> Done!\n";

}else{
/* : okay now deal with running in non-interactive mode */
	echo "==> Starting script in non-interactive mode\n";

	/* : start the timer */
	$timer = new timer();
	$timer->start("script start - " . gmdate("F j, g:i a T"));

	/* : start processing stuff that should be processed on every script run */
	hourly();

	/* : process nightly stats here (at about 4:00am) */
	if (gmdate("H") == 11) {
		/* : perform daily tasks */
		daily();

		/* : optimize the database */
		optimize();
	}

	echo $timer->lap("done - " . gmdate("F j, g:i a T") );
	echo $timer->stop();
	echo "==> Done!\n";
}


/* FUNCTION hourly */

/* SYNOPSIS
 * This function is called every time the script is ran, everything in here should be setup so its good to be ran
 * once an hour
 * END SYNOPSIS */

/* HISTORY
 * IncrDev May 26, 2006 by pdrapeau
 * END HISTORY */
function hourly () {
	/* : BEGIN GLOBALS */
	global $db, $mods, $debuginfousers, $usernotify, $usersdb, $timer, $dbs;
	/* : END GLOBALS */

	$newtime=gmmktime(gmdate("H"),0,0,gmdate("n"),gmdate("j"),gmdate("Y"));
	$userdbInstances = $usersdb->getSplitDBs();
	$timer->lap("start admin hourly - " . gmdate("F j, g:i a T"));


	foreach($dbs as $slowdb)
		$slowdb->setslowtime(6000000);	//600 secs


	echo $timer->lap("add hourly stats");
	getStats();

	echo $timer->lap("clean up newest users list");
	$sexes = array("Male","Female");
	$ages = range(14,60);
	$ids = array();
	foreach($ages as $age){
		foreach($sexes as $sex){
			$result = $db->prepare_query("SELECT userid FROM newestusers WHERE age = ? && sex = ? ORDER BY userid DESC LIMIT 10, 1000", $age, $sex);
			while($line = $result->fetchrow())
				$ids[] = $line['userid'];
		}
	}

	if(count($ids))
		$db->prepare_query("DELETE FROM newestusers WHERE userid IN (?)", $ids);


	echo $timer->lap("clean up recently update profile list");
	$sexes = array("Male","Female");
	$ages = range(14,60);
	$ids = array();
	foreach($ages as $age){
		foreach($sexes as $sex){
			$result = $db->prepare_query("SELECT userid FROM newestprofile WHERE age = ? && sex = ? ORDER BY userid LIMIT 10,10000", $age, $sex);
			while($line = $result->fetchrow())
				$ids[] = $line['userid'];
		}
	}
	if(count($ids))
		$db->prepare_query("DELETE FROM newestprofile WHERE userid IN (?)", $ids);


	echo $timer->lap("delete old uncached sessions (1 hour)");
	$usersdb->prepare_query("DELETE FROM sessions WHERE cachedlogin='n' && activetime <= #", $newtime-3600);


	echo $timer->lap("delete old spotlight history (2 hours)");
	$db->prepare_query("DELETE FROM spotlighthist WHERE time < #", $newtime - 3600*2);

	echo $timer->lap("do mod promotions");
	$mods->doPromotions($debuginfousers[0]);

	echo $timer->lap("triggering user notifications");
	$usernotify->triggerNotifications();
}
/* END FUNCTION hourly */


/* FUNCTION daily */

/* SYNOPSIS
 * This function is called typically once a night at about 4:00am in the morning
 * all stuff in here should expect to be ran atleast once nightly
 * END SYNOPSIS */

/* HISTORY
 * IncrDev May 26, 2006 by pdrapeau
 * END HISTORY */
function daily () {
	/* : BEGIN GLOBALS */
	global $config, $db, $mods, $debuginfousers, $usernotify, $messaging, $polls;
	global $usersdb, $forums, $galleries, $articlesdb, $timer, $usersdb, $filesystem;
	global $useraccounts, $usercomments, $archive;
	/* : END GLOBALS */

	$newtime=gmmktime(gmdate("H"),0,0,gmdate("n"),gmdate("j"),gmdate("Y"));

	$dbs = $usersdb->getSplitDBs();

	echo $timer->lap("start admin daily - " . gmdate("F j, g:i a T") );


	echo $timer->lap("potentially create next months archive table");
	$archive->createTable();

	echo $timer->lap("remind about almost expired plus");
	remindAlmostExpiredPlus();

	echo $timer->lap("rebuild user stats");
	rebuildStats();

	echo $timer->lap("dump active account stats");
	dumpActiveAccountStats();

	echo $timer->lap("delete old cached sessions (30 days)");
	$usersdb->prepare_query("DELETE FROM sessions WHERE cachedlogin='y' && activetime <= ?", $newtime-86400*30);


	echo $timer->lap("Prune filesystem fileupdates table");
	$filesystem->prunedb();


	echo $timer->lap("delete old unactivated accounts (1 week)");

	$results = $usersdb->prepare_query("SELECT userid FROM users WHERE state='new' && jointime < ? && activetime < ?", ($newtime-86400*7), ($newtime-86400*7));
	$uids = array();

	while($line = $results->fetchrow()) {
		$uids[] = $line['userid'];
	}

	// delete unactivated accounts
	$useraccounts->delete($uids,"Account not activated");


	echo $timer->lap("update spotlight list");
	updateSpotlightList();

	echo $timer->lap("update everyones ages based on b-day");
	updateUserBirthdays();

	echo $timer->lap("update all the search indexes");
	updateUserIndexes();

	echo $timer->lap("clear profile hit history after a week");
	$usersdb->repeatquery($usersdb->prepare("DELETE FROM profileviews WHERE time <= ?", $newtime - 86400*7));


	echo $timer->lap("clear poll vote history");
	$polls->db->prepare_query("DELETE FROM pollvotes WHERE time <= #", $newtime - 86400*90);

	echo $timer->lap("delete pending gallery pics");
	$galleries->clearpending();


	echo $timer->lap("delete old article comments (3 months)");
	$articlesdb->prepare_query("DELETE FROM comments WHERE time <= ?", $newtime-86400*90 );
	$articlesdb->query("DELETE commentstext FROM commentstext LEFT JOIN comments ON comments.id=commentstext.id WHERE comments.id IS NULL"); //untested

	echo $timer->lap("prune the forums (inactive forums: 4 weeks, inactive threads: 7*sorttime)");
	$forums->pruneforums();

	echo $timer->lap("delete old forum reads (14 days)");
	$forums->db->repeatquery($forums->db->prepare("DELETE FROM forumread WHERE readtime <= ?", $newtime-86400*14));

	echo $timer->lap("delete old blog notifications (14 days)");
	$usersdb->prepare_query("DELETE FROM blogcommentsunread WHERE time <= ?", $newtime - 86400*14);


	echo $timer->lap("delete bad moditems");
	$mods->db->query("DELETE FROM moditems WHERE points < -6");

	$deleted = $mods->db->affectedrows();
	if($deleted > 0)
		trigger_error("Deleted $deleted bad rows from the moditems table", E_USER_NOTICE);


	echo $timer->lap("fix missing pending pic moditems");
	$mods->db->prepare_query("SELECT MIN(itemid) FROM moditems WHERE type = ?", MOD_PICS);
	$id = $mods->db->fetchfield();

	$result = $usersdb->prepare_query("SELECT id FROM picspending WHERE id < ?", $id);

	while($line = $result->fetchrow())
	$mods->db->prepare_query("INSERT IGNORE INTO moditems SET itemid = ?, type = ?", $line['id'], MOD_PICS);


	echo $timer->lap("delete old mod votes log items");
	$mods->db->prepare_query("DELETE FROM modvoteslog WHERE time <= ?", $newtime-86400*30 );


	echo $timer->lap("dump mod stats");
	$mods->dumpModStats();

	echo $timer->lap("delete old login log items");
	$usersdb->prepare_query("DELETE FROM loginlog WHERE time <= #",  $newtime-86400*365);


	echo $timer->lap("delete old messages");
	$messaging->prune();

	echo $timer->lap("delete old user comments (1 month)");
	$usercomments->prune();

}
/* END FUNCTION daily */


/* FUNCTION optimize */

/* SYNOPSIS
 * This function is called when we wish to run database optimizations
 * once an hour
 * END SYNOPSIS */

/* HISTORY
 * IncrDev May 26, 2006 by pdrapeau
 * END HISTORY */
function optimize () {
	/* : BEGIN GLOBALS */
	global $config, $dbs, $db, $statsdb, $disp, $sitebasedir, $mods, $banner, $debuginfousers;
	global $usernotify, $messaging, $usercomments, $polls, $usersdb, $forums, $galleries, $logdb;
	global $timer;
	/* : END GLOBALS */

	echo $timer->lap("start daily optimizations - " . gmdate("F j, g:i a T") );

	/* : optimize/analyze dbs */
	foreach($dbs as $name => $optdb) {
		if (gmdate("w") == 0 || gmdate("w") == 3) {
			echo $timer->lap("optimize db: $name");
			$dbs[$name]->optimize(0, array('archive'));
		} else {
			echo $timer->lap("analyze db: $name");
			$dbs[$name]->analyze(0, array('archive'));
		}
	}
}
/* END FUNCTION optimize */


/* FUNCTION usage */

/* SYNOPSIS
 * This function prints out the usage for the script
 * END SYNOPSIS */

/* HISTORY
 * IncrDev May 26, 2006 by pdrapeau
 * END HISTORY */
function usage () {
	/* : BEGIN GLOBALS */
	global $scriptName, $intFlags;
	/* : END GLOBALS */

	echo "Usage: $scriptName <function> <function> <function> ...\n";
	echo "Runs in non-interactive mode (to be ran hourly) when no function is specified\n";
	echo "otherwise functions will be ran in the order in which they are given\n";
	echo "\n";
	echo "Functions are as follows: \n";
	foreach ($intFlags as $flag) {
		echo "  $flag\n";
	}

}
/* END FUNCTION usage */


/* FUNCTION truncate_stats */

/* SYNOPSIS
 * This function truncates all the stats tables, and than calls the daily portion of the script
 * to force a re-build of everything
 * END SYNOPSIS */

/* HISTORY
 * IncrDev May 26, 2006 by pdrapeau
 * END HISTORY */
function truncate_stats () {
	/* : BEGIN GLOBALS */
	global $usersdb, $masterdb;
	/* : END GLOBALS */

	// truncate all stats tables
	$usersdb->prepare_query("TRUNCATE intereststats");
	$usersdb->prepare_query("TRUNCATE locstats");
	$usersdb->prepare_query("TRUNCATE agesexgroups");

	$masterdb->prepare_query("TRUNCATE masterintereststats");
	$masterdb->prepare_query("TRUNCATE masterlocstats");
	$masterdb->prepare_query("TRUNCATE masteragesexgroups");
	daily();
}
/* END FUNCTION truncate_stats */

/* END MODULE adminhourly.php */

