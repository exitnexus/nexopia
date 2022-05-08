<?php

// This is a base class for any object that is essentially a primary-key mapped
// entity in a database table. It defines the mapping between columns and members and
// identifies what needs to happen to commit the object to the database.
// The derived class needs to implement a function that will invalidate
// any cachelines associated with the ids passed to it, aside from the one
// set in the call to the constructor. This member function should be named
// invalidateCache()
class databaseobject
{
	private $db;
	private $table;
	private $cacheprefix;
	private $keyarea;
	private $columns;
	private $keynames;

	private $values;

	private $changes;

	// $columns is a map of column names to their types (as #, ?, %, and a special
	// one for this object to indicate the primary id field: ! -- which is assumed
	// to be like #).
    //
	// $handlers is a mapping of member names to function names that
    // will be called in response to a get or set. The name will be prefixed
    // with get/set to indicate the operation, and otherwise acts like __set
    // and __get, except __set must also return the value it was actually set to,
    // or false if it didn't change.
    //
    // $membermapping defines overrides for column names specified in
    // $columns. If $membermapping['somecolumn'] => 'somename', $obj->somename
    // will get/set to the database column 'somecolumn'.
    //
	// Note that this class will only respond to __set events
    // that match one of the keys in $columns or $handlers, or values of
    // $membermapping.
	function __construct($db, $table, $keyarea, $cacheprefix, $columns)
	{
		$this->db = $db;
		$this->table = $table;
		$this->cacheprefix = $cacheprefix;
		$this->keyarea = $keyarea;

		$balancekey = array();
		$this->keynames = array();
		foreach ($columns as $columnname => &$columntype)
		{
			if ($columntype == '%')
				$balancekey[$columnname] = '%';
			else if ($columntype == '!')
			{
				$this->keynames[$columnname] = '#';
				$columntype = '#';
			}
		}
		$this->columns = $columns;
		$this->keynames = array_merge($balancekey, $this->keynames);

		// array of the keys from $columns mapped to null values (as an initial
		$this->values = array_combine(array_keys($columns), array_pad(array(), count($columns), 0));
		$this->changes = array();
	}

	function __set($columnname, $value)
	{
		if (isset($this->values[$columnname]))
		{
			if ($value !== $this->values[$columnname])
			{
				$this->values[$columnname] = $value;
				$this->changes[$columnname] = $value;
			}
			return;
		}
		throw new dboError("Invalid attempt to set member named $name.");
	}

	function __get($name)
	{
		if (isset($this->values[$name]))
			return $this->values[$name];

		throw new dboError("Invalid attempt to get member named $name.");
	}

	// pass an array of columns to generate it from something other than this object
	private function makeKey($cols = null)
	{
		if ($cols === null)
			$cols = &$this->values;

		$keyelements = array();
		foreach ($this->keynames as $keyname => $type)
		{
			if (!$cols[$keyname])
				return false;
			$keyelements[$keyname] = $cols[$keyname];
		}
		return $keyelements;
	}

	private function getBalanceValue()
	{
		reset($this->keynames);
		$balancekey = key($this->keynames);
		return $this->values[$balancekey];
	}

	private function setPrimaryKey($val)
	{
		reset($this->keynames);
		foreach ($this->keynames as $key => $type)
			if ($type != '%') break;

		$this->$key = $val;
	}

	// this function works a little differently from the previous method
    // of doing it. It's not done through a static function, but through a
    // concept of the initial object being a never-committed template.
	function getObjects($ids)
	{
		global $cache;

		$data = $cache->get_multi($ids, $this->cacheprefix);
		$missing = array_diff($ids, array_keys($data));

		if ($missing)
		{
			$result = $this->db->prepare_query("SELECT " . implode(",", array_keys($this->columns)) . " FROM {$this->table} WHERE ^",
				$this->db->prepare_multikey($this->keynames, $missing));

			while ($line = $result->fetchrow())
			{
				$id = implode(':', $this->makeKey($line));
				$data[$id] = $line;
				$cache->put($this->cacheprefix . $id, $line, 24*60*60);
			}
		}
		$objs = array();
		foreach ($data as $id => $item)
		{
			// copy the object and add the data to it.
			$objs[$id] = clone($this);

			foreach ($objs[$id]->values as $column => &$value)
			{
				if (isset($item[$column]))
					$value = $item[$column];
			}
		}
		return $objs;
	}
	function getObject($id)
	{
		$objs = $this->getObjects(array($id));
		if ($objs)
			return array_shift($objs);
		else
			return false;
	}

	// This function invalidates cachelines associated with this object.
    // This is called after commit has pushed changes to the database,
    // but before the changes list has been cleared. The default implementation
    // clears $cacheprefix$itemid, and MUST be called by derived versions.
	// $type can be 'modify', 'delete', or 'create'
	function invalidateCache($type = 'modify')
	{
		global $cache;
		if ($type != 'create' && $id = $this->makeKey())
			$cache->remove($this->cacheprefix . implode(':', $id));
	}

	function hasChanged($columnname)
	{
		return (isset($this->changes[$columnname]));
	}

	// this function commits any changes made to the object's internal variables.
	function commit($splitid = false)
	{
		$affected = 0;
		$type = 'modify';
		$id = $this->makeKey();

		if ($id === false)
		{
			if (!($balance = $this->getBalanceValue()))
				throw dboError("Attempted to commit new item without a balance key set.");

			$itemid = $this->db->getSeqId($balance, $this->keyarea);
			$this->setPrimaryKey($itemid);

			$result = $this->db->prepare_array_query("INSERT INTO {$this->table} (" . implode(',', array_keys($this->columns)) . ") VALUES (" . implode(',', array_values($this->columns)) . ")", $this->values);

			$affected = $this->db->affectedrows($result);
			$type = 'create';
		} else if ($this->changes) {
			// otherwise, update with the changes.
			$query = "UPDATE {$this->table} SET ";
			$sets = array();
			foreach ($this->changes as $column => $value)
			{
				$sets[] = $this->db->prepare("{$column} = ?", $value);
			}
			$query .= implode(',', $sets);
			$query .= $this->db->prepare(" WHERE ^", $this->db->prepare_multikey($this->keynames, array($id)));
			$result = $this->db->query($query);
			$affected = $this->db->affectedrows($result);
		}
		if ($affected)
		{
			$this->invalidateCache($type);
			$this->changes = array();
		}
		return $affected;
	}

	static function deleteMulti($db, $objs)
	{
		$affected = 0;
		if ($objs)
		{
			$ids = array();
			foreach ($objs as &$obj)
			{
				if ($id = $obj->makeKey())
				{
					// assumption: these do not change between objects in this list.
					$keynames = $obj->keynames;
					$table = $obj->table;
					$ids[implode(':',$id)] = true;
				}
			}

			$ids = array_keys($ids);

			$result = $db->prepare_query("DELETE FROM {$table} WHERE ^", $db->prepare_multikey($keynames, $ids));
			$affected = $db->affectedrows($result);
			foreach ($objs as &$obj)
			{
				$obj->invalidateCache('delete');
				$obj->id = false;
			}
		}
		return $affected;
	}

	function delete()
	{
		return self::deleteMulti($this->db, array(implode(':',$this->makeKey()) => &$this));
	}
}
