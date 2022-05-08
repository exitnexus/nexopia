<?

	$login=0;

	require_once("include/general.lib.php");

	addRefreshHeaders();


	if(!($fid = getREQval('fid', 'int')))
		die("Bad Forum id");

	$perms = $forums->getForumPerms($fid);	//checks it's a forum, not a realm, users permissions here, and column info

	if(!$perms['view'])
		die("You don't have permission to view this forum");

	$forumdata = $perms['cols'];


	incHeader();

	echo "<table>";

	echo "<tr><td class=header align=center>Global Rules</td></tr>";
	echo "<tr><td class=body>";
	echo getStaticValue('forumrules'); //needs to accept named ids
	echo "</td></tr>";


	echo "<tr><td class=header align=center>Global Rules</td></tr>";
	echo "<tr><td class=body>";
	echo $forumdata['rules']; //needs to be run through removeHTML, nl2br, etc
	echo "</td></tr>";

	incFooter();
