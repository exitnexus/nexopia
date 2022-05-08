<?

	$login=1;

	require_once("include/general.lib.php");

	header("Content-Type: text/xml");

	echo "<" . "?xml version=\"1.0\"?" . ">\n";
	echo "<rss version=\"2.0\">\n";
	echo "	<channel>\n";
	echo "		<title>Nexopia Messages</title>\n";
	echo "		<link>http://$wwwdomain/messages.php</link>\n";
	echo "		<pubDate>" . userDate("r") . "</pubDate>\n";

	$items = 0;

	$db->prepare_query("SELECT msgs.id, msgheader.fromname, msgheader.subject, msgheader.date FROM msgs, msgheader WHERE msgs.msgheaderid=msgheader.id && msgs.userid = ? && msgs.folder = ? && msgs.userid = msgheader.to && msgheader.new='y'", $userData['userid'], MSG_INBOX);
	$lines = $db->fetchrowset();

	if(!$lines){
		echo "		<item>\n";
		echo "			<title>No New Messages</title>\n";
		echo "			<link>http://$wwwdomain/messages.php</link>\n";
		echo "		</item>\n";
	}else{
		echo "		<item>\n";
		echo "			<title>" . count($lines) . " New Message(s)</title>\n";
		echo "			<link>http://$wwwdomain/messages.php</link>\n";
		echo "		</item>\n";

		foreach ($lines as $line){
			echo "		<item>\n";
			echo "			<title>" . htmlentities("$line[fromname] - $line[subject]") . "</title>\n";
			echo "			<link>" . htmlentities("http://$wwwdomain/messages.php?action=view&id=$line[id]") . "</link>\n";
			echo "		</item>\n";
		}
	}

	echo "		<item>\n";
	echo "			<title>-----------------------------</title>\n";
	echo "			<link>http://$wwwdomain/</link>\n";
	echo "		</item>\n";

	$db->prepare_query("SELECT forumthreads.id,forumthreads.title FROM forumread,forumthreads WHERE forumread.subscribe='y' && forumread.userid = ? && forumread.threadid=forumthreads.id && forumread.time < forumthreads.time", $userData['userid']);
	$lines = $db->fetchrowset();

	if(!$lines){
		echo "		<item>\n";
		echo "			<title>No Subscriptions</title>\n";
		echo "			<link>http://$wwwdomain/managesubscriptions.php</link>\n";
		echo "		</item>\n";
	}else{
		echo "		<item>\n";
		echo "			<title>" . count($lines) . " Subscriptions</title>\n";
		echo "			<link>http://$wwwdomain/managesubscriptions.php</link>\n";
		echo "		</item>\n";

		foreach ($lines as $line){
			echo "		<item>\n";
			echo "			<title>" . htmlentities("$line[title]") . "</title>\n";
			echo "			<link>" . htmlentities("http://$wwwdomain/forumviewthread.php?tid=$line[id]") . "</link>\n";
			echo "		</item>\n";
		}
	}

	echo "	</channel>\n";
	echo "</rss>";

