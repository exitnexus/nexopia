<?

	$forceserver = true;

	require_once("include/general.lib.php");


	$numposts = $db->nextAuto("forumposts");
	$db->query("SELECT count(*) FROM users");
	$numusers = $db->fetchfield();

	echo "Users: $numusers<br>\nPosts: $numposts";
