<?


class messages{
	public $msgs;

	function __construct(){
		settype($this->msgs,"array");
	}

	function clearMsgs(){
		$this->msgs = array();
	}

	function addMsg($msg){
		$this->msgs[] = $msg;
	}

	function get(){
		$ret = "";
		if(count($this->msgs)){
			$ret = "<table width=100%>";
			foreach($this->msgs as $msg)
				$ret .= "<tr><td class=msg align=center>$msg</td></tr>";
			$ret .= "</table>";
		}
		return $ret;
	}
	function display(){
		print $this->get();
	}
}

