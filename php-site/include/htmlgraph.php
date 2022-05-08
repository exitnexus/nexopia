<?


class htmlgraph {
	public $type; //bar, stacked, layered
	public $width;
	public $height;
	
	public $title;
	public $rows;
	public $xname;
	public $yname;
	public $yvals;
	public $showxticks;
	public $showyticks;
	public $spacing;
	
	public $colors;


	function __construct($width, $height, $type = 'bar'){
		$this->type = $type;
		$this->width = $width;
		$this->height = $height;
		
		$this->title = '';
		$this->rows = array();
		$this->xname = '';
		$this->yname = '';
		$this->yvals = null;
		
		$this->showxticks = true;
		$this->showyticks = true;
		
		$this->spacing = 1;
		
		$this->colors = array( '#FF0000', '#00FF00', '#0000FF', '#FFFF00', '#FF00FF', '#00FFFF',
		                       '#f4c6d8', '#b41a03', '#123465', '#4e1b7c', '#efab01', '#4ab356');
	}

	function setTitle($title){
		$this->title = $title;
	}
	
	function addRow($data, $name = ""){
		if($name)
			$this->rows[$name] = $data;
		else
			$this->rows[] = $data;
	}

	function setXName($xname){
		$this->xname = $xname;
	}

	function setYName($yname){
		$this->yname = $yname;
	}
	
	function setYVals($data){
		$this->yvals = $data;
	}

	function setSpacing($spacing){
		$this->spacing = $spacing;
	}

	function draw(){
		$rownames = array_keys($this->rows);
		$numcols = count($this->rows[$rownames[0]]);
		$numrows = count($this->rows);


		$str = "";
		$str .= "<table cellspacing=0 cellpadding=0>";
		
	//title
		if($this->title){
			$str .= "<tr>";
			if($this->yname)
				$str .= "<td></td>";
			$str .= "<td colspan=2></td><td align=center>$this->title</td></tr>";
		}
		
		$str .= "<tr>";
		
	//y name
		if($this->yname){
			$str .= "<td width=30 align=center>";
			$str .= implode("<br>", str_split($this->yname));
			$str .= "</td>";
		}
		
	//y scale	
		$str .= "<td valign=bottom rowspan=2>";

		$numticks = 10; //floor($this->height/30);

		$ints = true;
		$minval = $maxval = $this->rows[$rownames[0]][0];

		foreach($this->rows as $row){
			foreach($row as $val){
				if($val > $maxval)
					$maxval = $val;
				if(!is_int($val))
					$ints = false;
			}
		}
		
		if($ints && $maxval < floor($this->height/20)){
			$numticks = $maxval;
		}else{
			$multiple = 0;
			$val = $maxval;
			while($val > 100){
				$multiple++;
				$val /= 10;
			}

			if($val < 20)
				$interval = 2;
			elseif($val < 50)
				$interval = 5;
			else
				$interval = 10;

			for($i = 0; $i < $multiple; $i++)
				$interval *= 10;

			$maxval = ceil(ceil($maxval/$interval)*$interval);

			if($multiple || $interval == 10)
				$ints = true;
		}


		$height = round($this->height/($numticks+1));


		$str .= "<table cellspacing=0 cellpadding=0>";
		for($i = $numticks; $i >= 0; $i--)
			$str .= "<tr><td height=$height valign=bottom align=right>" . number_format(($i*($maxval/($numticks))), ($ints ? 0 : 1)) . "&nbsp;&nbsp;&nbsp;<div style=\"width: 10; height: 1; background-color: #000000\"></div></td></tr>";
		$str .= "</table>";

		$str .= "</td>";

		$str .= "<td valign=bottom><div style=\"width: 1; height: " . ($height*$numticks) . "; background-color: #000000\"></div></td>";

	//graph body
		$str .= "<td valign=bottom>";
		
		$str .= "<table cellspacing=0 cellpadding=0>";
		
		$blockwidth = floor(($this->width - ($this->showxticks ? $this->spacing*($numrows-1) : 0))/($numcols*($this->type == 'layered' ? 1 : $numrows)));
		
		$str .= "<tr>";
		for($i = 0; $i < $numcols; $i++){
			if($i != 0 && $this->showxticks && $this->spacing)
				$str .= "<td><div style=\"width: $this->spacing\"></div></td>";

			switch($this->type){
				case 'bar':
				case 'stacked':
					$str .= "<td valign=bottom>";
					for($j = 0; $j < $numrows; $j++){
						if($j && $this->type == 'bar')
							$str .= "</td><td valign=bottom>";
		
						$thisheight = (isset($this->rows[$rownames[$j]][$i]) ? round($height*$numticks*$this->rows[$rownames[$j]][$i]/$maxval) : 0);
						$str .= "<div style=\"width:$blockwidth; height: $thisheight; background-color:" . $this->colors[$j] . "\"></div>";
					}
					$str .= "</td>";

					break;

				case 'layered':
					$h = 0;
					$heights = array();

					for($j = $numrows-1; $j >= 0; $j--){
						$heights[$j] = $h;

						$h = (isset($this->rows[$rownames[$j]][$i]) ? round($height*$numticks*$this->rows[$rownames[$j]][$i]/$maxval) : 0);
					}

					$str .= "<td valign=bottom>";
					for($j = 0; $j < $numrows; $j++){
						$thisheight = (isset($this->rows[$rownames[$j]][$i]) ? round($height*$numticks*$this->rows[$rownames[$j]][$i]/$maxval) : 0);
						$str .= "<div style=\"width:$blockwidth; height: " . ($thisheight - $heights[$j]) . "; background-color:" . $this->colors[$j] . "\"></div>";
					}
					$str .= "</td>";
			}
		}
		$str .= "</tr>";
		
		$str .= "</table>";
		
		$str .= "</td>";


	//legend
		if($numrows > 1){
			$str .= "<td>";
			$str .= "<table border=1 cellspacing=0 cellpadding=0><tr><td>";

			$str .= "<table>";
			foreach($rownames as $k => $name)
				$str .= "<tr><td><div style=\"border: 1px solid #000000; width: 12; height: 12; background-color: " . $this->colors[$k]. ";\"></div></td><td>$name</td></tr>";
				
			$str .= "</table>";
			
			$str .= "</td></tr></table>";
			$str .= "</td>";
		}
		
		$str .= "</tr>";
		
		
	//x line
		$str .= "<tr>";
		if($this->yname)
			$str .= "<td></td>";
		$str .= "<td colspan=2 height=1><div style=\"width: 100%; height: 1; background-color: #000000\"></div></td>";
		$str .= "</tr>";


	//x axis
	
		if($this->showxticks){
			$str .= "<tr>";
			if($this->yname)
				$str .= "<td></td>";
			$str .= "<td></td><td colspan=2>";
	
			$str .= "<table width=100% cellspacing=0 cellpadding=0>";	
			$str .= "<tr>";

			if($this->yvals === null || count($this->yvals) == $numcols){
				for($i = 0; $i < $numcols; $i++){
					$str .= "<td valign=top><div style=\"width: 1; height:10; background-color: #000000\"></div></td>";
					$str .= "<td width=" . ($blockwidth*($this->type == 'layered' ? 1 : $numrows)) . " align=center>" . ($this->yvals ? $this->yvals[$i] : '') . "</td>";
				}
			}else{
				foreach($this->yvals as $yval){
					list($name, $width) = $yval;
					$str .= "<td valign=top width=1><div style=\"width: 1; height:10; background-color: #000000\"></div></td>";
					$str .= "<td width=" . ($blockwidth*($this->type == 'layered' ? 1 : $numrows)*$width + ($width-1)*$this->spacing-2) . " align=center>$name</td>";
				}
			}
			$str .= "<td valign=top><div style=\"width: 1; height:10; background-color: #000000\"></div></td>";
			
			$str .= "</tr>"; 
			$str .= "</table>";
	
			
			$str .= "</td>";
			$str .= "</tr>";
		}


	//x title
		if($this->xname){
			$str .= "<tr>";
			if($this->yname)
				$str .= "<td></td>";
			$str .= "<td colspan=2></td><td align=center>$this->xname</td></tr>";
			$str .= "</tr>";
		}
		
		$str .= "</table>";

		return $str;
	}
}
