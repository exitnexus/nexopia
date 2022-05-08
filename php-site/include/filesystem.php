<?

class filesystem {
	var $db; // fileupdates, fileservers
	var $server;

	function filesystem($server){
		global $db;

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

	function process(){
		global $docRoot;

		$this->db->prepare_query("SELECT queueposition FROM fileservers WHERE server = ?", $this->server);

		if(!$this->db->numrows()){
			trigger_error("Server does not exist in fileservers table", E_USER_WARNING);

			$this->db->prepare_query("INSERT INTO fileservers (server, queueposition) SELECT ?, MAX(id) FROM fileupdates", $this->server);

			return;
		}

		$position = $this->db->fetchfield();

		$this->db->prepare_query("SELECT * FROM fileupdates WHERE id > ? ORDER BY id ASC", $position);

		$finalposition = $position;

		while($line = $this->db->fetchrow()){
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

					$remote = fopen($remotefile,'r');
					if(!$remote){
						trigger_error("error: Can't open remote file: $remotefile", E_USER_WARNING);
						break;
					}

					$local = fopen($localfile,'w');
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
	}
}
