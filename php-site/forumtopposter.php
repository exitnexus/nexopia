<?

	$login=0;

	require_once("include/general.lib.php");

	$query = "SELECT userid,username,posts FROM users ORDER BY posts DESC LIMIT 20";
	$result = $db->query($query);

	incHeader();

	echo "<table>";
	echo "<tr><td class=header></td><td class=header>Username</td><td class=header>Posts</td></tr>";

	$num=1;
	while($line = $db->fetchrow($result)){
		echo "<tr><td class=body>$num.</td><td class=body><a class=body href=profile.php?uid=$line[userid]>$line[username]</a></td>";
		echo "<td class=body>$line[posts]</td></tr>";
		$num++;
	}
	echo "</table>";

	incFooter();
