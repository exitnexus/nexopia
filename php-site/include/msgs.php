<?


class messages{
	var $msgs;
	
	function messages(){
		settype($this->msgs,"array");
	}
	
	function addMsg($msg){
		$this->msgs[] = $msg;
	}
	
	function display(){
		if(count($this->msgs)>0){
			echo "<table width=100%>";
			foreach($this->msgs as $msg)
				echo "<tr><td class=msg align=center>$msg</td></tr>";
			echo "</table>";
		}
	}
}

