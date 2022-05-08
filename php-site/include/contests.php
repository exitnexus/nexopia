<?

class contests {

	public $db;

/*
tables:
 -contests
 -contestentries
*/

	function __construct( & $db ){
		$this->db = & $db;
	}

	function addContest($name, $end, $anon, $content, $final){
		$this->db->prepare_query("INSERT INTO contests SET name = ?, end = ?, content = ?, final = ?, anonymous = ?", $name, $end, $content, $final, ($anon ? 'y' : 'n'));
	}

	function updateContest($id, $name, $end, $anon, $content, $final){
		$this->db->prepare_query("UPDATE contests SET name = ?, end = ?, content = ?, final = ?, anonymous = ? WHERE id = ?", $name, $end, $content, $final, ($anon ? 'y' : 'n'), $id);
	}


	function getContest($id){
		$res = $this->db->prepare_query("SELECT content, end, anonymous FROM contests WHERE id = ?", $id);
		$line = $res->fetchrow();

		if(!$line)
			return "Bad Contest";

		if($line['end'] < time())
			return "This contest has finished.";

		return $line;
	}

	function getContestFinal($id){
		$res = $this->db->prepare_query("SELECT final, end FROM contests WHERE id = ?", $id);
		$line = $res->fetchrow();

		if(!$line)
			return "Bad Contest";

		if($line['end'] < time())
			return "This contest has finished.";

		return $line['final'];
	}

	function getContests(){
		$res = $this->db->prepare_query("SELECT * FROM contests ORDER BY id");
		return $res->fetchrowset();
	}

	function addEntry($id, $userid, $contact){
		$this->db->prepare_query("INSERT IGNORE INTO contestentries SET contestid = ?, userid = ?, contact = ?", $id, $userid, $contact);

		if($this->db->affectedrows())
			$this->db->prepare_query("UPDATE contests SET entries = entries + 1 WHERE id = ?", $id);

		$res = $this->db->prepare_query("SELECT final FROM contests WHERE id = ?", $id);
		return $res->fetchfield();
	}

	function chooseWinner($id, $num){
		$res = $this->db->prepare_query("SELECT userid, contact FROM contestentries WHERE contestid = ? ORDER BY RAND() LIMIT #", $id, $num);

		$rows = array();
		while($line = $res->fetchrow())
			$rows[] = $line;

		return $rows;
	}

}
