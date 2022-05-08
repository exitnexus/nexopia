<?
	$login = 0;
	require_once('include/general.lib.php');
	require_once('include/userfiles.php');

	class pageUserFiles extends pagehandler {
		public $userfiles, $root, $errmsgs = array(), $config;
		public static $constToolbars = array(
			'upload'		=> 1,
			'newfiles'		=> 2,
			'newfolders'	=> 4,
			'all'			=> 7
		);

		function __construct () {
			global $userData, $usersdb;

			$vuid = getREQval('uid', 'integer', 0);
			if (! $vuid)
				$vuid = $userData['userid'];

			$userfiles = new userfiles($vuid);
			$this->userfiles = $userfiles;

			$this->config = $userfiles->config;

			if ($userfiles->ruid == -1 && $userfiles->vuid == -1)
				return $this->showError('You must login to your account in order to manage your files.');

			if ($userfiles->ruid == $userfiles->vuid && $userData['premiumexpiry'] < time())
				return $this->showError('The files section is a a Plus only feature. <a href="/plus.php" class="body">Find out how to get Plus</a>');

			$info = getUserInfo($userfiles->vuid);
			if (! $info || $info['state'] == 'frozen')
				return $this->showError('The user who\'s files you are trying to access does not exist.');

			$this->root = getREQval('root', 'string', '/');
			if (strlen($this->root) > 500)
				$this->root = '/';

			$this->root = $this->filenameAllowed($this->root);

			// we have a valid, existing path
			if ($this->root !== false && $userfiles->rootExists($this->root, 'any-strict')) {
				// keep the root folder and files within the root folder private
				if ($userfiles->vuid != $userfiles->euid && strpos($this->root, '/', 2) === false)
					return $this->showError('You do not have permission to view this file or folder.');
			}

			// path does not exist
			else
				return $this->showError('The file or folder requested could not be found.');

			// get the toplevel folder of the entire path and check permissions for it
			$path = array();
			if (preg_match('/\A(\/[^\/]+\/)/', $this->root, $path)) {
				$sth = $usersdb->prepare_query('SELECT permissions FROM userfiles WHERE userid = % AND folderpath = ?', $userfiles->vuid, $path[1]);
				$row = $sth->fetchrow();
				$perms = $row['permissions'];

				if ($userfiles->vuid != $userfiles->euid) {
					switch ($perms) {
						case 'private':
							return $this->showError('You do not have permission to view this user\'s files.');
							break;

						case 'loggedin':
							if ($userfiles->ruid == -1)
								return $this->showError('You must login to your account in order to view this user\'s files.');
							break;

						case 'friends':
							if ($userfiles->ruid != $userfiles->vuid && ! isFriend($userfiles->ruid, $userfiles->vuid))
								return $this->showError('You do not have permission to view this user\'s files.');
							break;
					}
				}
			}

			$this->registerSubHandler(__FILE__, new varsubhandler(
				$this, 'folderCreate', REQUIRE_LOGGEDIN_PLUS,
				varargs('action', 'Create Folders', 'post', true),
				varargs('newFolder', array('string'), 'post', false, array())
			));

			$this->registerSubHandler(__FILE__, new varsubhandler(
				$this, 'filesUpload', REQUIRE_LOGGEDIN_PLUS,
				varargs('action', 'Upload Files', 'post', true)
			));

			$this->registerSubHandler(__FILE__, new varsubhandler(
				$this, 'filesDelete', REQUIRE_LOGGEDIN_PLUS,
				varargs('action', 'Delete', 'post', true),
				varargs('file', array('string'), 'post', false, array())
			));

			$this->registerSubHandler(__FILE__, new varsubhandler(
				$this, 'filesCreate', REQUIRE_LOGGEDIN_PLUS,
				varargs('action', 'Create Files', 'post', true),
				varargs('newFile', array('string'), 'post', false, array())
			));

			$this->registerSubHandler(__FILE__, new varsubhandler(
				$this, 'fileEdit', REQUIRE_LOGGEDIN_PLUS,
				varargs('action', 'Edit File', 'post', true)
			));

			$this->registerSubHandler(__FILE__, new varsubhandler(
				$this, 'fileSave', REQUIRE_LOGGEDIN_PLUS,
				varargs('action', 'Save File', 'post', true),
				varargs('subaction', 'string', 'post', false, 'Discard Changes'),
				varargs('contents', 'string', 'post', true)
			));

			$this->registerSubHandler(__FILE__, new varsubhandler(
				$this, 'filesMove', REQUIRE_LOGGEDIN_PLUS,
				varargs('action', 'Move', 'post', true),
				varargs('moveto', 'string', 'post', true),
				varargs('file', array('string'), 'post', false, array())
			));

			$this->registerSubHandler(__FILE__, new varsubhandler(
				$this, 'fileRename', REQUIRE_LOGGEDIN_PLUS,
				varargs('action', 'Rename', 'post', true),
				varargs('renameTo', 'string', 'post', true)
			));

			$this->registerSubHandler(__FILE__, new varsubhandler(
				$this, 'fileDownload', REQUIRE_ANY,
				varargs('action', 'Download', 'post', true)
			));

			$this->registerSubHandler(__FILE__, new varsubhandler(
				$this, 'toolbarStatus', REQUIRE_LOGGEDIN_PLUS,
				varargs('action', 'toolbarStatus', 'post', true),
				varargs('toolbars', 'int', 'post', true)
			));

			$this->registerSubHandler(__FILE__, new varsubhandler(
				$this, 'savePerms', REQUIRE_LOGGEDIN_PLUS,
				varargs('action', 'savePerms', 'post', true),
				varargs('perms', 'string', 'post', true)
			));

			$this->registerSubHandler(__FILE__, new varsubhandler(
				$this, 'setQuota', REQUIRE_LOGGEDIN_PLUS,
				varargs('action', 'Set Quota', 'post', true),
				varargs('quota', 'int', 'post', true)
			));

			$this->registerSubHandler(__FILE__, new varsubhandler(
				$this, 'filesIndex', REQUIRE_ANY
			));
		}

		function execTemplate ($file, $vars = array()) {
			$userfiles = $this->userfiles;

			$this->tmpl = new template($file);
			$this->tmpl->setMultiple(array_merge(
				array(
					'HOST'			=> $_SERVER['HTTP_HOST'],
					'userRoot'		=> (floor($userfiles->vuid / 1000)) . "/" . $userfiles->vuid,
					'root'			=> $this->root,
					'errmsgs'		=> $this->errmsgs,
					'errmsgs_count'	=> count($this->errmsgs),
					'canWrite'		=> $userfiles->canWrite,
					'config'		=> $this->config
				),
				$userfiles->userPerms(),
				$vars
			));
			$this->tmpl->display();
		}

		function adminlog ($entry) {
			global $mods;
			if ($this->userfiles->isAdmin)
				$mods->adminlog("files;{$this->userfiles->vuid}", $entry);
		}

		function delPerms ($root) {
			global $usersdb;
			$usersdb->prepare_query('DELETE FROM userfiles WHERE userid = % AND folderpath = ?', $this->userfiles->euid, $root);
		}

		function getPerms ($root) {
			global $usersdb;
			$sth = $usersdb->prepare_query('SELECT permissions FROM userfiles WHERE userid = % AND folderpath = ?', $this->userfiles->euid, $root);

			if ( ($row = $sth->fetchrow()) !== false )
				return $row['permissions'];
			else
				return false;
		}

		function setPerms ($root, $perms) {
			global $usersdb;
			$usersdb->prepare_query('INSERT IGNORE INTO userfiles (userid, folderpath, permissions) VALUES (%, ?, ?)', $this->userfiles->euid, $root, $perms);
		}

		function filesIndex () {
			global $usersdb, $config;
			$userfiles = $this->userfiles;

			if (! $userfiles->rootExists($this->root, 'folder-strict'))
				return $this->showError('Cannot retrieve a folder listing for the requested path.');

			// get the status of shown/hidden toolbars
			$sth = $usersdb->prepare_query('SELECT filestoolbar FROM users WHERE userid = %', $userfiles->ruid);
			if ( ($toolbars = $sth->fetchrow()) !== false )
				$toolbars = $toolbars['filestoolbar'];
			else
				$toolbars = self::$constToolbars['all'];

			// fetch total size used by user
			$allsize = $userfiles->ttlsize;
			$maxsize = $userfiles->config['maxTotal'];

			// fetch a full folder tree of the user's files
			$allFolders = $curFolders = $curFiles = $selectedFolder = array();
			foreach (array_merge(array('/'), $userfiles->listFolders("/", true)) as $key => $folderPath) {
				$allFolders[$key] = $userfiles->getHandle($folderPath)->fileInfo;
				$selectedFolder[$folderPath] = $this->root == $folderPath ? ' selected="selected"' : '';
			}

			// fetch the subfolders of the current folder
			foreach ($userfiles->listFolders($this->root) as $key => $folderPath) {
				$curFolders[$key] = $userfiles->getHandle($folderPath)->fileInfo;
				$curFolders[$key]['uniqueID'] = preg_replace('/[^a-zA-Z0-9]+/', '', md5($folderPath));
				$curFolders[$key]['size'] = floor($curFolders[$key]['size'] / 1024) . " kB";

				if ($this->root == '/') {
					$sth = $usersdb->prepare_query('SELECT permissions FROM userfiles WHERE userid = % AND folderpath = ?', $userfiles->euid, $folderPath);
					$row = $sth->fetchrow();
					$curFolders[$key]['permissions'] = $row['permissions'];
				}
			}

			$ttlsize = 0;
			// fetch the files of the current folder
			foreach ($userfiles->listFiles($this->root) as $key => $filePath) {
				$curFiles[$key] = $userfiles->getHandle($filePath)->fileInfo;
				$ttlsize += $curFiles[$key]['size'];
				$curFiles[$key]['size'] = floor($curFiles[$key]['size'] / 1024) . " kB";
				$curFiles[$key]['uniqueID'] = preg_replace('/[^a-zA-Z0-9]+/', '', md5($filePath));
			}

			/* get the parent folder of the folder we are viewing */
			// we are in root folder; there is no parent folder above this
			if ($this->root == '/')
				$rootParent = array('root' => '.');

			// we are not in root folder; parent folder exists
			else {
				$rootParent = $userfiles->getHandle($userfiles->rootParent($this->root))->fileInfo;

				// keep root folder private by not showing parent folder when we are one folder deep
				if ($userfiles->vuid != $userfiles->euid && $rootParent['root'] == '/')
					$rootParent = array('root' => '.');					
			}

			// calculate the folder depth (to enforce max folder depth)
			$allowNewFolders = true;
			$folderDepth = 0;
			preg_replace('/\//', '', $this->root, -1, $folderDepth);

			if ($folderDepth > $this->config['filesMaxFolderDepth'])
				$allowNewFolders = false;

			$this->execTemplate('userfiles/index', array(
				'jsloc'             => $config['jsloc'],
				'toolbars'			=> $toolbars,
				'userfilesToolbars'	=> self::$constToolbars,
				'selectedFolder'	=> $selectedFolder,
				'allFolders'		=> $allFolders,
				'rootParent'		=> $rootParent,
				'allowNewFolders'	=> $allowNewFolders,
				'folderCnt'			=> count($curFolders),
				'curFolders'		=> $curFolders,
				'fileCnt'			=> count($curFiles),
				'curFiles'			=> $curFiles,
				'ttlsize'			=> floor($ttlsize / 1024) . " kB",
				'allsize'			=> floor($allsize / 1024) . ' kB',
				'maxsize'			=> floor($maxsize / 1024) . ' kB',
				'quota'				=> floor($maxsize / 1024 / 1024)
			));
		}

		function folderCreate ($action, $newFolders) {
			$userfiles = $this->userfiles;

			if (! $userfiles->canWrite)
				return $this->showError('You do not have permission to modify this user\'s files.');

			$handle = $userfiles->getHandle($this->root);
			if ($handle === false || $handle->type == 'File')
				return $this->showError('The folder specified for location of new folder is invalid.');

			$folderDepth = 0;
			preg_replace('/\//', '', $handle->root, -1, $folderDepth);

			if ($folderDepth > $this->config['filesMaxFolderDepth']) {
				$this->errmsgs[] = "Maximum folder depth has been reached in this folder. Cannot create folder here.";
				$newFolders = array();
			}

			$allFolderCount = count($userfiles->listFolders("/", true));
			
			foreach ($newFolders as $newFolder) {
				if (
					strlen($newFolder) > $this->config['filesMaxFolderLength'] ||
					($newFolder = $this->filenameAllowed($newFolder, true)) === false
				) {
					$this->errmsgs[] = "Name for new folder contains invalid characters.";
					continue;
				}

				$pathExists = $userfiles->rootExists("{$handle->root}{$newFolder}", 'any');
				if ($pathExists === true) {
					$this->errmsgs[] = "A file or folder with the name '{$newFolder}' already exists in this folder.";
					continue;
				}

				if ($allFolderCount >= $this->config['filesMaxFolders']) {
					$this->errmsgs[] = "You have reached the limit of {$this->config['filesMaxFolders']} total folders. Could not create folder.";
					break;
				}

				++$allFolderCount;

				if ($this->root == '/')
					$this->setPerms("{$handle->root}{$newFolder}/", 'private');
				$this->adminlog("create folder;{$handle->root}{$newFolder}");
				$userfiles->makeFolder("{$handle->root}{$newFolder}");
				$this->errmsgs[] = "Created new folder '{$newFolder}'.";
			}

			$this->filesIndex();
		}

		function filesMove ($action, $movetoRoot, $fileRoots) {
			$userfiles = $this->userfiles;

			if (! $userfiles->canWrite)
				return $this->showError('You do not have permission to modify this user\'s files.');

			if ( ($movetoRoot = $this->filenameAllowed($movetoRoot)) === false )
				return $this->showError('The folder to which you are attempting to move files is invalid.');

			if ($movetoRoot == '0') {
				$this->errmsgs[] = "You must select the folder to which you want to move the files.";
				return $this->filesIndex();
			}

			$movetoHandle = $userfiles->getHandle($movetoRoot);

			if ($movetoHandle === false || $movetoHandle->type == 'File')
				return $this->showError('The folder to which you are attempting to move files is invalid.');

			$allowNewFolders = true;
			$folderDepth = 0;
			preg_replace('/\//', '', $movetoHandle->root, -1, $folderDepth);

			foreach ($fileRoots as $fileRoot) {
				if ( ($fileRoot = $this->filenameAllowed($fileRoot)) === false )
					continue;

				$handle = $userfiles->getHandle($fileRoot);
				if ($handle !== false && $handle->root != '/') {
					if ($handle->type == 'Folder' && $folderDepth > $this->config['filesMaxFolderDepth']) {
						$this->errmsgs[] = "Could not move '{$handle->basename}' to '{$movetoHandle->root}', as the folder has reached its maximum permitted folder depth.";
						continue;
					}

					$parentHandle = $userfiles->getHandle($userfiles->rootParent($handle->root));
					if ($parentHandle->root == $movetoHandle->root || strpos($movetoHandle->fsPath, $handle->fsPath) !== false) {
						$this->errmsgs[] = "{$handle->type} '{$handle->basename}' cannot be moved inside of '{$movetoHandle->root}' (doesn't make sense, nor is it possible).";
						continue;
					}

					$pathExists = $userfiles->rootExists("{$movetoHandle->root}{$handle->basename}", 'any');
					if ($pathExists === true) {
						$this->errmsgs[] = "Could not move '{$handle->basename}' to '{$movetoHandle->root}', as the folder already contains a file or folder with this name.";
						continue;
					}

					if ($handle->type == 'Folder') {
						$this->delPerms($handle->root);
						if ($movetoHandle->root == '/')
							$this->setPerms("{$movetoHandle->root}{$handle->basename}", 'private');
					}

					$this->adminlog("move file;from={$this->root}{$handle->basename};to={$movetoHandle->root}{$handle->basename}");
					$handle->rename("{$movetoHandle->root}{$handle->basename}");
					$this->errmsgs[] = "{$handle->type} '{$handle->basename}' moved to '{$movetoHandle->root}'.";
				}
			}

			$this->filesIndex();
		}

		function fileRename ($action, $renameTo) {
			$userfiles = $this->userfiles;

			if (! $userfiles->canWrite)
				return $this->showError('You do not have permission to modify this user\'s files.');

			$handle = $userfiles->getHandle($this->root);
			$parent = $userfiles->getHandle($userfiles->rootParent($handle->root));

			if ($handle === false || $handle->root == '/')
				return $this->showError('The file or folder you are attempting to rename is invalid.');

			if (
				($renameTo = $this->filenameAllowed($renameTo, true)) === false || (
					($handle->type == 'File' && strlen($renameTo) > $this->config['filesMaxFileLength']) ||
					($handle->type == 'Folder' && strlen($renameTo) > $this->config['filesMaxFolderLength'])
				)
			) {
				$this->errmsgs[] = "The new filename entered for '{$handle->basename}' is invalid.";
			}
			else {
				$pathExists = $userfiles->rootExists("{$parent->root}{$renameTo}", 'any');
				if ($pathExists === true)
					$this->errmsgs[] = "A file or folder with the name '{$renameTo}' already exists; could not rename file.";
			}

			if (! count($this->errmsgs)) {
				if ($handle->type == 'Folder') {
					$perms = $this->getPerms($handle->root);
					$this->delPerms($handle->root);
					$this->setPerms("{$parent->root}{$renameTo}" . ($handle->type == 'Folder' ? '/' : ''), $perms);
				}

				$this->adminlog("rename file;from={$handle->root};to={$parent->root}{$renameTo}");
				$handle->rename("{$parent->root}{$renameTo}");
				$this->errmsgs[] = $handle->type . " '{$handle->basename}' renamed to '{$renameTo}" . ($handle->type == 'Folder' ? '/' : '') . "'.";
			}

			$this->root = $parent->root;
			$this->filesIndex();
		}

		function filesDelete ($action, $roots) {
			$userfiles = $this->userfiles;

			if (! $userfiles->canWrite)
				return $this->showError('You do not have permission to modify this user\'s files.');

			foreach ($roots as $root) {
				if ( ($root = $this->filenameAllowed($root)) === false || $root == '/') {
					$this->errmsgs[] = "Could not delete file or folder due to invalid filename.";
					continue;
				}

				$handle = $userfiles->getHandle($root);
				if ($handle !== false) {
					if ($handle->type == 'Folder')
						$this->delPerms($handle->root);

					$this->adminlog("delete {$handle->type};{$handle->root}");
					$handle->delete();
					$this->errmsgs[] = "{$handle->type} '{$handle->basename}' deleted.";
				}
			}

			$userfiles->ttlsize = $userfiles->getTotalSize('/');
			$this->filesIndex();
		}

		function filesUpload ($action) {
			$userfiles = $this->userfiles;

			if (! $userfiles->canWrite)
				return $this->showError('You do not have permission to modify this user\'s files.');

			$handle = $userfiles->getHandle($this->root);
			if ($handle === false || $handle->type == 'File')
				return $this->showError('The folder specified for location of uploaded files is invalid.');

			$fileUploads = isset($_FILES['upload']) ? $_FILES['upload'] : null;
			if ($fileUploads !== null && ! is_array($fileUploads))
				$fileUploads = array($fileUploads);

			if (! is_null($fileUploads)) {
				foreach ($fileUploads['error'] as $key => $err) {
					if ($err == UPLOAD_ERR_NO_FILE)
						continue;

					list($fname, $mime, $size, $tmpfile) = array(
						$fileUploads['name'][$key], $fileUploads['type'][$key], $fileUploads['size'][$key], $fileUploads['tmp_name'][$key]
					);

					$fname = (isset($fname) && (string)$fname !== '') ? basename($fname) : 'unknown-filename';

					if (
						strlen($fname) > $this->config['filesMaxFileLength'] ||
						($fname = $this->filenameAllowed($fname, true)) === false
					) {
						$this->errmsgs[] = "Filename for one of the uploaded files is invalid. Upload skipped.";
						continue;
					}

					$pathExists = $userfiles->rootExists("{$handle->root}{$fname}", 'any');
					if ($pathExists === true) {
						$this->errmsgs[] = "A file or folder with the name '{$fname}' already exists in this folder. Upload skipped.";
						continue;
					}

					switch ($err) {
						case UPLOAD_ERR_INI_SIZE:
						case UPLOAD_ERR_FORM_SIZE:
							$this->errmsgs[] = "Filesize of '{$fname}' exceeds maximum limit. Upload skipped.";
							break;
						case UPLOAD_ERR_PARTIAL:
							$this->errmsgs[] = "Only received partial contents of '{$fname}'. Upload skipped.";
							break;
						case UPLOAD_ERR_NO_TMP_DIR:
							$this->errmsgs[] = "Encountered server-side error: no temporary directory defined. Upload skipped.";
							break;
						case UPLOAD_ERR_OK:
							if ($size == 0 || $tmpfile == '')
								continue;
							if ($size > $this->config['filesMaxFileSize']) {
								$this->errmsgs[] = "Filesize of '{$fname}' exceeds maximum limit. Upload skipped.";
								break;
							}
							if ($userfiles->ttlsize + $size > $userfiles->config['maxTotal']) {
								$this->errmsgs[] = "Uploading '{$fname}' would exceed your files quota. Upload skipped.";
								break;
							}

							$this->adminlog("upload file;{$handle->root}{$fname}");
							$userfiles->uploadFile($tmpfile, "{$handle->root}{$fname}");
							$this->errmsgs[] = "Uploaded file '{$fname}'.";
					}
				}
			}

			$userfiles->ttlsize = $userfiles->getTotalSize('/');
			$this->filesIndex();
		}

		function filesCreate ($action, $newFiles) {
			$userfiles = $this->userfiles;

			if (! $userfiles->canWrite)
				return $this->showError('You do not have permission to modify this user\'s files.');

			$handle = $userfiles->getHandle($this->root);
			if ($handle === false || $handle->type == 'File')
				return $this->showError('The folder specified for location of new text file is invalid.');

			foreach ($newFiles as $newFile) {
				if (
					strlen($newFile) > $this->config['filesMaxFileLength'] ||
					($newFile = $this->filenameAllowed($newFile, true)) === false
				)
					$this->errmsgs[] = "The filename entered for a new text file is invalid.";

				else if ($userfiles->rootExists("{$this->root}{$newFile}", 'any'))
					$this->errmsgs[] = "A file or folder with the name '{$newFile}' already exists.";

				else if ($userfiles->ttlsize + 1024 > $userfiles->config['maxTotal'])
					$this->errmsgs[] = "Creating new text file '{$newFile}' would exceed your files quota. File creation skipped.";

				else {
					$this->adminlog("create file;{$this->root}{$newFile}");
					$userfiles->touchFile("{$this->root}{$newFile}");
					$this->errmsgs[] = "New text file '{$newFile}' created.";
				}
			}

			$this->filesIndex();
		}

		function fileEdit ($action, $contents = null) {
			$userfiles = $this->userfiles;

			if (! $userfiles->canWrite)
				return $this->showError('You do not have permission to modify this user\'s files.');

			$handle = $userfiles->getHandle($this->root);
			if ($handle === false || $handle->type == 'Folder')
				return $this->showError('The file or folder chosen for editting purposes is invalid.');

			$fileInfo = $handle->fileInfo;
			$fileInfo['contents'] = is_null($contents) ? $handle->fileContents() : $contents;

			$this->execTemplate('userfiles/editfile', array('fileInfo' => $fileInfo));
		}

		function fileSave ($action, $subaction, $contents) {
			$userfiles = $this->userfiles;

			if (! $userfiles->canWrite)
				return $this->showError('You do not have permission to modify this user\'s files.');

			if ($subaction != 'Save and Continue Editting' && $subaction != 'Save and Close' && $subaction != 'Discard Changes')
				$subaction = 'Discard Changes';
			
			$handle = $userfiles->getHandle($this->root);
			if ($handle === false || $handle->type == 'Folder')
				return $this->showError('The file or folder chosen for editting purposes is invalid.');

			if (substr($subaction, 0, 4) == 'Save') {
				if (strlen($contents) > $this->config['filesMaxFileSize']) {
					$this->errmsgs[] = "FILE NOT SAVED! Filesize of new contents exceeds maximum limit. The contents must be shortened before the file can be saved.";
					return $this->fileEdit('Save and Continue Editting', $contents);
				}
				elseif ($userfiles->ttlsize - $handle->size + strlen($contents) > $userfiles->config['maxTotal']) {
					$this->errmsgs[] = "FILE NOT SAVED! Saving the contents of this file would exceed your files quota. The contents must be shortened before the file can be saved.";
					return $this->fileEdit('Save and Continue Editting', $contents);
				}
				else {
					$this->adminlog("save file;{$handle->root}");
					$handle->fileContents($contents);
					$userfiles->ttlsize = $userfiles->getTotalSize('/');
					$this->errmsgs[] = "Contents of '{$handle->basename}' saved.";
				}
			}
			else {
				$this->errmsgs[] = "Discarded changes made to '{$handle->basename}'.";
			}

			if ($subaction == 'Save and Continue Editting') {
				$this->fileEdit();
			}
			else {
				$this->root = $userfiles->getHandle($userfiles->rootParent($this->root))->root;
				$this->filesIndex();
			}
		}

		function fileDownload ($action) {
			$userfiles = $this->userfiles;

			$handle = $userfiles->getHandle($this->root);
			if ($handle === false || $handle->type == 'Folder')
				return $this->showError('The file you are attempting to download could not be retrieved from the server.');

			header("Content-type: application/octet-stream");
			header("Content-disposition: attachment; filename=\"{$handle->basename}\"");
			header("Content-length: {$handle->size}");
			header("Connection: close");
			header("Expires: 0");

			set_time_limit(0);
			$handle->readfile();
		}
		
		function toolbarStatus ($action, $toolbars) {
			global $usersdb;
			$userfiles = $this->userfiles;

			if ($toolbars > self::$constToolbars['all'])
				$toolbars = self::$constToolbars['all'];

			$usersdb->prepare_query('UPDATE users SET filestoolbar = # WHERE userid = %', $toolbars, $userfiles->ruid);

			header('Content-type: text/xml');
			$blank = new DOMDocument();
			$blank->appendChild( $blank->createElement('root') );
			echo $blank->saveXML();
		}

		function savePerms ($action, $perms) {
			global $usersdb;

			if (! $this->userfiles->canWrite)
				return $this->showError('You do not have permission to modify this user\'s files.');

			header('Content-type: text/xml');
			$blank = new DOMDocument();

			if ($this->root == '/' || ! in_array($perms, array('private', 'public', 'loggedin', 'friends')))
				$blank->appendChild($blank->createElement('root', 'bad permissions'));
			else {
				$usersdb->prepare_query('UPDATE userfiles SET permissions = ? WHERE userid = % AND folderpath = ?', $perms, $this->userfiles->euid, $this->root);
				$blank->appendChild($blank->createElement('root', 'saved'));
			}

			echo $blank->saveXML();
		}

		function setQuota ($action, $quota) {
			global $usersdb;

			$userfiles = $this->userfiles;

			if (! $userfiles->isAdmin)
				return $this->showError('You do not have permission to modify this user\'s files.');

			if ($quota >= 0 && $quota < 1000) {
				$usersdb->prepare_query('UPDATE users SET filesquota=# WHERE userid=%', $quota * 1024 * 1024, $userfiles->euid);
				$userfiles->config['maxTotal'] = $quota * 1024 * 1024;
				$this->adminlog("set quota;{$quota} MB");
				$this->errmsgs[] = "- User's quota set to $quota MB.";
			}
			else
				$this->errmsgs[] = "- Invalid quota. Quota must be between 0 and 999 MB.";

			$this->filesIndex();
		}

		function showError ($errmsg) {
			$this->execTemplate('userfiles/error', array('errmsg' => $errmsg));
		}

		function extensionAllowed ($fname) {
			return ! in_array(substr($fname, strrpos($fname, '.') + 1), $this->config['filesRestrictExts']);
		}

		function filenameAllowed ($fname, $checkForFolder = false) {
			$fname = preg_replace('!\./!', '', preg_replace('!/{2,}!', '/', trim($fname)));

			$invalid = false;
			foreach (array('..', '&', '\'', '"') as $badSeq)
				if (strpos($fname, $badSeq) !== false)
					$invalid = true;

			if (
				$invalid || ! preg_match('/\S/', $fname) || $fname{0} == '.' || substr($fname, -1) == '.' ||
				! $this->extensionAllowed($fname) || ($checkForFolder && strpos($fname, '/') !== false)
			)
				return false;

			return $fname;
		}
	}

	$page = new pageUserFiles;
	$page->runPage();
?>

