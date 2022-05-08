<?php
/********************************************************************************
Copyright 2007 Octazen Solutions
All Rights Reserved

You may not reprint or redistribute this code without permission from Octazen Solutions.

WWW: http://www.octazen.com
Email: support@octazen.com
Version: 1.0.0
Date: 21 Sep 2007
********************************************************************************/

class LdifRecord  {

	var $map = array();

	function add($field, $value) {
	 	$field = strtolower($field);
	 	if (!array_key_exists($field,$this->map))
		 	$this->map[$field] = array();
	 	$vals =& $this->map[$field];
	 	$vals[] = $value;
	}

	function get($field) {
	 	$field = strtolower($field);
	 	if (!array_key_exists($field,$this->map)) return null;
	 	else return $this->map[$field];
	}

	function getFirst($field) {
	 	$vals = $this->get($field);
	 	if (empty($vals)) return null;
	 	return $vals[0];
	}

	function clear() {
	 	$this->map = array();
	}

	function remove($field) {
	 	$field = strtolower($field);
	 	unset($this->map[$field]);
	}

	function getFields() {
	 	return array_keys($this->map);
	}
}


class LdifParser {

	var $_lines;
	var $_idx;
	var $_count;

	function LdifParser($ldif) {
		$this->_lines = preg_split("/\r?\n/", $ldif);
		$this->_idx = 0;
		$this->_count = count($this->_lines);
	}

	function unescape($str) {
		// No unescaping performed for now
		return $str;
	}

	function next()  {
		$r = new LdifRecord;

		$previousKey = null;
		$previousValue = "";
		while ($this->_idx<$this->_count) {
			$s =& $this->_lines[$this->_idx++];
			if (empty($s)) {
				// Reached blank line.
				if ($previousKey != null)
					break;
				// Else, continue (since we're skipping multiple blank lines)
			} else {
				$c = $s[0];
				if ($c == '#') {
					//Skip comment line
				}
				else if ($c == ' ' || $c == '\t') {
					// This is a folded value
					$previousValue.=substr($s,1);
				} else {
					// Flush out previous value
					if ($previousKey != null) {
						$r->add($previousKey, $this->unescape($previousValue));
						$previousKey = null;
						$previousValue = "";
					}
					$i = strpos($s,':');
					if ($i>0) {
					 	//If we have 2 colons, then it's a bse64-encoded value
					 	if ($i+1 < strlen($s) && $s[$i+1]==':') {
							$previousKey = trim(substr($s,0,$i));
							$previousValue = ltrim(substr($s,$i+2));
							//It's now in utf8...leave it
							$previousValue = base64_decode($previousValue);
						}
						else {
							$previousKey = trim(substr($s,0,$i));
							$previousValue = ltrim(substr($s,$i+1));
						}
					}
				}
			}
		}
		if ($previousKey != null) {
			$r->add($previousKey, $this->unescape($previousValue));
			return $r;
		} else {
			return null;
		}
	}
}

?>