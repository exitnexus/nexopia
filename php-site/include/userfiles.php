<?
	class userfiles {
		public $vuid, $ruid, $euid, $vuname, $runame, $euname, $userdir, $canWrite, $isAdmin, $config, $ttlsize;

		function __construct($vuid) {
			global $userData, $mods, $staticRoot, $cache, $config, $usersdb;

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
			}

			$this->ttlsize = $this->getTotalSize('/');
		}

		function userPerms () {
			return array(
				'ruid'   => $this->ruid,   'euid'   => $this->euid,   'vuid'   => $this->vuid,
				'runame' => $this->runame, 'euname' => $this->euname, 'vuname' => $this->vuname
			);
		}

		function rootExists ($root, $type) {
			$path = "{$this->userdir}$root";

			if ($type == 'any')
				return file_exists($path);
			elseif ($type == 'any-strict')
				return file_exists($path) and (
					(is_dir($path) and substr($path, -1) == '/') or (is_file($path) and substr($path, -1) != '/')
				) ? true : false;
			elseif ($type == 'file')
				return file_exists($path) and is_file($path);
			elseif ($type == 'file-strict')
				return file_exists($path) and is_file($path) and substr($path, -1) != '/';
			elseif ($type == 'folder')
				return file_exists($path) and is_dir($path);
			elseif ($type == 'folder-strict')
				return file_exists($path) and is_dir($path) and substr($path, -1) == '/';
		}

		function getHandle ($root) {
			return $this->rootExists($root, 'any-strict') ? new userfile($this, $root) : false;
		}

		function rootParent ($root) {
			return substr($root, 0, strrpos($root, '/', -2) + 1);
		}

		function listFolders ($root, $recurse = false) {
			$folders = array();

			$dh = opendir("{$this->userdir}$root");
			while ( ($fh = readdir($dh)) !== false ) {
				if (!is_dir("{$this->userdir}$root$fh") or $fh == '.' or $fh == '..') continue;

				$folders[] = "$root$fh/";
				if ($recurse)
					foreach ($this->listFolders("$root$fh/", true) as $folder)
						$folders[] = $folder;
			}
			closedir($dh);

			natcasesort($folders);
			return $folders;
		}

		function listFiles ($root) {
			$files = array();

			$dh = opendir("{$this->userdir}$root");
			while ( ($fh = readdir($dh)) !== false ) {
				if (is_file("{$this->userdir}$root$fh"))
					$files[] = "$root$fh";
			}
			closedir($dh);

			natcasesort($files);
			return $files;
		}

		function makeFolder ($path) {
			umask(0);
			mkdir("{$this->userdir}$path", 0775);
		}

		function uploadFile($tmpfile, $dest) {
			umask(0);
			$dest = "{$this->userdir}$dest";
			move_uploaded_file($tmpfile, $dest);
			chmod($dest, 0775);
		}

		function touchFile($root) {
			file_put_contents("{$this->userdir}$root", '');
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
		public $parent, $fileInfo, $fsPath, $root, $basename, $modified, $size, $type;

		function __construct (&$parent, $path) {
			$this->parent = $parent;

			clearstatcache();

			if ($path !== false) {
				$this->fsPath 		= "{$this->parent->userdir}{$path}";
				$this->root 		= $path;
				$this->basename 	= ($path == '/' ? '/' : substr($path, strrpos($path, '/', -2) + 1));
				$this->modified 	= filemtime($this->fsPath);
				$this->type 		= (substr($path, -1) == '/' ? 'Folder' : 'File');
				$this->size 		= $this->type == 'File' ? filesize($this->fsPath) : $this->parent->getTotalSize($this->root);

				$this->fileInfo = array(
					'root'		=> $this->root,
					'fsPath'	=> $this->fsPath,
					'basename'	=> $this->basename,
					'modified'	=> $this->modified,
					'size'		=> $this->size,
					'type'		=> $this->type
				);
			}
		}

		function rename ($renameTo) {
			rename($this->fsPath, "{$this->parent->userdir}$renameTo");
		}

		function delete () {
			if ($this->type == 'File')
				unlink($this->fsPath);
			else
				rmdirrecursive($this->fsPath);
		}

		function fileContents ($newContents = null) {
			// .remmargorp naem yrev a si maharG
			if (is_null($newContents))
				return file_get_contents($this->fsPath);
			else {
				file_put_contents($this->fsPath, $newContents);

				clearstatcache();
				$this->modified 	= filemtime($this->fsPath);
				$this->size 		= $this->type == 'File' ? filesize($this->fsPath) : $this->parent->getTotalSize($this->root);
			}
		}

		function readfile () {
			readfile("{$this->parent->userdir}{$this->root}");
		}
	}
?>
