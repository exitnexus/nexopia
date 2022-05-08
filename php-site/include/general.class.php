<?php

// this file is for generally useful utility and base classes.

// utility class for stuff that has a msg field that needs to be translated into an
// nmsg field.
class textparser
{
	private $msg;
	private $nmsg; // false if unchanged and unset, true if changed and unset, a string if set.

	function __construct($msg)
	{
        $this->msg = trim($msg);
        $this->nmsg = false;
	}

	protected function parseMsg()
	{
	        $this->nmsg =  trim($this->msg);

		$this->nmsg = removeHTML($this->nmsg);

		$this->nmsg = parseHTML($this->nmsg);
		$this->nmsg = smilies($this->nmsg);
		$this->nmsg = wrap($this->nmsg);
		$this->nmsg = nl2br($this->nmsg);
        }

    // $items has to be in array(id => &obj) form
	static function getParsedTextMulti($items, $cacheprefix)
	{
		global $cache;
		$posttexts = array();

		if (!$items)
			return;

		$itemids = array();
		foreach ($items as $itemid => &$item)
		{
			if ($item->nmsg === false)
			{
				$itemids[] = $itemid;
			}
		}

		$itemtexts = $cache->get_multi($itemids, $cacheprefix);
		foreach ($items as $itemid => &$item)
		{
			if (isset($itemtexts[$itemid]))
			{
				$item->nmsg = $itemtexts[$itemid];
			} else {
				$item->parseMsg();
				$cache->put("{$cacheprefix}$itemid", $item->nmsg, 7*24*60*60);
			}
		}
	}

	function getParsedText($itemid, $cacheprefix)
	{
		if (is_bool($this->nmsg))
		{
			if ($itemid === false || $this->nmsg === true)
				$this->parseMsg();
			else
				self::getParsedTextMulti(array($itemid => &$this), $cacheprefix);
		}

		return $this->nmsg;
	}

	function getText()
	{

		return removeHTML($this->msg);

	}

	function setText($msg)
	{
		$newmsg = trim($msg);

		$newmsg = removeHTML($newmsg);


		if ($newmsg != $this->msg)
		{
			$this->msg = $newmsg;
			$this->nmsg = true;
			return true;
		}
		return false;
	}
}

class dboError extends Exception
{
	function __construct($str)
	{
		parent::__construct($str);
	}
}

// add to this to add custom type validators.
global $typeregexes;
$typeregexes = array(
	'integer' => '-?[0-9]+',
	'idpair' => '[0-9]+:[0-9]+',
	'int' => '-?[0-9]+',
	'string' => '.+', // note: this may be bypassed
	'xml' => '.+', // note: this really SHOULD be bypassed
	'float' => '[0-9]+\.?[0-9]*'
);

// to indicate an array of a particular field type, you can also
// pass an array with the type as the single value. Ie. array('integer')
function varargs($argname, $vartype, $inputtype = 'request', $required = true, $default = null)
{
	if (!is_bool($required)) trigger_error("fourth argument to varargs() must be of type boolean!", E_USER_ERROR);
	return array($argname, $vartype, $inputtype, $required, $default);
}

class subhandlerbase
{
	private $obj;
	private $func;
	private $passargs;
	private $minlevel;
	private $adminpriv;

	private $actuallevel;

	function __construct(&$obj, $func, $minlevel)
	{
		$this->obj = &$obj;

		if (is_array($func))
		{
			$this->func = $func[0];
			$this->passargs = array_slice($func, 1);
		} else {
			$this->func = $func;
			$this->passargs = array();
		}

		if (is_array($minlevel))
		{
			$this->adminpriv = $minlevel[1];
			$this->minlevel = $minlevel[0];
		} else {
			$this->minlevel = $minlevel;
			$this->adminpriv = null;
		}
	}

	function checkMinLevel($userData)
	{
		global $mods;
		$isAdmin = false;
		if (isset($this->adminpriv))
			$isAdmin = $mods->isAdmin($userData['userid'],$this->adminpriv);

		if($this->minlevel == REQUIRE_NOTLOGGEDIN && $userData['loggedIn'])
			return false;
		if($this->minlevel == REQUIRE_HALFLOGGEDIN && !$userData['halfLoggedIn'])
			return false;
		if($this->minlevel == REQUIRE_LOGGEDIN && !$userData['loggedIn'])
			return false;
		if($this->minlevel == REQUIRE_LOGGEDIN_PLUS && (!isset($userData['premium']) || !$userData['premium']))
			return false;
		if($this->minlevel == REQUIRE_LOGGEDIN_ADMIN && !$isAdmin)
			return false;

		// now set the actual level of the user
		if($userData['loggedIn'])
			$this->actuallevel = REQUIRE_LOGGEDIN;
		elseif($userData['halfLoggedIn'])
			$this->actuallevel = REQUIRE_HALFLOGGEDIN;
		else
			$this->actuallevel = REQUIRE_NOTLOGGEDIN;

		if (isset($userData['premium']) && $userData['premium'])
			$this->actuallevel = REQUIRE_LOGGEDIN_PLUS;
		if ($isAdmin)
			$this->actuallevel = REQUIRE_LOGGEDIN_ADMIN;

		return true;
	}

	function callfunc($pageargs, $trace)
	{
		if (!is_array($pageargs))
			$pageargs = array($pageargs);

		$this->obj->setActualLevel($this->actuallevel);

		if ($trace) { print("TRACE: Calling function {$this->func} with args => "); var_dump($pageargs); print("<br>\n"); }

		return call_user_func_array(array(&$this->obj, $this->func), array_merge($this->passargs, $pageargs));
	}

	protected function checkvartype($type, &$value)
	{
		global $typeregexes;

		if (is_array($type))
		{
			if (!is_array($value))
				return false;

			$type = $type[0];

			foreach ($value as $single)
			{
				if (!$this->checkvartype($type, $single))
					return false;
			}
		} else {
			if ($type == 'xml')
			{
				$xmlobj = simplexml_load_string($value);
				if ($xmlobj)
					$value = $xmlobj;
				else
					return false;
			} else {
				$regex = (isset($typeregexes[$type])? $typeregexes[$type] : $type);
				if ($type != 'string' && !preg_match("/^$regex$/", $value))
					return false;
			}
		}
		return true;
	}

	public function getActualLevel()
	{
		return $this->actuallevel;
	}

	public function getHandlerName()
	{
		return $this->func;
	}
}

class varsubhandler extends subhandlerbase
{
	private $args;

	function __construct(&$obj, $func, $minlevel = REQUIRE_ANY)
	{
		parent::__construct($obj, $func, $minlevel);

		$args = func_get_args();
		$args = array_slice($args, 3);
		$this->args = $args;
	}

	// returns true if it matches and was executed, false if not.
	function execute($pagename, $extra, $postvars, $reqvars, $userData, $trace)
	{
		if(!$this->checkMinLevel($userData))
			return false;

		$funcargs = array();
		foreach ($this->args as $arg)
		{
			list($argname, $vartype, $inputtype, $required, $default) = $arg;
			if ($inputtype == 'post')
				$checkfrom = &$postvars;
			else
				$checkfrom = &$reqvars;

			if ($trace) {print("TRACE: Checking for argument => "); var_dump($arg); print("<br>\n");}

			if ($required && !isset($checkfrom[$argname]))
				return false;

			if (isset($checkfrom[$argname]) && $this->checkvartype($vartype, $checkfrom[$argname]))
				$value = $checkfrom[$argname];
			else if ($required)
				return false;
			else
				$value = $default;

			$funcargs[$argname] = $value;
		}
		$this->callfunc($funcargs, $trace);
		return true;
	}
}

function methodargs($argname, $vartype, $required = true, $default = null)
{
	return array($argname, $vartype, $required, $default);
}

class methodhandler extends subhandlerbase
{
	private $args;

	function __construct(&$obj, $func, $minlevel = REQUIRE_ANY)
	{
		parent::__construct($obj, $func, $minlevel);

		$args = func_get_args();
		$args = array_slice($args, 3);
		$this->args = $args;
	}

	function execute($methodname, $args, $userData)
	{
		if (!$this->checkMinLevel($userData))
			return false;

		$funcargs = array();
		foreach ($this->args as $arg)
		{
			list($argname, $vartype, $required, $default) = $arg;

			if (isset($args[$argname]) && $this->checkvartype($vartype, $args[$argname]))
				$value = $checkfrom[$argname];
			else if ($required)
				return false;
			else
				$value = $default;

			$funcargs[$argname] = $value;
		}
		$this->callfunc($funcargs);
		return true;
	}
}

function uriargs($argname, $vartype)
{
	return array($argname, $vartype);
}

class urisubhandler extends subhandlerbase
{
	private $minlevel;
	private $adminpriv;
	private $args;

	function __construct(&$obj, $func, $minlevel = REQUIRE_ANY)
	{
		parent::__construct($obj, $func, $minlevel);

		$args = func_get_args();
		$args = array_slice($args, 3);
		$this->args = $args;
	}

	// returns true if matched and executed, false if not.
	function execute($pagename, $extra, $postvars, $reqvars, $userData, $trace)
	{
		global $typeregexes;

		if (!$this->checkMinLevel($userData))
			return false;

		$matchstr = "/^";
		$argnames = array();
		foreach ($this->args as $arg)
		{
			list($argname, $vartype) = $arg;

			$argnames[] = $argname;
			$regex = (isset($typeregexes[$vartype])? $typeregexes[$vartype] : $vartype);

			$matchstr .= "\/($regex)"; // group all the args
		}
		$matchstr .= "$/";
		if ($trace) print("TRACE: Matching '$extra' against '$matchstr'.<br>\n");
		$matches = array();
		if (!preg_match($matchstr, $extra, $matches))
			return false;

		$matches = array_slice($matches, 1);
		if (count($matches))
			$funcargs = array_combine($argnames, $matches);
		else
			$funcargs = array();

		foreach ($funcargs as $key => &$val)
			$val = urldecode($val);

		// function should return an array of results.
		// anything else will be considered a failure/pass.
		return $this->callfunc($funcargs, $trace);
	}
}

global $tracepagehandlers;
if (!isset($tracepagehandlers))
	$tracepagehandlers = false;

global $pagehandlers;
$pagehandlers = array(); // this really belongs in the class as a static variable,
                         // but apc3.0.8 + php5.1 goes nuts.
global $methodhandlers;
$methodhandlers = array(); // this does too

// this base class is used to simplify the construction of a page view. It
// handles initial sanitization of input data, deals with branching based on the
// type of input data given to it, and potentially in the future will allow for
// a switch to a client/server model instead of php-instance-per-page model as is
// currently used.
class pagehandler
{
//	static private $pagehandlers = array();
	private $actuallevel;

	// call this to register a handler function for normal post/get vars
    // $uri should be __FILE__ for normal handlers, and an arbitrary /anchored
    // string for niceuri handlers.
	function registerSubHandler($uri, $subhandler)
	{
		global $pagehandlers, $docRoot, $tracepagehandlers;

		if ($tracepagehandlers)
			print("TRACE: Registering subhandler for '$uri', docRoot is '$docRoot'<br>\n");

		$uri = rtrim(str_replace('\\', '/', $uri), '/');
		$docroot = rtrim(str_replace('\\', '/', $docRoot), '/');

		if (strpos($uri, $docroot) === 0)
			$uri = substr($uri, strlen($docroot));

		if (!isset($pagehandlers[$uri]))
			$pagehandlers[$uri] = array();

		$pagehandlers[$uri][] = $subhandler;
	}

	function registerMethodHandler($methodname, $methodhandler)
	{
		global $methodhandlers;

		if (!isset($methodhandlers[$uri]))
			$methodhandlers[$methodname] = array();

		$methodhandlers[$methodname][] = $methodhandler;
	}

	static function executeHandler($pagename, $postvars, $reqvars, $userData)
	{
		global $pagehandlers, $tracepagehandlers;

		$pagename = rtrim($pagename, '/');
		if ($tracepagehandlers)
		{
			print("TRACE: $pagename\n Post => "); var_dump($postvars);
			print("<br>\n Request => "); var_dump($reqvars);
			print("<br>\n UserData => "); var_dump($userData);
			print("<br>\n");
		}
		foreach ($pagehandlers as $uri => $handlerarr)
		{
			if ($tracepagehandlers)
				print("TRACE: Checking '$pagename' against handler for '$uri'<br>\n");

			if (strpos($pagename, $uri) === 0)
			{
				$extra = substr($pagename, strlen($uri));
				foreach ($handlerarr as $subhandler)
				{
					if ($tracepagehandlers) {print("TRACE: Checking handler " . $subhandler->getHandlerName() . "<br/>\n");}

					if ($subhandler->execute($pagename, $extra, $postvars, $reqvars, $userData, $tracepagehandlers))
					{
						if ($tracepagehandlers) print("TRACE: Handler successful<br>\n");
						return true;
					}
				}
			}
		}
		if ($tracepagehandlers) print("TRACE: No handler found.<br>\n");
		return false;
	}

	static function executeMethod($methodname, $args)
	{
		global $methodhandlers, $userData;
		if (isset($methodhandlers[$methodname]))
		{
			// if we got login details, do that first
			if (isset($args['userid']) && isset($args['key']))
				requireLogin(REQUIRE_ANY, false, $args['userid'], $args['key']);

			foreach ($methodhandlers[$methodname] as $methodhandler)
			{
				$result = $methodhandler->execute($args, $userData);
				if (is_array($result))
					return $result;
			}
		}
		return false;
	}

	// simple function to cause the current page as defined by php preset variables
    // to run. Eventually this may become a legacy function.
	static function runPage()
	{
		// $runstandalone is a config var to indicate that php is running in
        // some kind of standalone server and should not attempt to run the
        // executeHandler on every file load, but will instead be run directly
        // by some kind of handler.
		global $runstandalone, $userData;
		if (!isset($runstandalone) || !$runstandalone)
		{
			header("Status: 200 OK");
			// Don't pass in $_POST if the request came from an untrusted source, just give it an empty array.
			return self::executeHandler($_SERVER['REQUEST_URI'], isValidPost()? $_POST : Array(), $_REQUEST, $userData);
		}
	}
	function reRunPage($req = array(), $post = array())
	{
		global $userData;
		return self::executeHandler($_SERVER['REQUEST_URI'], $post, $req, $userData);
	}

	public function setActualLevel($level)
	{
		$this->actuallevel = $level;
	}

	public function getActualLevel()
	{
		return $this->actuallevel;
	}
}


//This is a class for maintaining a timetable based on days/hours of the week
//It was designed for use with banners but could be applied elsewhere
class timetable {
	public $constructorString;
	public $allowedtimes; //constructor string with invalid entries removed
	public $invalidRanges; //invalid entries from constructor string
	public $validHours;

	//$times needs to be comma seperated day/hour ranges
	//days can be SMTWRFY, hours 0-23
	//days can be listed or ranged MTWR or M-R or MT-R are all equivalent
	//hours must be ranged unless only a single hour is specified, 11 or 11-12 are equivalent
	function __construct($times) {
		$constructorString = $times;
		//strip out non-whitelisted characters
		$times = preg_replace('/[^MmTtWwRrFfYySs\-\d,]+/', '', $times);
		//if any ranges are empty remove them
		$times = preg_replace('/,+/', ',', $times);

		$ranges = explode(',', $times);

		foreach ($ranges as $key => $range) {
			if (!$this->validateRange($range)) {
				unset($ranges[$key]);
				$this->invalidRanges[] = $range;
			}
		}
		$this->allowedtimes = implode(',', $ranges);
		$this->parseAllowedTimes();

	}

	function printTimeTable() {
		echo $this->getTimeTable();
	}

	function getTimeTable() {
		$str = "<table cellspacing=1px cellpadding=1px><tr><td></td>";
		for ($hour=0; $hour<24; $hour++) {
			$str .= "<td class=header>";
			if ($hour==0) {
				$str .= "12am";
			} elseif($hour < 12) {
				$str .= $hour."am";
			} elseif($hour == 12) {
				$str .= "12pm";
			} else {
				$str .= ($hour%12)."pm";
			}
			$str .= "&nbsp;</td>";
		}
		$str .= "</tr>";
		for ($day = 0; $day < 7; $day++) {
			$str .= "<tr><td class=header>";
			switch ($day) {
				case 0:
				$str .= 'Sunday';
				break;
				case 1:
				$str .= 'Monday';
				break;
				case 2:
				$str .= 'Tuesday';
				break;
				case 3:
				$str .= 'Wednesday';
				break;
				case 4:
				$str .= 'Thursday';
				break;
				case 5:
				$str .= 'Friday';
				break;
				case 6:
				$str .= 'Saturday';
				break;
			}
			$str .= '</td>';
			for ($hour=0; $hour<24; $hour++) {
				$str .= '<td class=body2 '. ($this->validHours[$day][$hour]? 'style="background-color: green"' : '') ."></td>";
			}
			$str .= "</tr>";
		}
		$str .= '</table>';
		return $str;
	}

	function parseAllowedTimes() {
		$rangeArray = explode(',', $this->allowedtimes);

		$days_array = array( 'S'=>0, 's'=>0, 'M'=>1, 'm'=>1, 'T'=>2, 't'=>2, 'W'=>3, 'w'=>3, 'R'=>4, 'r'=>4, 'F'=>5, 'f'=>5, 'Y'=>6, 'y'=>6 );
		for ($day = 0; $day < 7; $day++) {
			for ($hour = 0; $hour<24; $hour++) {
				$this->validHours[$day][$hour] = false;
			}
		}

		foreach ($rangeArray as $val) {
			for ($i=0; $i<7; $i++) {
				$days[$i] = false;
			}
			for ($i=0; $i<24; $i++) {
				$hours[$i] = false;
			}
			$inRange = false;
			$invalid = false;
			$lastHour = false;
			$lastDay = false;

			for ($i=0; $i<strlen($val); $i++) {
				if (preg_match('/[MmTtWwRrFfYySs]/', $val{$i})) {
					if ($lastHour !== false) { //days should always come before hours
						$invalid = true;
						break;
					}
					if (!$lastDay || !$inRange) {
						$days[$days_array[$val{$i}]] = true;
						$lastDay = $val{$i};
					} else { //$lastDay && $inRange && !$lastHour eg. M-?
						$j=$days_array[$lastDay];
						do {
							$days[$j] = true;
							$j = ($j+1)%7;
						} while ($j != ($days_array[$val{$i}]+1)%7);
						$lastDay = $val{$i};
						$inRange = false;
					}
				} elseif (preg_match('/\-/', $val{$i})) {
					if (!($lastHour !== false || $lastDay) || $inRange) { //We need to have something preceding the hyphen and it can't be a hyphen
						$invalid = true;
						break;
					} else {
						$inRange = true;
					}
				} elseif (preg_match('/\d/', $val{$i})) {
					$start = $i;
					while ($i < strlen($val)-1 && preg_match('/\d/', $val{$i+1})) {
						$i++;
					}
					$currentHour = substr($val, $start, $i+1-$start);
					if ($currentHour > 23) { //non-existant hour
						$invalid = true;
						break;
					}
					if (!$lastDay && $lastHour === false && !$inRange) { //start of input, hours given are for all days
						for ($j=0; $j<7; $j++) {
							$days[$j] = true;
						}
						$hours[$currentHour] = true;
						$lastHour = $currentHour;
					} elseif ($lastDay && $lastHour === false && !$inRange) {
						$hours[$currentHour] = true;
						$lastHour = $currentHour;
					} elseif ($lastHour !== false && $inRange) {
						$j = $lastHour;
						do {
							$hours[$j] = true;
							$j = ($j+1)%24;
						} while ($j != $currentHour);
						$lastHour = $currentHour;
						$inRange = false;
					} elseif ($lastHour === false && $inRange) { //M-23 is not a valid range
						$invalid = true;
						break;
					} else {//($lastHour && !$inRange) this should never happen but is here for completeness
						$invalid = true;
						break;
					}
				} else { //this should never happen but is here for completeness
					$invalid = true;
					break;
				}
			}
			if ($lastDay && $lastHour === false) { //all hours if none specified
				for ($j=0; $j<24; $j++) {
					$hours[$j] = true;
				}
			}
			foreach ($days as $day => $valid) {
				if ($valid) {
					foreach ($hours as $hour => $hourValid) {
						if ($hourValid) {
							$this->validHours[$day][$hour] = 1;
						}
					}
				}
			}
		}
		return $this->validHours;
	}

	function validateRange($val) {
		$invalid = false;
		$inRange = false;
		$lastHour = false;
		$lastDay = false;
		for ($i=0; $i<strlen($val); $i++) {
			if (preg_match('/[MmTtWwRrFfYySs]/', $val{$i})) {
				if ($lastHour !== false) { //days should always come before hours
					$invalid = true;
					break;
				}
				if (!$lastDay || !$inRange) {
					$lastDay = $val{$i};
				} else { //$lastDay && $inRange && !$lastHour eg. M-?
					$lastDay = $val{$i};
					$inRange = false;
				}
			} elseif (preg_match('/\-/', $val{$i})) {
				if (!($lastHour !== false || $lastDay) || $inRange) { //We need to have something preceding the hyphen and it can't be a hyphen
					$invalid = true;
					break;
				} else {
					$inRange = true;
				}
			} elseif (preg_match('/\d/', $val{$i})) {
				$start = $i;
				while ($i < strlen($val)-1 && preg_match('/\d/', $val{$i+1})) {
					$i++;
				}
				$currentHour = substr($val, $start, $i+1-$start);
				if ($currentHour > 23) { //non-existant hour
					$invalid = true;
					break;
				}
				if (!$lastDay && $lastHour === false && !$inRange) { //start of input, hours given are for all days
					$lastHour = $currentHour;
				} elseif ($lastDay && $lastHour === false && !$inRange) {
					$lastHour = $currentHour;
				} elseif ($lastHour !== false && $inRange) {
					$lastHour = $currentHour;
					$inRange = false;
				} elseif ($lastHour === false && $inRange) { //M-23 is not a valid range
					$invalid = true;
					break;
				} else {//($lastHour && !$inRange) this should never happen but is here for completeness
					$invalid = true;
					break;
				}
			} else { //this should never happen but is here for completeness
				$invalid = true;
				break;
			}
		}
		return !$invalid;
	}
}
