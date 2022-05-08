<?

if(!class_exists('RubyObject')) { 
class RubyObject {
	private $reference_name;

	function __construct($reference_name){
		$this->reference_name = $reference_name;
	}
	
	//automatically called by php for any function that doesn't exist.
	public function __call($method, $args){
		return $this->_send($method, $args);
	}
	
	public function __set($method, $arg){
		return $this->_send("${method}=", array($arg));
	}
	public function __get($method){
		return $this->_send($method, array());
	}
	public function __isset($method){
		return $this->_send($method, array());
	}
	public function __unset($method){
		return $this->__set($method, null);
	}

	//can be used to call any ruby function, include ones that end in ! and ?
	function _send($method, $args){
		if($args == null)
			$args = array();

		// we get the current timeout because ruby is going to 'steal' the SIGPROF
		// signal handler and we should restore the time limit after we're done.
		$prev_timeout = ini_get("max_execution_time");
		if ($prev_timeout)
			$prev_timeout_at = time();
		array_unshift($args, $this->reference_name, $method);
		$result = call_user_func_array("ruby_callback", $args);
		if ($prev_timeout)
		{
			$now = time();
			set_time_limit(max(1, $prev_timeout - ($now - $prev_timeout_at)));
		}

		if(isset($result["error"]) && $result["error"])
			trigger_error("Error caught from ruby: $result[error] calling $method(" . join($args, ',') . ")", E_USER_ERROR);

		if($result["type"] == "wrapped"){
			return new RubyObject($result["result"]);
		}else {
			// Unwrap any proxy objects we got back
			return $this->unwrap_proxy($result["result"]);
		}
	}

	/* If we are passing back Ruby arrays or hash tables containing
	 * Ruby objects, we have to cheat and pass them back as magic
	 * strings.  This is because if we go from Ruby to Php to Ruby,
	 * we can't call back to Php.  Instead, we pass back strings as
	 * #!RAP:xxxx where xxxx is the generated class name for the
	 * proxy object (see RAP.rb#register_proxy_object).  This
	 * function unwraps those objects.  It does so recursively to
	 * handle arrays of arrays, etc.
	 */
	private function unwrap_proxy($obj){
		if (is_string($obj)) {
			if (strpos($obj, "#!RAP:") === 0){
				// Unwrap
				return new RubyObject(substr($obj, 6)); // Anything after #!RAP:
			}else{
				return $obj;
			}
		}else if (is_array($obj)) {
			$result = array();
			foreach($obj as $key => $value){
				$result[$key] = $this->unwrap_proxy($value);
			}
			return $result;
		}else{
			return $obj;
		}
	}

}
}

