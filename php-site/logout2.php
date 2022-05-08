<?	

	require_once("include/general.lib.php");

	if(isset($userid) && isset($key))
		destroySession($userid,$key);
	else
		header("location: /");

	if(!isset($count))
		$count = 2;

	if($count<=0)
		header("location: /");
	else
		header("location: logout2.php?count=" . (count-1));
	exit;
