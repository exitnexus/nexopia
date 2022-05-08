<?

	$login = 1;

	require_once('include/general.lib.php');

	//track clicks to this page through a banner of type link.
	$banner->click(1217, "z2h");

	header("Location: http://live.zeros2heroes.com/z2h/nexopia_welcome.php?user_id=$userData[userid]");
