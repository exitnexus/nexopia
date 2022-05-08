<?

function increasepriority(&$db, $table, $id, $where="", $circular = false){
	global $msgs;
	if(empty($id) || empty($table))
		return false;

	$query = $db->prepare("SELECT priority FROM $table WHERE id = #", $id);
	if($where) $query .=" && $where";
	$res = $db->query($query);
	$priority = $res->fetchfield();

	if($priority>1){  //max priority = 1
		$query = $db->prepare("SELECT id FROM $table WHERE priority = # - 1", $priority);
		if($where) $query .=" && $where";
		$result = $db->query($query);
		if ($lrow = $result->fetchrow())
			$lid = $lrow['id'];
		else {
			fixPriorities($db, $table, $where);
			return increasepriority($db, $table, $id, $where, $circular);
		}

		$query = $db->prepare("UPDATE $table SET priority = priority - 1 WHERE id = #", $id);
		if($where) $query .=" && $where";
		$db->query($query);

		$query = $db->prepare("UPDATE $table SET priority = priority + 1 WHERE id = #", $lid);
		if($where) $query .=" && $where";
		$db->query($query);

		$msgs->addMsg("Priority Increased");
		return array($lid, $id);
	}elseif($circular){ //already max, set to min
		$query = $db->prepare("UPDATE $table SET priority = priority - 1 WHERE priority > #", $priority);
		if($where) $query .=" && $where";
		$db->query($query);
		$numModified = $db->affectedrows();

		$query = $db->prepare("UPDATE $table SET priority = priority + # WHERE id = #", $numModified, $id);
		if($where) $query .=" && $where";
		$db->query($query);

		$msgs->addMsg("Priority set to Minimum");
		return array($id);
	}else{
		$msgs->addMsg("Already Max Priority");
		return false;
	}
}

function decreasepriority(&$db, $table, $id, $where="", $circular = false){
	global $msgs;
	if(empty($id) || empty($table))
		return false;

	$query = $db->prepare("SELECT priority FROM $table WHERE id = #", $id);
	if($where) $query .=" && $where";
	$res = $db->query($query);
	$priority = $res->fetchfield();

	$query = "SELECT count(*) FROM $table";
	if($where) $query .=" WHERE $where";
	$res = $db->query($query);
	$maxPriority = $res->fetchfield();


	if($priority<$maxPriority){
		$query = $db->prepare("SELECT id FROM $table WHERE priority = # + 1", $priority);
		if($where) $query .=" && $where";
		$res = $db->query($query);
		if ($hrow = $res->fetchrow())
			$hid = $hrow['id'];
		else {
			fixPriorities($db, $table, $where);
			return decreasepriority($db, $table, $id, $where, $circular);
		}

		$query = $db->prepare("UPDATE $table SET priority = priority + 1 WHERE id = #", $id);
		if($where) $query .=" && $where";
		$db->query($query);

		$query = $db->prepare("UPDATE $table SET priority = priority - 1 WHERE id = #", $hid);
		if($where) $query .=" && $where";
		$db->query($query);

		$msgs->addMsg("Priority Decreased");
		return array($hid, $id);
	}elseif($circular){ //already min, set to max
		$query = $db->prepare("UPDATE $table SET priority = priority + 1 WHERE priority < #", $priority);
		if($where) $query .=" && $where";
		$db->query($query);
		$numModified = $db->affectedrows();

		$query = $db->prepare("UPDATE $table SET priority = 1 WHERE id = #", $id);
		if($where) $query .=" && $where";
		$db->query($query);

		$msgs->addMsg("Priority set to Maximum");
		return array($id);
	}else{
		$msgs->addMsg("Already Min Priority");
		return false;
	}
}

function setMaxPriority(&$db, $table, $id, $where=""){//max priority id, lowest priority though

	if(empty($id) || empty($table))
		return false;

	$query = $db->prepare("SELECT priority FROM $table WHERE id = #", $id);
	if($where) $query .=" && $where";
	$res = $db->query($query);
	$line = $res->fetchrow();
	$priority = $line['priority'];

	$query = $db->prepare("UPDATE $table SET priority = priority - 1 WHERE priority > #", $priority);
	if($where) $query .=" && $where";
	$db->query($query);
	$numModified = $db->affectedrows();

	$query = $db->prepare("UPDATE $table SET priority = priority + # WHERE id = #", $numModified, $id);
	if($where) $query .=" && $where";
	$db->query($query);
}

function getMaxPriority(&$db, $table, $where=""){ //max priority id, lowest priority though

	$query = "SELECT count(*) AS count FROM $table";
	if($where) $query .=" WHERE $where";
	$res = $db->query($query);
	$count = 0;
	while ($line = $res->fetchrow())
		$count += $line['count'];
	return $count + 1;
}

function fixPriorities(&$db, $table, $where=""){

	$query = "SELECT id, priority FROM $table";
	if($where) $query .= " WHERE $where";
	$query .= " ORDER BY priority";
	$result = $db->query($query);

	for($i=1; $line = $result->fetchrow(); $i++){
		if($line['priority'] != $i){
			$query = $db->prepare("UPDATE $table SET priority = # WHERE id = #", $i, $line['id']);
			if($where) 	$query .= " && $where";
			$db->query($query);
		}
	}
}

