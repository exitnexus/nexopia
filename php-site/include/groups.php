<?php

class group extends databaseobject
{
	// the fields this object deals with by default are:
	// id, rootid, parentid, userid, time, deleted, and msg.
	// any other fields needed can be passed in through extrafields.
	function __construct($groupdb)
	{
		$fields = array(
			'id' => "!",
			'location' => "#",
			'type' => "#",
			'name' => "?",
		);
		parent::__construct($groupdb, 'groups', DB_AREA_GROUPS, "group-", $fields);
	}
}


class groupmember extends databaseobject
{
	private $groupdb;
	private $userdb;
	private $group;
	
	// the fields this object deals with by default are:
	// id, rootid, parentid, userid, time, deleted, and msg.
	// any other fields needed can be passed in through extrafields.
	function __construct($groupdb, $userdb)
	{
		$this->groupdb = $groupdb;
		$this->userdb = $userdb;
		
		$fields = array(
			'userid' => "!",
			'groupid' => "!",
			'frommonth' => "#",
			'fromyear' => "#",
			'tomonth' => "#",
			'toyear' => "#",
			'visibility' => "#",
		);
		parent::__construct($userdb, 'groupmembers', DB_AREA_GROUPMEMBERS, "groupmember-", $fields);
	}


	function getValues()
	{
		return $this->values;
	}
	
	function groupName()
	{	
		$this->ensureInitialized();
		
		return $this->group->name;
	}
	
	
	function groupType()
	{
		$this->ensureInitialized();
		
		return $this->group->type;
	}
	
	
	function groupTypeName()
	{
		$this->ensureInitialized();
		
		$groupTypeNames = getGroupTypeNames();
		
		return $groupTypeNames[$this->groupType()];
	}
	
	
	function groupLocation()
	{
		$this->ensureInitialized();
		
		return $this->group->location;
	}
	
	
	function fromDate()
	{
		$timestamp = strtotime($this->fromyear."-".$this->frommonth."-01");
		
		return date("F, Y", $timestamp);
	}
	
	
	function toDate()
	{
		if ($this->toyear == -1 && $this->tomonth == -1)
		{
			return "Present";
		}
		
		$timestamp = strtotime($this->toyear."-".$this->tomonth."-01");
		
		return date("F, Y", $timestamp);
	}
	
	
	function isVisibleTo($userid)
	{
		return isVisibleTo($this->userid, $userid, $this->visibility);
	}
	
	
	function ensureInitialized()
	{
		global $cache;
		
		if (!$this->group)
		{
			$template = new group($this->groupdb);
			$this->group = $template->getObject($this->groupid);
		}
	}	
}


class groupmembers
{
	public $groupdb;

	function __construct($groupdb, $userdb)
	{
		$this->groupdb = $groupdb;
		$this->userdb = $userdb;
	}


	function getGroupMembersForUser($userid)
	{
		global $cache;
		
		$data = $cache->get("groupmembers-$userid");
		if($data === false)
		{
			$result = $this->userdb->prepare_query("SELECT * FROM groupmembers WHERE userid = %", $userid);

			while ($line = $result->fetchrow())
			{
				$id = $line['userid'].':'.$line['groupid'];
				$data[$id] = $line;
			}
			
			$cache->put("groupmembers-$userid", $data, 24*60*60);
		}

		$objs = array();
		if (!empty($data))
		{
			foreach ($data as $id => $item)
			{
				// copy the object and add the data to it.
				$objs[$id] = new groupmember($this->groupdb, $this->userdb);
				$objs[$id]->setFromDBResultRow($item);
			}
		}
		return $objs;
	}
	
	
	function orderByGroupType($groupMembers)
	{
		$orderedMembers = array();
		$i = 0;
		foreach($groupMembers as $groupMember)
		{
			$orderedMembers[$i++] = $groupMember;
		}
		
		// Bubble sort of group members
		for($i=sizeof($orderedMembers); $i>0; $i--)
		{
			for($j=1; $j < $i; $j++)
			{
				$member1 = $orderedMembers[$j-1];
				$member2 = $orderedMembers[$j];
				
				if($member1->groupType() > $member2->groupType())
				{
					$orderedMembers[$j-1] = $member2;
					$orderedMembers[$j] = $member1;
				};
			}
		}
		
		return $orderedMembers;
	}
}