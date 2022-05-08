<?

// TODO: Write another version of call were you also pass in the name of the
//       method so we can deal with <method>? and <method>! more gracefully

class RubyObject
{
	private $reference_name;

	public function RubyObject($reference_name)
	{
		$this->reference_name = $reference_name;
	}
	
	public function __call($method, $arguments)
	{
		if($arguments != null)
		{
			array_unshift($arguments, $this->reference_name, $method);
			$result = call_user_func_array("ruby_callback", $arguments);
		}
		else
		{
			$arguments = array();
			array_unshift($arguments, $this->reference_name, $method);
			$result = call_user_func_array("ruby_callback", $arguments);
		}
	
/* 		echo $result["type"]."\n"; */
	
		if($result["type"] == "wrapped")
		{
			return new RubyObject($result["result"]);
		}
		else
		{
			return $result["result"];
		}
	}
}

?>