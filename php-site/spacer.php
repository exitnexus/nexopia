<?

	$login = 1;
	$simplepage = 2;
	$simpleauth = true;
	$forceserver = true;
	$accepttype = false;

	require_once("include/general.lib.php");

	$uid = getREQval('uid', 'int');
	$anon = getREQval('anon', 'int');
	$time = getREQval('t', 'int');
	$k = getREQval('k');

	$curtime = time();

//time in the last 60 seconds, and has the right key
	if($time <= $curtime && $time+60 > $curtime && checkKey("$uid:secret:$anon:$time", $k)){
		$usersdb->prepare_query("INSERT IGNORE INTO profileviews SET hits = 1, time = #, userid = %, viewuserid = #, anonymous = #", time(), $uid, $userData['userid'], $anon);

		if($usersdb->affectedrows()){
			$usersdb->prepare_query("UPDATE profile SET views = views + 1 WHERE userid = %", $uid);
			$cache->incr("profileviews-$uid");
		}else{
			$usersdb->prepare_query("UPDATE profileviews SET hits = hits + 1, time = #, anonymous = # WHERE userid = % && viewuserid = #", time(), $anon, $uid, $userData['userid']);
		}
	}
