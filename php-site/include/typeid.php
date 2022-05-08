<?php

class typeid
{
	public $db;

	function __construct($db)
	{
		$this->db = $db;
	}

	function getTypeID($name)
	{
		global $cache;

		$key = 'typeid:' . urlencode($name);
		$id = $cache->get($key);
		if (!is_int($id))
		{
			$id = $this->db->prepare_query('SELECT typeid FROM typeid WHERE typename = ?', $name)->fetchfield();
			if (!is_numeric($id))
			{
				$res = $this->db->prepare_query('INSERT IGNORE INTO typeid (typename) VALUES (?)', $name);
				if ($res->affectedrows() == 0)
				{
					// conflicted with something else inserting the same thing, so retry. Should not be
					// possible for this to loop unless there's something really wrong.
					return $this->getTypeID($name);
				}
				$cache->remove("typename:$id");
				$id = $res->insertid();
			}
			$id = intval($id);
			$cache->put($key, $id, 7*24*60);
		}
		return $id;
	}

	function getTypeName($id)
	{
		global $cache;
		global $db;

		$key = "typename:$id";
		$name = $cache->get($key);
		if (!is_string($name))
		{
			$name = $this->db->prepare_query('SELECT typename FROM typeid WHERE typeid = #', $id)->fetchfield();
			$cache->put($key, $name, 7*24*60);
		}
		return $name;
	}
}
