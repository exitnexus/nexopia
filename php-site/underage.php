<?

/*
underage
-userid
-username
-numtimes
-lastchecked
-confirmed
-deleted
PRIMARY userid
*/

function reportUnderage($userid, $username){
	$db->prepare_query("INSERT IGNORE INTO underage SET userid = ?, username = ?", $userid, $username);

	if($db->affectedrows() == 0)
		$db->prepare_query("UPDATE underage SET numtimes = numtimes + 1 WHERE userid = ?", $userid);
}

function checkUnderage($userid, $confirm){
	$db->prepare_query("UPDATE underage SET lastchecked = ?, confirmed = ? WHERE userid = ?", time(), ($confirm ? 'y' : 'n'), $userid);
}

function deleteUnderage($userid){

	$db->prepare_query("UPDATE underage SET deleted = ? WHERE userid = ?", time(), $userid);

	$result = $db->prepare_query("SELECT friendid, username FROM friends, users WHERE friends.friendid = users.username && friends.userid = ?", $userid);

	while($line = $db->fetchrow($result))
		reportUnderage($line['userid'], $line['username']);
}

function prune(){
	$db->prepare_query("DELETE FROM underage WHERE deleted > 0 && deleted < ?", time() - 86400*7);
}

function listUnderage($confirmed){
	$db->prepare_query("SELECT userid, username,  ");

}
