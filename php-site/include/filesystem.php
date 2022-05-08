<?

class filesystem {
	public $db; // fileupdates, fileservers
	public $server;

	function __construct( & $db, $server){
		$this->server = $server;
		$this->db = & $db;
	}

	function add($filename){
		$this->logaction('add', $filename);
	}
	function update($filename){
		$this->logaction('update', $filename);
	}
	function delete($filename){
		$this->logaction('delete', $filename);
	}

	function logaction($action, $filename){
		$this->db->prepare_query("INSERT INTO fileupdates SET action = ?, file = ?, server = ?, time = #", $action, $filename, $this->server, time());
	}

	function prunedb(){
		$res = $this->db->query("SELECT MIN(queueposition) FROM fileservers");

		$min = $res->fetchfield();

		$this->db->prepare_query("DELETE FROM fileupdates WHERE id < #", $min);
	}

	function getCommands($server){
		global $staticRoot;

		$res = $this->db->prepare_query("SELECT queueposition FROM fileservers WHERE server = ?", $server);
		$fileserver = $res->fetchrow();

		if(!$fileserver){
			trigger_error("Server '$server' does not exist in fileservers table", E_USER_WARNING);

			return;
		}

		$position = $fileserver['queueposition'];

		$res = $this->db->prepare_query("SELECT * FROM fileupdates WHERE id > # ORDER BY id ASC LIMIT 1000", $position);
		$files = $res->fetchrowset();
		if (!$files)
			return;

		$finalposition = $position;

		foreach($files as $line){
			$finalposition = $line['id'];

//			if($line['server'] == $server)
//				continue;

			switch($line['action']){
/*
				case "add":
					echo "mkdir -p -m777 $docRoot" . dirname($line['file']) . ";\n";
					echo "if [ ! -f $docRoot$line[file] ]\nthen\n";
					echo "wget -nv -O $docRoot$line[file] http://www.nexopia.com$line[file];\n";
					echo "fi\n";

					break;

				case "update":
					echo "wget -nv -O $docRoot$line[file] http://www.nexopia.com$line[file];\n";
					break;
*/
				case "delete":
					echo "rm -f $staticRoot$line[file]\n";
					break;
			}
		}

		if($finalposition != $position)
			$this->db->prepare_query("UPDATE fileservers SET queueposition = # WHERE server = ?", $finalposition, $server);
	}
}
