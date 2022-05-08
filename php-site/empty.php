<?

	$login=0;

	require_once("include/general.lib.php");

	incHeader();//false,array('incLoginBlock','incTextAdBlock','incSortBlock','incTopGirls','incTopGuys','incNewestMembersBlock'));

	echo "Hello World! (test again and again and again and again)";

	$template = new Template('moderate/forumBans');
	$template->dump();

	incFooter();//array('incSideAdBlock','incPollBlock','incActiveForumBlock'));

