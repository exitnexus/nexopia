<?php

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'])) // question: special admin priv?
		die("Permission denied");

	switch ($action)
	{
	case "view":
		$notifyid = getREQval('id', 'integer');
		viewNotification($notifyid);
		break;
	case "delete":
	case "Delete":
		$notifyids = getPOSTval('checkID', 'array');
		deleteNotifications($notifyids);
		break;
	case "write":
	case "Preview":
		$to = getPOSTval('to');
		$month = getPOSTval('month', 'integer');
		$day = getPOSTval('day', 'integer');
		$year = getPOSTval('year', 'integer');
		$hour = getPOSTval('hour', 'integer');
		$subject = getPOSTval('subject');
		$msg = getPOSTval('msg');
		writeNotification($to, $subject, $msg, $month, $day, $year, $hour, $action == "Preview");
		break;
	case "Create":
		$to = getPOSTval('to');
		$month = getPOSTval('month', 'integer');
		$day = getPOSTval('day', 'integer');
		$year = getPOSTval('year', 'integer');
		$hour = getPOSTval('hour', 'integer');
		$subject = getPOSTval('subject');
		$msg = getPOSTval('msg');
		createNotification($to, $subject, $msg, $month, $day, $year, $hour);
		break;
	}
	listNotifications();

	function listNotifications()
	{
		global $userData, $usernotify;

		$notifications = $usernotify->listNotifications($userData['userid']);

		incHeader();

		echo "<table width=100%>\n";
		echo "<tr><td class=body colspan=5><a class=body href=$_SERVER[PHP_SELF]?action=write>New Notification</a></td></tr>";
		echo "<tr><td class=header colspan=5><table width=100% cellspacing=0 cellpadding=0><tr>";

		echo "    <td class=header>&nbsp;</td>";
		echo "    <td class=header align=right>&nbsp;</td>";
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
			echo "<td class=$classes[$i]><a class=body href=/users/". urlencode($line["creatorname"]) .">$line[creatorname]</td>";
			echo "<td class=$classes[$i]><a class=body href=$_SERVER[PHP_SELF]?action=view&id=$line[usernotifyid]>$line[subject]</a></td>";
			echo "<td class=$classes[$i]>" . userdate("D M j, Y g:i a",$line['createtime']) . "</td>\n";
			echo "<td class=$classes[$i] nowrap>" . userdate("D M j, Y g:i a",$line['triggertime']) . "</td></tr>\n";
		}
		echo "<tr><td class=header colspan=5><table width=100% cellpadding=0 cellspacing=0><tr><td class=header>";
		echo "<input class=body type=submit name=action value=Delete>";

		echo "</td>\n<td class=header></td></form>";

		echo "<td align=right class=header>&nbsp;";

		echo "</td></tr></table>";
		echo "</table>\n";

		incFooter();
		exit();
	}

	function writeNotification($to="",$subject="",$msg="",$month="", $day = "", $year = "", $hour = "", $preview = false){
		global $msgs, $userData, $sortt, $sortd, $config, $db, $messaging;

		if(is_numeric($to)){
			$to = getUserName($to);
			if(!$to){
				$msgs->addMsg("Invalid User");
				$to="";
			}
		}

		for($i=1;$i<=12;$i++)
			$months[$i] = gmdate("F", gmmktime(0,0,0,$i,1,0));

		if (empty($month))
			$month = userdate('n');
		if (empty($day))
			$day = userdate('j');
		if (empty($year))
			$year = userdate('Y');
		if (empty($hour))
			$hour = userdate('G');

		$friends = getFriendsList($userData['userid']);

		$nmsg = cleanHTML(trim($msg));

		incHeader();

		echo "<table align=center>";

		if($preview){
			$nsubject = trim(removeHTML($subject));

			$nmsg2 = parseHTML($nmsg);
			$nmsg3 = smilies($nmsg2);

			echo "<tr><td colspan=2 class=body>";

			echo "Here is a preview of what the message will look like:";

			echo "<table width=100%>";
			echo "<tr><td class=header>Subject:</td><td class=header>$nsubject</td></tr>";
			echo "<tr><td class=body colspan=2>" . $nmsg3 . "</td></tr>";

			echo "</table><hr>";
			echo "</td></tr>";
		}
		echo "</table>";

		echo "<form action=$_SERVER[PHP_SELF] method=post name=editbox>\n";
		echo "<table align=center>";
	//	echo "<tr><td class=body colspan=2 align=center><b>Please do not share personal or financial information while using Nexopia.com.<br>All information passed through the use of this site, is at the risk of the user.<br>Nexopia.com will assume no liability for any users' actions.</b></td></tr>";
		echo "<tr><td class=body>To: </td><td class=body><input class=body type=text name=to value=\"$to\" style=\"width:120\"><select name=friends style=\"width:120\" class=body onChange=\"if(this.selectedIndex!=0) this.form.to.value=this.options[this.selectedIndex].value;this.selectedIndex=0\"><option>Choose a Friend" . make_select_list($friends) . "</select></td></tr>\n";
		echo "<tr><td class=body>Subject: </td><td class=body><input class=body type=text name=\"subject\" value=\"" . htmlentities($subject) . "\" style=\"width:300\" maxlength=64></td></tr>\n";
		echo "<tr><td class=body>When to Send: </td><td class=body>";
			echo "<select class=body name=\"month\"><option value=0>Month" . make_select_list_key($months,$month) . "</select>";
			echo "<select class=body name=\"day\"><option value=0>Day" . make_select_list(range(1,31),$day) . "</select>";
			echo "<select class=body name=\"year\"><option value=0>Year" . make_select_list(range(gmdate("Y"),gmdate("Y")+5),$year) . "</select>\n";
			echo "<select class=body name=hour><option value=0>Hour" . make_select_list(range(0, 23), $hour) . "</select></td></tr>\n";
		echo "</td></tr>\n";
		echo "<tr><td class=body colspan=2>";

		editbox($msg);

		echo "</td></tr>\n";
		echo "<tr><td class=body colspan=2 align=center><input class=body type=submit name=action value=Preview> <input class=body type=submit name=action accesskey='s' value=\"Create\"></td></tr>\n";

		echo "</table></form>";

		incFooter();
		exit();
	}

	function createNotification($to, $subject, $msg, $month, $day, $year, $hour)
	{
		global $msgs;
		global $usernotify;

		if(!is_numeric($to))
		{
			$to = getUserID($to);
			if(!$to){
				$msgs->addMsg("Invalid User: $to");
				writeNotification("", $subject, $msg, $month, $day, $year);
			}
		} else {
			$toname = getUserName($to);
			if (!$toname)
			{
				$msgs->addMsg("Invalid User: $to");
				writeNotification("", $subject, $msg, $month, $day, $year);
			}
		}

		if (blank($to, $subject, $msg, $month, $day, $year))
		{
			$msgs->addMsg("You did not specify enough information.");
			writeNotification($to, $subject, $msg, $month, $day, $year);
		}
		$timestamp = usermktime($hour, 0, 0, $month, $day, $year);

		$spam = spamfilter(trim($msg));

		if(!$spam)
		{
			writeNotification($to, $subject, $msg, $month, $day, $year);
		}
		if (!$usernotify->newNotify($to, $timestamp, $subject, $msg))
		{
			$msgs->addMsg("Could not set notification");
			writeNotification($to, $subject, $msg, $month, $day, $year);
		}

		header("location: $_SERVER[PHP_SELF]");
		exit;
	}

	function deleteNotifications($deleteids)
	{
		global $usernotify, $msgs;
		$deleted = $usernotify->deleteNotify($deleteids);
		$msgs->addMsg("Deleted $deleted notifications");
	}

	function viewNotification($notifyid)
	{
		global $usernotify, $msgs, $userData;

		$notification = $usernotify->getNotification($notifyid);
		if (!$notification)
		{
			$msgs->addMsg("Failed to load notification.");
			listNotifications();
		}
		if ($notification['creatorid'] != $userData['userid'] &&
		    $notification['targetid']  != $userData['userid'])
		{
			$msgs->addMsg("Failed to load notification.");
			listNotifications();
		}

		incHeader();

		echo "<table width=100%>\n";
		echo "<tr><td class=body colspan=2><a class=body href=\"$_SERVER[PHP_SELF]\">Notification list</a></td></tr>";
		echo "<tr><td class=header>From:</td><td class=header><a class=header href=/users/". urlencode($notification["creatorname"])  .">$notification[creatorname]</a></td></tr>";
		echo "<tr><td class=header>Created:</td><td class=header>" . userdate("D M j, Y g:i a", $notification['createtime']) . "</td></tr>";
		echo "<tr><td class=header>Trigger on:</td><td class=header>" . userdate("D M j, Y g:i a", $notification['triggertime']) . "</td></tr>";
		echo "<tr><td class=header>Subject:</td><td class=header>$notification[subject]</td></tr>";
		echo "<tr><td class=body valign=top colspan=2>" . parseHTML(smilies($notification['message'])) . "<br><br></td></tr>";
		echo "</table>";

		incFooter();
		exit();
	}
