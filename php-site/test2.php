<?

	$login=1;

	require_once("include/general.lib.php");

	set_time_limit(0);

	$num=150;
	$time = time() - $num;
	$tid=47;
	$fid=2;

	$query = "SELECT userid,username FROM users";
	$result = mysql_query($query);

	$users = array();
	while($line = mysql_fetch_assoc($result))
		$users[] = $line;



	for($i=0;$i<$num;$i++){
		$userData = $users[rand(0,count($users)-1)];
		$query = "UPDATE forumthreads SET posts = posts+1, time='$time', lastauthor='$userData[username]',lastauthorid='$userData[userid]' WHERE id='$tid'";
		mysql_query($query) or die("post failed");

		$query = "UPDATE forums SET posts = posts+1,time='$time' WHERE id='$fid'";
		mysql_query($query) or die("post failed");

		$query = "UPDATE users SET posts = posts+1 WHERE userid='$userData[userid]'";
		mysql_query($query) or die("post failed");

		$query = "INSERT INTO forumposts SET threadid='$tid', authorid='$userData[userid]', author='$userData[username]', msg='post $i', nmsg='post $i', time='" . ($time+$i) . "'";
		mysql_query($query) or die("post failed");
	}
