<?

	$forceserver = true;

	require_once("include/general.lib.php");
	updateStats();

	$numposts = $forums->db->nextAuto("forumposts");

	$numusers = $siteStats['userstotal'];

	echo "Users: $numusers<br>\nPosts: $numposts";

