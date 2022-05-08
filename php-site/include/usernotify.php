<?php

class usernotify
{
	var $db;
	
	function usernotify(&$db)
	{
		$this->db = &$db;
	}
	function listNotifications($userid = false, $pagenum = false)
	{
		global $userData, $pagenum, $config;
		
		if ($userid === false)
			$userid = $userData['userid'];
		
		$queryLimit = "";
		if ($pagenum === false)
			$queryLimit .= " LIMIT " . $pagenum*$config['linesPerPage'] . ", " . $config['linesPerPage'];
		
		$result = $this->db->prepare_query("SELECT usernotifyid, creatorid, creatorname, createtime, triggertime, subject FROM usernotify WHERE targetid = ? ORDER BY triggertime DESC " . $queryLimit, $userid);
		
		return $this->db->fetchrowset($result);
	}
	
	function newNotify($targetid, $targetname, $triggertime, $subject, $message, $creatorid = false, $creatorname = false)
	{
		global $userData;
		
		if ($creatorid === false || $creatorname === false)
		{
			$creatorid = $userData['userid'];
			$creatorname = $userData['username'];
		}
		
		$this->db->prepare_query(
			'INSERT INTO usernotify SET creatorid = #, creatorname = ?, createtime = #, targetid = #, triggertime = #, subject = ?, message = ?',
			                           $creatorid,    $creatorname,     time(),        $targetid,    $triggertime,    $subject,    $message);
	}
	
	function deleteNotify($usernotifyids)
	{
		$this->db->prepare_query('DELETE FROM usernotify WHERE usernotifyid IN (#)', $usernotifyids);
	}
	
	function triggerNotifications()
	{
		global $messaging;
		
		$currenttime = time();
		
		// fetch notifies with a triggertime in the past
		$result = $this->db->prepare_query('SELECT creatorid, creatorname, targetid, subject, message FROM usernotify WHERE triggertime <= #', $currenttime);
		
		// send them out as messages
		$count = 0;
		while ($triggered = $this->db->fetchrow($result))
		{
			$count++;
			if ($triggered['creatorid'] == $triggered['targetid'])
				$triggered['creatorname'] = 'yourself';
			
			$triggered['subject'] = "Timed Notification from " . $triggered['creatorname'] . ": " . $triggered['subject'];
			$messaging->deliverMsg($triggered['targetid'], $triggered['subject'], $triggered['message'], 0, "Nexopia", 0);
		}
		
		// now remove them from the database
		$this->db->prepare_query('DELETE FROM usernotify WHERE triggertime <= #', $currenttime);
		
		return $count;
	}
}
