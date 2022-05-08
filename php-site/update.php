<?

	$forceserver = true;
	$enableCompression = true;
	$login=1;
	$errorLogging = false;
	require_once("include/general.lib.php");

	if(!in_array($userData['userid'], $debuginfousers))
		die("error");


	echo str_repeat(" ",400). "\n";
	set_time_limit(0);
//	ignore_user_abort(true);

$mods->doPromotions($debuginfousers[0]);

/*
//convert from single db to hash balanced db
	$tables = array("iplog" => "ip", "loginlog" => "userid", "userhitlog" => "userid");

	foreach($tables as $table => $keycol){

		$result = $logdb->query("SHOW CREATE TABLE `$table`");
		$create = $logdb->fetchfield(1,0,$result);

		$newlogdb->query(false, $create);

		$logdb->unbuffered_query("SELECT * FROM $table");

		while($line = $logdb->fetchrow()){
			$query = "INSERT INTO $table SET ";

			$parts = array();
			foreach($line as $k => $v)
				$parts[] = $newlogdb->prepare("$k = ?", $v);

			$query .= implode(", ", $parts);

			$newlogdb->query($line[$keycol], $query);
		}
	}
*/

/*
//get my messages
INSERT INTO msgsold.msgs SELECT * FROM nexopia3.msgs WHERE nexopia3.msgs.userid = 1;
//get message headers
INSERT INTO msgsold.msgheader SELECT nexopia3.msgheader.* FROM nexopia3.msgs, nexopia3.msgheader WHERE nexopia3.msgs.msgheaderid = nexopia3.msgheader.id && nexopia3.msgs.userid = 1;
//get other side of my messages
INSERT INTO msgsold.msgs
	SELECT
			othermsgs.*
		FROM
			nexopia3.msgs,
			nexopia3.msgheader,
			nexopia3.msgs as othermsgs
		WHERE
			nexopia3.msgs.userid = 1 &&
			nexopia3.msgs.msgheaderid = nexopia3.msgheader.id &&
			othermsgs.msgheaderid = nexopia3.msgheader.id &&
			othermsgs.userid != 1;



UPDATE msgsold.msgheader SET id1 = 0, id2 = 0, reply1 = 0, reply2 = 0;
TRUNCATE TABLE msgsnew.msgs;
*/

/*
ALTER TABLE `msgsold.msgheader`
	ADD `id1` INT UNSIGNED NOT NULL ,
	ADD `id2` INT UNSIGNED NOT NULL ,
	ADD `reply1` INT UNSIGNED NOT NULL ,
	ADD `reply2` INT UNSIGNED NOT NULL ;


ALTER TABLE `msgtext`
	ADD `date` INT NOT NULL AFTER `id` ,
	ADD `compressed` ENUM( 'n', 'y' ) NOT NULL AFTER `date` ,
	ADD `html` ENUM( 'n', 'y' ) NOT NULL AFTER `compressed` ,
	ADD INDEX ( `date` ) ,
	MAX_ROWS=4294967295 AVG_ROW_LENGTH=50;



UPDATE msgsold.msgheader, msgsold.msgs
	SET msgsold.msgheader.id1 = msgsold.msgs.id
	WHERE msgsold.msgs.msgheaderid = msgsold.msgheader.id && userid = `to`;

UPDATE msgsold.msgheader, msgsold.msgs
	SET msgsold.msgheader.id2 = msgsold.msgs.id
	WHERE msgsold.msgs.msgheaderid = msgsold.msgheader.id && userid = `from`;




UPDATE	msgsold.msgheader as msgheader,
		msgsold.msgheader as replyheader,
		msgsold.msgs as msgs
	SET msgheader.reply1 = msgs.id
	WHERE
		msgheader.replyto = replyheader.id &&
		replyheader.id = msgs.msgheaderid &&
		replyheader.`to` = msgs.userid;

UPDATE	msgsold.msgheader as msgheader,
		msgsold.msgheader as replyheader,
		msgsold.msgs as msgs
	SET msgheader.reply2 = msgs.id
	WHERE
		msgheader.replyto = replyheader.id &&
		replyheader.id = msgs.msgheaderid &&
		replyheader.`from` = msgs.userid;





INSERT INTO msgsnew.msgs (id, userid, folder, otheruserid , `to`, toname, `from`, fromname, date, subject, msgtextid, status, othermsgid, replyto)
	SELECT msgsold.msgs.id, userid, folder, other, `to`, toname, `from`, fromname, date, subject, msgtextid,
		IF(replied = 'y', 'replied', IF(new = 'y', 'new', 'read')),
		IF(userid = `to`, id2, id1),
		IF(userid = `to`, reply2, reply1)
	FROM msgsold.msgs, msgsold.msgheader
	WHERE msgsold.msgs.msgheaderid = msgsold.msgheader.id;


$db->prepare_query("UPDATE msgtext SET date = #, html = 'n', compressed = 'n'", time());




/*
//get table list
	$tables = array();

	foreach($dbs as $dbname => $optdb){
		$tableresult = $dbs[$dbname]->listtables();

		while(list($tname) = $dbs[$dbname]->fetchrow($tableresult, DB_NUM)){

			echo "$dbname.$tname<br>";

			$result = $dbs[$dbname]->query("SHOW CREATE TABLE `$tname`");
			$output = $dbs[$dbname]->fetchfield(1,0,$result);

			$tables[$tname] = $output;
		}
	}

	ksort($tables);

	echo "<pre>";
	echo implode("\n\n------------------------\n\n", $tables);
	echo "</pre>";
*/


	echo "\n<br>\n<br>Update Complete<br>\n";
	$endTime = gettime();
	$dtime = number_format(($endTime - $startTime)/10000,4);
	echo "Run-time $dtime seconds<br>\n";

	outputQueries();

