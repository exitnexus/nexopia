<?

	$login=1;

	require_once("include/general.lib.php");



class mancala{
	public $board;
	public $score;
	public $players;

	function __construct($game = 0){
		global $cache;

		if($game){
			$save = $cache->get("mancala-$game");
			if($save){
				$this->board = $save['board'];
				$this->score = $save['score'];
				$this->players = $save['players'];
			}
		}else
			$this->newGame();
	}

	function newGame($players){
		$this->board = array(4,4,4,4,4,4,
							 4,4,4,4,4,4);
		$this->score = array(0, 0);
		$this->players = $players;
	}

	function validMove($side, $move){
		return ($this->board[6*$side + $move] > 0);
	}

	function doMove($side, $move){
		$pos = 6 * $side + $move;
		$num = $this->board[$pos];
		$this->board[$pos] = 0;

		while(1){
			$pos++;
			$pos = $pos % 12;

			$curside = $pos/6;

			if(($pos == 0 || $pos == 6) && $side != $curside){ //scored
				$score[$side]++;
				$num--;
				if(!$num)
					return $side; //finished by scoring, take another turn
			}
			$this->board[$pos]++;
			$num--;

			if(!$num){
				if($curside == $side && $this->board[$pos] == 1){ //steal opponents, and score
					$this->board[$pos] = 0;
					$this->score[$side]++;

					$this->score[$side] += $this->board[11 - $pos];
					$this->board[11 - $pos] = 0;
				}

				return !$side;
			}
		}
	}
}




	incHeader();

	echo "<table>";

	echo "<tr>";
	echo "<td class=body rowspan=2>$score[0]</td>";
	for($i=5; $i >= 0; $i--)
		echo "<td class=body>$board[$i]</td>";
	echo "<td class=body rowspan=2>$score[0]</td>";
	echo "</tr>";

	echo "<tr>";
	for($i=6; $i < 12; $i++)
		echo "<td class=body>$board[$i]</td>";
	echo "</tr>";

	echo "</table>";



	incFooter();


