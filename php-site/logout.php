<?

	$login=0;

	require_once("include/general.lib.php");

	destroySession($userid,$key);

	$db->prepare_query("UPDATE users SET online = 'n' WHERE userid = ?", $userid);

	header("location: /");


