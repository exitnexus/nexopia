<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isMod($userData['userid']))
		die("Permission denied");

