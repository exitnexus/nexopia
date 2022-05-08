<?

	$login=0;

	require_once("include/general.lib.php");

	if(!($fid = getREQval('fid', 'int')))
		die("Bad Forum id");

	$perms = $forums->getForumPerms($fid);	//checks it's a forum, not a realm, users permissions here, and column info

	if(!$perms['view'])
		die("You don't have permission to view this forum");

	$forumdata = $perms['cols'];
	$template = new template('forums/forumrules');
	$template->set('forumRules', getStaticValue('forumrules'));
	$template->set('specificRules', nl2br(parseHTML(smilies($forumdata['rules']))));
	$template->display();

