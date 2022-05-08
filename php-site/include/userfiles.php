<?
	class userfiles {
		public $db, $tmpcache;
		public $vuid, $ruid, $euid, $vuname, $runame, $euname, $userdir, $canWrite, $isAdmin, $config, $ttlsize;

		function __construct($vuid) {
			global $userData, $mods, $staticRoot, $cache, $config, $usersdb, $mogfs;

			$this->db = $usersdb;
			$this->mogfs = $mogfs;

			$this->config = $config;
			$this->config['filesRestrictExts'] = split(' ', $this->config['filesRestrictExts']);
			$this->config['validPerms'] = array('private', 'friends', 'loggedin', 'public');

			$this->vuid = $vuid;
			$this->vuname = getUserName($this->vuid);

			if ($userData['loggedIn'] === false) {
				$this->ruid = $this->runame = $this->euid = $this->euname = -1;
				if ($this->vuid == $userData['userid'])
					$this->vuid = $this->vuname = -1;
			}
			else {
				$this->ruid = $userData['userid'];
				$this->runame = getUserName($this->ruid);

				$this->euid = $mods->isAdmin($this->ruid, 'editfiles') ? $this->vuid : $this->ruid;
				$this->euname = getUserName($this->euid);
			}

			$this->canWrite = $this->euid == $this->vuid ? 1 : 0;
			$this->isAdmin = ($mods->isAdmin($this->ruid, 'editfiles') and $this->vuid != $this->ruid);
			$this->userdir = "{$staticRoot}{$this->config['basefiledir']}" . floor($this->vuid / 1000);

			$sth = $usersdb->prepare_query('SELECT filesquota FROM users WHERE userid = %', $this->vuid);
			$this->config['maxTotal'] = (($row = $sth->fetchrow()) === false) ? 0 : $row['filesquota'];

			$info = getUserInfo($this->vuid);
			if ($info !== false && $info['state'] == 'active') {
				umask(0);
				if (!file_exists($this->userdir))
					mkdir($this->userdir, 0775);

				$this->userdir .= "/{$this->vuid}";

				if (!file_exists($this->userdir))
					mkdir($this->userdir, 0775);

				$sth = $this->db->prepare_query('SELECT COUNT(*) AS cnt FROM userfileslayout WHERE userid = % AND path = ? AND type = ?', $this->vuid, '/', 'folder');
				if ( ($cnt = $sth->fetchfield()) == 0)
					$this->db->prepare_query('INSERT IGNORE INTO userfileslayout SET userid = %, id = #, path = ?, type = ?', $this->vuid, $this->db->getSeqID($this->vuid, DB_AREA_FILES_LAYOUT), '/', 'folder');
			}

			$this->cacheAll();
			$this->ttlsize = $this->getTotalSize('/');
		}

		function userPerms () {
			return array(
				'ruid'   => $this->ruid,   'euid'   => $this->euid,   'vuid'   => $this->vuid,
				'runame' => $this->runame, 'euname' => $this->euname, 'vuname' => $this->vuname
			);
		}

		function cacheAll () {
			$this->tmpcache = array();

			$sth = $this->db->prepare_query('SELECT path, type, size FROM userfileslayout WHERE userid = %', $this->vuid);
			while ( ($row = $sth->fetchrow()) !== false )
				$this->tmpcache[$row['path']] = array('type' => $row['type'], 'size' => $row['size']);
		}

		function rootExists ($root, $type) {
			$path = "{$this->userdir}$root";

			$row = isset($this->tmpcache[$root]) ? $this->tmpcache[$root] : false;

			if ($type == 'any')
				return $row !== false;

			elseif ($type == 'any-strict') {
				if ($row === false)
					return false;

				return( ($row['type'] == 'folder' && substr($path, -1) == '/') || ($row['type'] == 'file' && substr($path, -1) != '/') );
			}

			elseif ($type == 'file')
				return $row !== false && $row['type'] == 'file';

			elseif ($type == 'file-strict')
				return $row !== false && $row['type'] == 'file' && substr($path, -1) != '/';

			elseif ($type == 'folder')
				return $row !== false && $row['type'] == 'folder';

			elseif ($type == 'folder-strict')
				return $row !== false && $row['type'] == 'folder' && substr($path, -1) == '/';
		}

		function getHandle ($root) {
			return $this->rootExists($root, 'any-strict') ? new userfile($this, $root) : false;
		}

		function rootParent ($root) {
			return substr($root, 0, strrpos($root, '/', -2) + 1);
		}

		function listFolders ($root, $recurse = false) {
			$folders = array();

			foreach ($this->tmpcache as $filepath => $row) {
				if (preg_match("/^\Q" . str_replace('/', "\E\\/\Q", $root) . "\E[^\/]+\/$/", $filepath)) {
					$folders[] = $filepath;
					if ($recurse)
						foreach ($this->listFolders($filepath, true) as $folder)
							$folders[] = $folder;
				}
			}

			natcasesort($folders);
			return $folders;
		}

		function listFiles ($root) {
			$files = array();

			foreach ($this->tmpcache as $filepath => $row) {
				if (preg_match("/^\Q" . str_replace('/', "\E\\/\Q", $root) . "\E[^\/]+$/", $filepath))
					$files[] = $filepath;
			}

			natcasesort($files);
			return $files;
		}

		function makeFolder ($path) {
			umask(0);
			mkdir("{$this->userdir}$path", 0775);
			$this->db->prepare_query('INSERT IGNORE INTO userfileslayout SET userid = %, id = #, path = ?, type = ?', $this->vuid, $this->db->getSeqID($this->vuid, DB_AREA_FILES_LAYOUT), "${path}/", 'folder');
			$this->tmpcache["${path}/"] = array('type' => 'folder', 'size' => 0);
		}

		function uploadFile($tmpfile, $dest) {
			$data = file_get_contents($tmpfile);
			
			$this->db->prepare_query('INSERT IGNORE INTO userfileslayout SET userid = %, id = #, path = ?, type = ?, size = #', $this->vuid, $this->db->getSeqID($this->vuid, DB_AREA_FILES_LAYOUT), $dest, 'file', filesize($tmpfile));
			$this->mogfs->add(FS_UPLOADS, "{$this->vuid}${dest}", $data);
			$this->tmpcache[$dest] = array('type' => 'file', 'size' => filesize($tmpfile));

			umask(0);
			$dest = "{$this->userdir}$dest";

			//$retval = move_uploaded_file($tmpfile, $dest);
			$destfd = fopen($dest, "w+");
			fwrite($destfd, $data);
			fflush($destfd);
			fclose($destfd);
			
			chmod($dest, 0775);
	}

		function touchFile($root) {
			$this->db->prepare_query('INSERT IGNORE INTO userfileslayout SET userid = %, id = #, path = ?, type = ?', $this->vuid, $this->db->getSeqID($this->vuid, DB_AREA_FILES_LAYOUT), $root, 'file');
			$this->mogfs->add(FS_UPLOADS, "{$this->vuid}${root}", ' ');
			file_put_contents("{$this->userdir}${root}", ' ');
			$this->tmpcache[$root] = array('type' => 'file', 'size' => 1);
		}

		function getTotalSize ($root) {
			if (! $this->rootExists($root, 'folder-strict'))
				return 0;

			$ttlsize = 0;
			foreach (array_merge(array('/'), $this->listFolders("/", true)) as $folderPath) {
				if (strpos($folderPath, $root) === false)
					continue;

				foreach ($this->listFiles($folderPath) as $file) {
					if ( ($handle = $this->getHandle($file)) !== false )
						$ttlsize += $handle->size;
				}
			}

			return $ttlsize;
		}
	}

	class userfile {
		public $parent, $fileInfo, $fsPath, $root, $basename, $size, $type;

		function __construct (&$parent, $path) {
			$this->parent = $parent;

			clearstatcache();

			if ($path !== false) {
				$this->fsPath 		= "{$this->parent->userdir}{$path}";
				$this->root 		= $path;
				$this->basename 	= ($path == '/' ? '/' : substr($path, strrpos($path, '/', -2) + 1));
				$this->type 		= (substr($path, -1) == '/' ? 'Folder' : 'File');

				if ($this->type == 'File')
					$this->size = $this->parent->tmpcache[$this->root]['size'];

				else
					$this->size = $this->parent->getTotalSize($this->root);

				$this->fileInfo = array(
					'root'		=> $this->root,
					'fsPath'	=> $this->fsPath,
					'basename'	=> $this->basename,
					'size'		=> $this->size,
					'type'		=> $this->type
				);
			}
		}

		function rename ($renameTo) {
			if ($this->type == 'File') {
				$this->parent->db->prepare_query(
					'UPDATE userfileslayout SET path = ? WHERE userid = % AND path = ?',
					$renameTo, $this->parent->vuid, $this->root
				);

				$this->parent->mogfs->move(FS_UPLOADS, "{$this->parent->vuid}{$this->root}", "{$this->parent->vuid}${renameTo}");
				rename($this->fsPath, "{$this->parent->userdir}${renameTo}");

				$old = $this->parent->tmpcache[$this->root];
				$this->parent->tmpcache[$renameTo] = array('type' => $old['type'], 'size' => $old['size']);
				unset($this->parent->tmpcache[$this->root]);
			}

			else {
				foreach ($this->parent->tmpcache as $filepath => $row) {
					if (preg_match("/^\Q" . str_replace('/', "\E\\/\Q", $this->root) . "\E/", $filepath)) {
						$newpath = str_replace('//', '/', str_replace($this->root, "${renameTo}/", $filepath));

						$this->parent->mogfs->move(FS_UPLOADS, "{$this->vuid}${filepath}", "{$this->parent->vuid}${newpath}");
						$this->parent->db->prepare_query(
							'UPDATE userfileslayout SET path = ? WHERE userid = % AND path = ?', $newpath, $this->parent->vuid, $filepath
						);

						$old = $this->parent->tmpcache[$filepath];
						$this->parent->tmpcache[$newpath] = array('type' => $old['type'], 'size' => $old['size']);
						unset($this->parent->tmpcache[$filepath]);
					}
				}

				rename($this->fsPath, "{$this->parent->userdir}${renameTo}");
			}
		}

		function delete () {
			if ($this->type == 'File') {
				$this->parent->db->prepare_query('DELETE FROM userfileslayout WHERE userid = % AND path = ?', $this->parent->vuid, $this->root);
				$this->parent->mogfs->delete(FS_UPLOADS, "{$this->parent->vuid}{$this->root}");
				unlink($this->fsPath);

				unset($this->parent->tmpcache[$this->root]);
			}

			else {
				foreach ($this->parent->tmpcache as $filepath => $row) {
					if (preg_match("/^\Q" . str_replace('/', "\E\\/\Q", $this->root) . "\E/", $filepath)) {
						$this->parent->mogfs->delete(FS_UPLOADS, "{$this->parent->vuid}${filepath}");
						unset($this->parent->tmpcache[$filepath]);
					}
				}

				$this->parent->db->prepare_query('DELETE FROM userfileslayout WHERE userid = % AND path LIKE ?', $this->parent->vuid, "{$this->root}%");
				rmdirrecursive($this->fsPath);
			}
		}

		function fileContents ($newContents = null) {
			if (is_null($newContents)) {
				if ( ($res = $this->parent->mogfs->fetch(FS_UPLOADS, "{$this->parent->vuid}{$this->root}")) !== false )
					return $res;

				return file_get_contents($this->fsPath);
			}

			else {
				$this->parent->mogfs->add(FS_UPLOADS, "{$this->parent->vuid}{$this->root}", $newContents);
				file_put_contents($this->fsPath, $newContents);

				$this->parent->db->prepare_query(
					'UPDATE userfileslayout SET size = # WHERE userid = % AND path = ?',
					($this->size = $this->parent->tmpcache[$this->root]['size'] = strlen($newContents)), $this->parent->vuid, $this->root
				);
			}
		}

		function readfile () {
			if ( ($res = $this->parent->mogfs->fetch(FS_UPLOADS, "{$this->parent->vuid}{$this->root}")) !== false )
				echo $res;
			else
				readfile($this->fsPath);
		}
	}

