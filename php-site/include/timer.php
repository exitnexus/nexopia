<?

class timer{
	public $times = array();
	public $names = array();
	public $num = 0;

	function __construct($name = false){
		if($name !== false)
			$this->start($name);
	}

	function start($name = false){
		$this->times = array();
		$this->names = array();
		$this->times[] = gettime();
		$this->names[] = $name;
		$this->num = 0;
	}

	function lap($name = false){
		$this->names[] = $name;
		$this->times[] = gettime();

		return str_pad(number_format(($this->times[++$this->num] - $this->times[$this->num-1])/10, 3),13,' ',STR_PAD_LEFT) . " ms - " . $this->names[$this->num-1] . "\n";
	}

	function stop(){
		$this->times[] = gettime();
		return	str_pad(number_format(($this->times[++$this->num] - $this->times[$this->num-1])/10, 3),13,' ',STR_PAD_LEFT) . " ms - " . $this->names[$this->num-1] . "\n" .
				str_pad(number_format(($this->times[$this->num] - $this->times[0])/10, 3),13,' ',STR_PAD_LEFT) . " ms - total\n";
	}

	function reset(){
		$this->times = array();
		$this->names = array();
		$this->num = 0;
	}

	function dump(){
		$str = "";
		for($i=1; $i <= $this->num; $i++)
			$str .= $this->names[$i-1] . ":	" . number_format(($this->times[$i] - $this->times[$i-1])/10,2) . " ms\n";

		return $str;
	}
}

