<?php
/********************************************************************************
JSON tools

Copyright 2008 Octazen Solutions
All Rights Reserved

You may not reprint or redistribute this code without permission from Octazen Solutions.

WWW: http://www.octazen.com
Email: support@octazen.com
Date: 27 July 2008
********************************************************************************/

define ('JSONTYPE_OBJECT',0);
define ('JSONTYPE_ARRAY',1);
define ('JSONTYPE_NULL',2);
define ('JSONTYPE_BOOLEAN',3);
define ('JSONTYPE_STRING',4);
define ('JSONTYPE_NUMERIC',5);


define('JSONNSTATE_INTEGER',0);
define('JSONNSTATE_FRACTION',1);
define('JSONNSTATE_E',2);
define('JSONNSTATE_E_DIGITS',3);

function json_is_valid_name_char ($c) {
 	$c = ord($c);
	//return (($v >= ord('A') && $c <= ord('Z')) || ($c >= ord('a') && $c <= ord('z')) || $c == ord('$') || $c == ord('_'));
	return (($c>=65 && $c<=90) || ($c>=97 && $c<=122) || $c==36 || $c==95);
}


class JsonParser {
 	var $json;
 	var $idx;
 	var $jsonlen;
 	var $assoc; //true=use associative array, false=use stdclass
 	
	function JsonParser ($str, $assoc=false) {
	 	$this->json = $str;
	 	$this->idx = 0;
	 	$this->jsonlen = strlen($str);
	 	$this->assoc = $assoc;
	}
	
	function read () {
		if ($this->idx<$this->jsonlen) {
			return $this->json[$this->idx++];
		}
		else {
			return null;
		}
	}
	
	function skipWhitespace () {
		$i = $this->idx;
		$n = $this->jsonlen;
		for ($i=$this->idx; $i<$n; $i++) {
			if (!ctype_space($this->json[$i])) break;
		}
		$this->idx = $i;
	}
	
	function readName () {
		$this->skipWhitespace();
		
		$i1 = $this->idx;
		$n = $this->jsonlen;
		if ($i1>=$n) return null;
		$i = $i1;
		for (;$i<$n;$i++) {
			if (!json_is_valid_name_char($this->json[$i])) 
				break;
		}
		$this->idx = $i;
		if ($i==$i1) return null;
		else return substr($this->json,$i1,$i-$i1);
	}

	function readString() {
	 
	 	//TODO: OPTIMIZE BY SIMPLY USING SUBSTR IF NO ESCAPE SEQUENCE ENCOUNTERED
	 
		// Read first char. If it's " or ', then it's a quoted name.
		// Otherwise we'll throw an exception as we cant recognize type

		$i = $this->idx;
		$n = $this->jsonlen;
		if ($i>=$n) return null;
		$c = $this->json[$i++];
		$quoteChar = $c;
		$sb = '';
		$escape = false;
		for (;$i<$n;$i++) {
			$c = $this->json[$i];
			if ($escape) {
				$escape = false;
				switch ($c) {
				case 'r': $sb.="\r"; break;
				case 'n': $sb.="\n"; break;
				case 't': $sb.="\t"; break;
				case 'b': $sb.="\b"; break;
				case 'u':
					// Read the next 4 digits as the unicode character code!!!!!!
					$hex = substr($this->json,$i+1,4);
					$i+=4;
					if ($hex != null && strlen($hex) == 4) {
					 	//Try to parse unicode (??)
					 	$cv = hexdec($hex);
					 	$sb.=chr_utf8($cv);
						break;
					}
					break;
				// TODO FUTURE SUPPORT FOR \\U, which is 32-bit unicode
				case '\\':
				case '"':
				case '\'':
				default:
					$sb.=$c;
					break;
				}
			} else {
				if ($c == '\\') {
					$escape = true;
				} else if ($c == $quoteChar) {
					// Hit the ending quote char
					$i++;
					break;
				} else {
					$sb.=$c;
				}
			}
		}
		$this->idx = $i;
		return $sb;
	}
	
	function readNumber()  {

		// First char can be 0 or -
		$this->skipWhitespace();

		$i1 = $this->idx;
		$n = $this->jsonlen;
		if ($i1>=$n) return null;

		// Verify this is a number (or not!)
		$c = $this->json[$i1];
		if ($c!='-' && c!='+' && !ctype_digit($c)) {
			return null;
		}

		// NUMBR: -123.456e-10
		// STATE: 000001111233
		//
		// We don't use octal or hex form numbers in JSON. (See json.org)
		// But we may define numbers in exponent form
		//
		// We can have:
		// -10e3 (notice we jumped to state 2 straight once we hit an e)
		// -10e3 (notice we jumped to state 2 once encountered 3, and jumped to
		// state 3 since we couldn't find a - or + after e)

		$state = JSONSTATE_INTEGER;
		$i = $i1;
		//$sb = '';
		//$sb.=$c;
		for (;$i<$n;$i++) {
			$c = $this->json[$i];
			//if ($c == null) break;
			if ($state == JSONSTATE_INTEGER) {
				if ($c == '.')
					$state = JSONSTATE_FRACTION;
				else if ($c == 'e' || $c == 'E')
					$state = JSONSTATE_E;
				else if (!ctype_digit($c)) {
					break;
				}
			} else if ($state == JSONSTATE_FRACTION) {
				if ($c == 'e' || $c == 'E')
					$state = JSONSTATE_E;
				else if (!ctype_digit($c)) {
					break;
				}
			} else if ($state == JSONSTATE_E) {
				if (ctype_digit($c)) {
					// Do nothing. Append to sb, and switch to e digits state
				} else if ($c != '-' && $c != '+') {
					break;
				}
				$state = JSONSTATE_E_DIGITS;
			} else {
				// State is 3 ...must be...
				if (!ctype_digit($c)) {
					break;
				}
			}
			//$sb.=$c;
		}

		$this->idx = $i;
		$sv = substr($this->json,$i1,$i-$i1);
		// If state reached 1, then we have decimal places. Use double
		// instead
		if ($state >= STATE_FRACTION) {
		 	$d = floatval($sv);
			//return new JsonValue(d);
			return $d;
		} else {
		 	$iv = intval($sv);
			//int iv = Integer.parseInt(sv);
			//return new JsonValue(iv);
			return $iv;
		}
	}	

	function &readObject () {

		$obj = $this->assoc ? array() : new stdclass;

		// Read members of the object
		while (true) {
			$this->skipWhitespace();

			// Read name
			$name = $this->readString();
			if ($name == null) break;

			// Read ":"
			$this->skipWhitespace();
			$c = $this->read();
			//PHP4 doesn't support exceptions. We'll just swallow the error for now...
			//if ($c != ':') throw new JsonParserException("Expecting ':'");

			// Read value
			$value = $this->readValue();
			if ($value[0]===false) break;

			
			//FIXME WHAT IF $VALUE IS EOF???
			
			//Set a value in the object. 
			if ($this->assoc) $obj[$name]=$value[1];
			else $obj->$name = $value[1];
			
			// Read "," or "}" or "]" or ")"
			$this->skipWhitespace();
			$c = $this->read();
			if ($c == null || $c == '}') {
				break;
			} else if ($c != ',') {
			 	//PHP doesn't support exceptions. We'll just swallow the error for now ...
				//throw new JsonParserException("Expecting ','");
			}
		}
		return $obj;
	}
	

	function &readArray() {

		$obj = array();
		while (true) {
			$this->skipWhitespace();

			// Read value
			$val =& $this->readValue();
			if ($val[0]===false) break;
			$obj[] = $val[1];

			// Next, we expect either , or ]
			$this->skipWhitespace();
			$c = $this->read();
			if ($c == ']')
				break;
			else if ($c != ',') {
			 	//PHP doesn't support exceptions. We'll just swallow the error for now ...
			 	
				// Unexpected token! Expecting a "," for next item!
				//throw new JsonParserException("Expecting ','");
			}
		}
		return $obj;
	}
	

	//Attempt to read a PHP value
	//Returns an array. First element is a "value was read" boolean value. If true, then value is in the 2nd array element. If false, it means no data was read (typically, EOF)
	function &readValue() {

		//EOF...
		if ($this->idx>=$this->jsonlen)
			return array(false,null);
		
		// Read until comma? read until }, ), ] ?

		// Skip whitespaces
		$this->skipWhitespace();

		// Read character and figure out what it is
		$c = $this->json[$this->idx];
		if ($c == '}' || $c == ']' || $c == ')' || $c == ',') {
			return array(false,null);
			//return null;
		} else if ($c == '{') {
		 	$this->idx++; 
			$v =& $this->readObject();
			return array($v===null?false:true,$v);
		} else if ($c == '[') {
		 	$this->idx++;
			$v =& $this->readArray();
			return array($v===null?false:true,$v);
		} else if ($c == '"' || $c == '\'') {
			$v =& $this->readString();
			return array($v===null?false:true,$v);
			//if ($s == null) return null;
			//else return new JsonValue(s);
		} else if ($c == '+' || $c == '-' || ctype_digit($c)) {
			// If first digit is "0", check if it's a hex code.
			// Read until we hit x
			$v = $this->readNumber();
			return array($v===null?false:true,$v);
		} else {
			$s =& $this->readName();
			if ($s === null) return array(false,null);
			$s = strtolower($s);
			if ($s==='null') {
				return array(true,null);
			 	//return null;		//?????????????????????
				//return JsonValue.NULL;
			} else if ($s==='true') {
				return array(true,true);
			 	//return true;
				//return new JsonValue(true);
			} else if ($s==='false') {
				return array(true,false);
			 	//return false;
				//return new JsonValue(false);
			} else {
			 	//PHP doesn't support exceptions. We'll just swallow the error for now ...
			 	
				// CRAP! Don't know what it is!
				//throw new UnsupportedOperationException("NOT YET IMPLEMENTED. GOT " + s);
			}
		}
	}	
}

function oz_json_decode ($json, $assoc) {
 	//Use PHP's json decode where available
 	if (function_exists('json_decode')) {
		return json_decode($json,$assoc);
	}
	else {
		$p = new JsonParser($json,$assoc);
		$v = $p->readValue();
		return $v[1];	//No matter if got value, or not
	}
}



?>