<?

	$login=1;

	require_once("include/general.lib.php");

	$query = "SELECT * FROM smilies ORDER BY code";
	$result = $db->query($query);

	echo "<html><head><title>$config[title]</title></head><script src=$config[imgserver]/general.js></script>";
	echo "<link rel=stylesheet href='$skindir/default.css'>";

	echo "<body background='$skindir/smilybg.png' onLoad='init()'>\n";

	echo "<table>";
	openCenter();

	echo "<table align=center width=50%>";
	echo "<tr><td class=header>Picture</td><td class=header>Code</td></tr>";
	while($line = $db->fetchrow($result)){
		echo "<tr><td class=body><a href=\"javascript:opener.emoticon('$line[code]'); window.focus()\"><img src=\"$config[smilyloc]$line[pic].gif\" alt=\"$line[code]\" border=0></a></td><td class=body><font face=courier>$line[code]</font></td></tr>";
	}
	echo "</table>";

	closeCenter();
	echo "</table>";

	echo "</body></html>";
