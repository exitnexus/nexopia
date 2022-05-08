<?

function increasepriority(&$db, $table, $id, $where="", $circular = false){
	global $msgs;
	if(empty($id) || empty($table))
		return false;

	$id = $db->escape($id);

	$query = "SELECT priority FROM $table WHERE id = $id";
	if($where) $query .=" && $where";
	$db->query($query);
	$priority = $db->fetchfield();

	if($priority>1){  //max priority = 1
		$query = "SELECT id FROM $table WHERE priority=$priority-1";
		if($where) $query .=" && $where";
		$result = $db->query($query);
		$lid = $db->fetchfield();

		$query = "UPDATE $table SET priority=priority-1 WHERE id = '$id'";
		if($where) $query .=" && $where";
		$db->query($query);

		$query="UPDATE $table SET priority=priority+1 WHERE id = '$lid'";
		if($where) $query .=" && $where";
		$db->query($query);

		$msgs->addMsg("Priority Increased");
		return true;
	}elseif($circular){ //already max, set to min
		$query="UPDATE $table SET priority=priority-1 WHERE priority > $priority";
		if($where) $query .=" && $where";
		$db->query($query);
		$numModified = $db->affectedrows();

		$query="UPDATE $table SET priority=$priority+$numModified WHERE id='$id'";
		if($where) $query .=" && $where";
		$db->query($query);

		$msgs->addMsg("Priority set to Minimum");
		return true;
	}else{
		$msgs->addMsg("Already Max Priority");
		return false;
	}
}

function decreasepriority(&$db, $table, $id, $where="", $circular = false){
	global $msgs;
	if(empty($id) || empty($table))
		return false;

	$id = $db->escape($id);

	$query = "SELECT priority FROM $table WHERE id='$id'";
	if($where) $query .=" && $where";
	$db->query($query);
	$priority = $db->fetchfield();

	$query = "SELECT count(*) FROM $table";
	if($where) $query .=" WHERE $where";
	$db->query($query);
	$maxPriority = $db->fetchfield();


	if($priority<$maxPriority){
		$query = "SELECT id FROM $table WHERE priority=$priority+1";
		if($where) $query .=" && $where";
		$db->query($query);
		$hid = $db->fetchfield();

		$query="UPDATE $table SET priority=priority+1 WHERE id = '$id'";
		if($where) $query .=" && $where";
		$db->query($query);

		$query="UPDATE $table SET priority=priority-1 WHERE id = '$hid'";
		if($where) $query .=" && $where";
		$db->query ($query);

		$msgs->addMsg("Priority Decreased");
		return true;
	}elseif($circular){ //already min, set to max
		$query="UPDATE $table SET priority=priority+1 WHERE priority < $priority";
		if($where) $query .=" && $where";
		$db->query($query);
		$numModified = $db->affectedrows();

		$query="UPDATE $table SET priority=1 WHERE id='$id'";
		if($where) $query .=" && $where";
		$db->query($query);

		$msgs->addMsg("Priority set to Maximum");
		return true;
	}else{
		$msgs->addMsg("Already Min Priority");
		return false;
	}
}

function setMaxPriority(&$db, $table, $id, $where=""){ //max priority id, lowest priority though

	if(empty($id) || empty($table))
		return false;

	$id = $db->escape($id);

	$query = "SELECT priority FROM $table WHERE id='$id'";
	if($where) $query .=" && $where";
	$db->query($query);
	$line = $db->fetchrow();
	$priority = $line['priority'];

	$query="UPDATE $table SET priority=priority-1 WHERE priority > $priority";
	if($where) $query .=" && $where";
	$db->query($query);
	$numModified = $db->affectedrows();

	$query="UPDATE $table SET priority=$priority+$numModified WHERE id='$id'";
	if($where) $query .=" && $where";
	$db->query($query);
}

function getMaxPriority(&$db, $table, $where=""){ //max priority id, lowest priority though

	$query = "SELECT count(*) FROM $table";
	if($where) $query .=" WHERE $where";
	$result = $db->query($query);
	return $db->fetchfield() +1;
}

function fixPriorities(&$db, $table, $where=""){

	$query = "SELECT id,priority FROM $table";
	if($where) $query .= " WHERE $where";
	$query .= " ORDER BY priority";
	$result = $db->query($query);

	for($i=1; $line = $db->fetchrow($result); $i++){
		if($line['priority'] =! $i){
			$query = $db->prepare("UPDATE $table SET priority = ? WHERE id = ?", $i, $line['id']);
			if($where) 	$query .= " && $where";
			$db->query($query);
		}
	}
}

