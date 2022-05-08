<?

class filesystem {
	var $db; // fileupdates, fileservers
	var $server;

	function filesystem( & $db, $server){
		$this->server = $server;
		$this->db = & $db;
	}

	function add($filename){
		$this->db->prepare_query("INSERT INTO fileupdates SET action = 'add', file = ?, server = ?, time = ?", $filename, $this->server, time());
	}
	function update($filename){
		$this->db->prepare_query("INSERT INTO fileupdates SET action = 'update', file = ?, server = ?, time = ?", $filename, $this->server, time());
	}
	function delete($filename){
		$this->db->prepare_query("INSERT INTO fileupdates SET action = 'delete', file = ?, server = ?, time = ?", $filename, $this->server, time());
	}

	function prunedb(){
		$this->db->query("SELECT MIN(queueposition) FROM fileservers");

		$min = $this->db->fetchfield();

		$this->db->prepare_query("DELETE FROM fileupdates WHERE id < ?", $min);
	}

	function getCommands($server){
		global $docRoot;

		$this->db->prepare_query("SELECT queueposition FROM fileservers WHERE server = ?", $server);

		if(!$this->db->numrows()){
			trigger_error("Server '$server' does not exist in fileservers table", E_USER_WARNING);

			return;
		}

		$position = $this->db->fetchfield();

		$this->db->prepare_query("SELECT * FROM fileupdates WHERE id > ? ORDER BY id ASC LIMIT 1000", $position);

		if($this->db->numrows() == 0)
			break;

		$files = array();
		while($line = $this->db->fetchrow())
			$files[] = $line;

		$finalposition = $position;

		foreach($files as $line){
			$finalposition = $line['id'];

//			if($line['server'] == $server)
//				continue;

			switch($line['action']){
				case "add":
					echo "mkdir -p -m777 $docRoot" . dirname($line['file']) . ";\n";
					echo "if [ ! -f $docRoot$line[file] ]\nthen\n";
					echo "wget -nv -O $docRoot$line[file] http://www.nexopia.com$line[file];\n";
					echo "fi\n";

					break;

				case "update":
					echo "wget -nv -O $docRoot$line[file] http://www.nexopia.com$line[file];\n";
					break;

				case "delete":
					echo "rm -f $docRoot$line[file]\n";
					break;
			}
		}

		if($finalposition != $position)
			$this->db->prepare_query("UPDATE fileservers SET queueposition = ? WHERE server = ?", $finalposition, $server);
	}


/*
	function process(){
		global $docRoot;

		$this->db->prepare_query("SELECT queueposition FROM fileservers WHERE server = ?", $this->server);

		if(!$this->db->numrows()){
			trigger_error("Server does not exist in fileservers table", E_USER_WARNING);

			$this->db->prepare_query("INSERT INTO fileservers (server, queueposition) SELECT ?, MAX(id) FROM fileupdates", $this->server);

			return;
		}

		$position = $this->db->fetchfield();

		while(1){
			$this->db->prepare_query("SELECT * FROM fileupdates WHERE id > ? ORDER BY id ASC LIMIT 100", $position);

			if($this->db->numrows() == 0)
				break;

			$files = array();
			while($line = $this->db->fetchrow())
				$files[] = $line;

			$finalposition = $position;

			foreach($files as $line){
				$finalposition = $line['id'];

				if($line['server'] == $this->server)
					continue;

				switch($line['action']){
					case "add":
						if(file_exists($docRoot . $line['file']))
							break;
					case "update":

						$dirs = explode("/", dirname($line['file']));

						umask(0);

						$basedir = $docRoot;
						foreach($dirs as $dir){
							if(!is_dir("$basedir/$dir"))
								@mkdir("$basedir/$dir",0777);
							$basedir .= "/$dir";
						}

						$remotefile = "http://" . $line['server'] . $line['file'];
						$localfile = $docRoot . $line['file'];

						$remote = @fopen($remotefile,'r');
						if(!$remote){
							trigger_error("error: Can't open remote file: $remotefile", E_USER_WARNING);
							break;
						}

						$local = @fopen($localfile,'w');
						if(!$local){
							fclose($remote);
							trigger_error("error: Can't open local file: $localfile", E_USER_WARNING);
							break;
						}

						while($buf = fread($remote,4096))
							fwrite($local,$buf);

						fclose($remote);
						fclose($local);

						break;
					case "delete":
						if(file_exists($docRoot . $line['file']))
							unlink($docRoot . $line['file']);
						break;
				}
			}

			if($finalposition != $position)
				$this->db->prepare_query("UPDATE fileservers SET queueposition = ? WHERE server = ?", $finalposition, $this->server);

			$position = $finalposition;
		}
	}
*/

}



