<?

	$forceserver = true;

	require_once("include/general.lib.php");


	$numposts = $forums->db->nextAuto("forumposts");

	$res = $usersdb->query("SELECT count(*) FROM users");
	$numusers = $res->fetchfield();

	echo "Users: $numusers<br>\nPosts: $numposts";

