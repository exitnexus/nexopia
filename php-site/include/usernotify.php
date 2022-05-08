<?php

class usernotify
{
	public $db;

	function __construct(&$db)
	{
		$this->db = &$db;
	}
	function listNotifications($userid = false, $pagenum = false)
	{
		global $userData, $config;

		if ($userid === false)
			$userid = $userData['userid'];

		$queryLimit = "";
		if ($pagenum === false)
			$queryLimit .= " LIMIT " . $pagenum*$config['linesPerPage'] . ", " . $config['linesPerPage'];

		$result = $this->db->prepare_query("SELECT usernotifyid, creatorid, createtime, triggertime, subject FROM usernotify WHERE targetid = # ORDER BY triggertime DESC " . $queryLimit, $userid);

		$rows = $result->fetchrowset();
		
		$uids = array();
		foreach($rows as $row)
			$uids[$row['creatorid']] = $row['creatorid'];
		
		$usernames = getUserName($uids);
		
		foreach($rows as $k => $v)
			$rows[$k]['creatorname'] = $usernames[$v['creatorid']];

		return $rows;
	}

	function getNotification($notifyid)
	{
		$result = $this->db->prepare_query("SELECT * FROM usernotify WHERE usernotifyid = #", $notifyid);
		
		$row = $result->fetchrow();
		
		$row['creatorname'] = getUserName($row['creatorid']);
		
		return $row;
	}

	function newNotify($targetid, $triggertime, $subject, $message, $creatorid = false)
	{
		global $userData;

		if ($creatorid === false)
			$creatorid = $userData['userid'];

		$this->db->prepare_query(
			'INSERT INTO usernotify SET creatorid = #, createtime = #, targetid = #, triggertime = #, subject = ?, message = ?',
			                           $creatorid,     time(),        $targetid,    $triggertime,    $subject,    $message);
		return $this->db->affectedrows();
	}

	function deleteNotify($usernotifyids)
	{
		if ($usernotifyids)
		{
			$this->db->prepare_query('DELETE FROM usernotify WHERE usernotifyid IN (#)', $usernotifyids);
			return $this->db->affectedrows();
		} else {
			return 0;
		}
	}

	function triggerNotifications()
	{
		global $messaging;

		$currenttime = time();

		// fetch notifies with a triggertime in the past
		$result = $this->db->prepare_query('SELECT creatorid, targetid, subject, message FROM usernotify WHERE triggertime <= #', $currenttime);

		// send them out as messages
		$count = 0;
		while ($triggered = $result->fetchrow())
		{
			$count++;
			if ($triggered['creatorid'] == $triggered['targetid'])
				$triggered['creatorname'] = 'yourself';
			else
				$triggered['creatorname'] = getUserName($triggered['creatorid']);

			$triggered['subject'] = "Timed Notification from " . $triggered['creatorname'] . ": " . $triggered['subject'];
			$messaging->deliverMsg($triggered['targetid'], $triggered['subject'], $triggered['message'], 0, "Nexopia", 0);
		}

		// now remove them from the database
		$this->db->prepare_query('DELETE FROM usernotify WHERE triggertime <= #', $currenttime);

		return $count;
	}
}
