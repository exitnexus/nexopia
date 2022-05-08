<?php

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'])) // question: special admin priv?
		die("Permission denied");

	switch ($action)
	{
	case "view":
		$notifyid = getREQval('id');
		viewNotification($notifyid);
		break;
	case "delete":
	case "Delete":
		$notifyids = getREQval('checkIDs', 'array');
		deleteNotifications($notifyids);
		break;
	case "new":
		newNotification();
		break;
	case "Create":
		createNotification();
		break;
	}
	listNotifications();
	
	function listNotifications()
	{
		global $userData, $usernotify;
		
		$notifications = $usernotify->listNotifications($userData['userid']);
		
		incHeader();

		echo "<table width=100%>\n";
		echo "<tr><td class=body colspan=5><a class=body href=$_SERVER[PHP_SELF]?action=new>New Notification</a></td></tr>";
		echo "<tr><td class=header colspan=5><table width=100% cellspacing=0 cellpadding=0><tr>";
		
		echo "    <td class=header>&nbsp;</td>";
		echo "    <td class=header align=right>Page: (todo later)</td>";
		echo "</tr></table></tr>";
		echo "<form action=$_SERVER[PHP_SELF] method=post>";
		echo "<tr>\n";
			echo "<td class=header width=25></td>\n";
			echo "<td class=header width=10%>From</td>\n";
			echo "<td class=header width=50%>Subject</td>\n";
			echo "<td class=header width=20%>Created Time</td>\n";
			echo "<td class=header width=20%>Trigger Time</td>\n";
		echo "</tr>\n";

		$classes = array('body','body2');
		$i=1;

		foreach ($notifications as $line)
		{
			$i = !$i;
			echo "<tr><td class=$classes[$i]><input type=checkbox name=checkID[] value=\"$line[usernotifyid]\"></td>";
			echo "<td class=$classes[$i]><a class=body href=profile.php?uid=$line[creatorid]>$line[creatorname]</td>";
			echo "<td class=$classes[$i]><a class=body href=$_SERVER[PHP_SELF]?action=view&id=$line[usernotifyid]>$line[subject]</a></td>";
			echo "<td class=$classes[$i]>" . userdate("D M j, Y g:i a",$line['createtime']) . "</td>\n";
			echo "<td class=$classes[$i] nowrap>" . userdate("D M j, Y g:i a",$line['triggertime']) . "</td></tr>\n";
		}
		echo "<tr><td class=header colspan=5><table width=100% cellpadding=0 cellspacing=0><tr><td class=header>";
		echo "<input class=body type=submit name=action value=Delete>";

		echo "</td>\n<td class=header></td></form>";

		echo "<td align=right class=header>Page: (todo later)";

		echo "</td></tr></table>";
		echo "</table>\n";

		incFooter();
		exit();
	}
