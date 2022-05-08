<?
	$login = 1;
	require_once('include/general.lib.php');

	class picmodexamcommon extends pagehandler {
		public $examconfig = array();
		public $categories = array();
		public $db;
		public $cache;

		function __construct () {
			global $picmodexamdb, $config, $staticRoot, $cache;

			$this->db = &$picmodexamdb;
			$this->cache = &$cache;

			$this->examconfig = $this->cache->get('picmodexam-config');
			if (!$this->examconfig) {
				$res = $this->db->prepare_query('SELECT * FROM examconfig');
				while ($row = $res->fetchrow())
					$this->examconfig[$row['var']] = $row['data'];
				$this->examconfig['webpicdir'] = $config['picmodexamdir'];
				$this->cache->put('picmodexam-config', $this->examconfig, 60*60*24*7);
			}
			$this->examconfig['fspicdir'] = "${docRoot}{$config['picmodexamdir']}";

			$this->restrictconfig = array('fspicdir', 'webpicdir');
			if (!file_exists($this->examconfig['fspicdir'])) trigger_error("File system path for picmodexam images does not exist!", E_USER_ERROR);

			$this->categories = $this->cache->get('picmodexam-categories');
			if (!$this->categories) {
				$res = $this->db->prepare_query('SELECT * FROM exampiccategories ORDER BY catid');
				$this->categories = $res->fetchrowset();
				$this->cache->put('picmodexam-categories', $this->categories, 60*60*24*7);
			}
		}
	}

	class picmodexamadmin extends picmodexamcommon {
		function __construct () {
			parent::__construct();

			$this->registerSubHandler(__FILE__, new varsubhandler(
				$this, 'picmanager_uploadpics', array(REQUIRE_LOGGEDIN_ADMIN, 'editmods'),
				varargs('page', 'picmanager_uploadpics', 'request', true)
			));

			$this->registerSubHandler(__FILE__, new varsubhandler(
				$this, 'picmanager_editpending', array(REQUIRE_LOGGEDIN_ADMIN, 'editmods'),
				varargs('page', 'picmanager_editpending', 'request', true)
			));

			$this->registerSubHandler(__FILE__, new varsubhandler(
				$this, 'picmanager_editcurrent', array(REQUIRE_LOGGEDIN_ADMIN, 'editmods'),
				varargs('page', 'picmanager_editcurrent', 'request', true)
			));

			$this->registerSubHandler(__FILE__, new varsubhandler(
				$this, 'config_catcnt', array(REQUIRE_LOGGEDIN_ADMIN, 'editmods'),
				varargs('page', 'config_catcnt', 'request', true)
			));

			$this->registerSubHandler(__FILE__, new varsubhandler(
				$this, 'config_vars', array(REQUIRE_LOGGEDIN_ADMIN, 'editmods'),
				varargs('page', 'config_vars', 'request', true)
			));

			$this->registerSubHandler(__FILE__, new varsubhandler(
				$this, 'usermanager_index', array(REQUIRE_LOGGEDIN_ADMIN, 'editmods'),
				varargs('page', 'usermanager_index', 'request', true)
			));

			$this->registerSubHandler(__FILE__, new varsubhandler(
				$this, 'display_exam', array(REQUIRE_LOGGEDIN_ADMIN, 'editmods'),
				varargs('page', 'display_exam', 'request', true)
			));

			$this->registerSubHandler(__FILE__, new varsubhandler(
				$this, 'invalid_mode', array(REQUIRE_LOGGEDIN_ADMIN, 'editmods'),
				varargs('page', 'string', 'request', true)
			));

			$this->registerSubHandler(__FILE__, new varsubhandler(
				$this, 'main_index', array(REQUIRE_LOGGEDIN_ADMIN, 'editmods')
			));
		}

		function pageheader () {
			incHeader();
?>

<table align="center">
	<tr>
		<td>
			<table style="margin-bottom: 10px; width: 100%;" cellpadding="0" cellspacing="0" align="center">
				<tr>
					<td class="header" style="font-weight: bold; padding: 3px;" colspan="2">Pic Mod Exam Admin</td>
				</tr>
				<tr>
					<td class="body2" style="padding: 3px;" colspan="2">
						<strong>Pic Manager:</strong>
						<a class="body" href="<?= $_SERVER['PHP_SELF']; ?>?admin=1&amp;page=picmanager_uploadpics">Upload New Pics</a> |
						<a class="body" href="<?= $_SERVER['PHP_SELF']; ?>?admin=1&amp;page=picmanager_editpending">Manage Pending Pics</a> |
						<a class="body" href="<?= $_SERVER['PHP_SELF']; ?>?admin=1&amp;page=picmanager_editcurrent">Manage Current Pics</a>
					</td>
				</tr>
				<tr>
					<td class="body2" style="padding: 3px;">
						<strong>User Manager:</strong>
						<a class="body" href="<?= $_SERVER['PHP_SELF']; ?>?admin=1&amp;page=usermanager_index">Manage Users &amp; Exam Results</a>
					</td>
					<td class="body2" style="padding: 3px;">
						<strong>Config Editor:</strong>
						<a class="body" href="<?= $_SERVER['PHP_SELF']; ?>?admin=1&amp;page=config_catcnt">Manage Categories</a> |
						<a class="body" href="<?= $_SERVER['PHP_SELF']; ?>?admin=1&amp;page=config_vars">Exam Settings</a>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td class="body">

<?
		}

		function pagefooter () {
			echo "</td></tr></table>";
			incFooter();
		}

		function invalid_mode () {
			$this->pageheader();
			echo "Invalid administration mode!";
			$this->pagefooter();
		}

		function main_index () {
			$this->pageheader();
			echo "Select the function you wish to work with from the menus above.";
			$this->pagefooter();
		}

		function config_catcnt ($page) {
			$errmsgs = array( 'addcategory' => array(), 'missing' => array(), 'badnums' => array(), 'saved' => array() );
			list($savecnts) = cleanvarsarray(array('save', 0, 3, '::digit::', 0, 0));
			$addcategory = cleanvar('addcategory', 40, 'a-zA-Z \\/', null, null);

			if (!is_null($addcategory[1])) {
				if (strlen($addcategory[1]) > 40 or strlen($addcategory[1]) < 10) {
					$errmsgs['addcategory'][] = "- New category must be between 10 and 40 characters in length!";
				}
				elseif ($addcategory[0] !== $addcategory[1]) {
					$errmsgs['addcategory'][] = "- New category contains invalid characters. Modified version provided for resubmit.";
				}
				else {
					foreach ($this->categories as $cat) {
						if ($cat['catlabel'] === $addcategory[0]) {
							$errmsgs['addcategory'][] = "- Category '{$cat['catlabel']}' already exists. Addition aborted.";
							break;
						}
					}
				}

				if (count($errmsgs['addcategory']) === 0) {
					$this->db->prepare_query('INSERT INTO exampiccategories SET catlabel=?', $addcategory[0]);
					$this->categories[] = array('catid' => $this->db->insertid(), 'catlabel' => $addcategory[0], 'catcnt' => 0);
					$this->cache->put('picmodexam-categories', $this->categories, 60*60*24*7);
					$errmsgs['addcategory'][] = "- Added new category '{$addcategory[0]}'.";
					$addcategory[0] = '';
				}
			}

			if (count($savecnts) > 0) {
				foreach ($savecnts as $catid => $catcnt) {
					if (!isset($this->categories[$catid - 1])) continue;
					$catlabel = $this->categories[$catid - 1]['catlabel'];

					if ($catcnt[0] !== $catcnt[1]) {
						$errmsgs['missing'][] = "- '${catlabel}' was not given a numerical value. Modified version provided for resubmit.";
						continue;
					}

					$catcnt = $catcnt[0];
					if ($this->categories[$catid - 1]['catcnt'] == $catcnt) continue;

					if (!is_numeric($catcnt) or $catcnt < 0 or $catcnt > 255) {
						$errmsgs['missing'][] = "- '${catlabel}' needs a number between 0 and 255.";
						continue;
					}

					$res = $this->db->prepare_query(
						'SELECT forceinclude FROM exampics WHERE modcategory=# AND modanswer IN (?, ?, ?) AND isretired=?',
						 $catid, 'accept', 'deny', 'instant fail', 'n'
					);
					$piccnt = 0;
					$forced = 0;
					while ($row = $res->fetchrow()) {
						++$piccnt;
						if ($row['forceinclude'] == 'y') ++$forced;
					}

					if ($catcnt < $forced)
						$errmsgs['badnums'][] = "- '${catlabel}' has $forced force-included pics in the exam. " .
							"Increase the number for this category to a minimum of ${forced}, or retire " . ($forced - $catcnt) . " force-included pics for this category.";

					if ($catcnt > $piccnt)
						$errmsgs['badnums'][] = "- '${catlabel}' only has $piccnt pics in the exam pool. " .
							"Decrease the number for this category to a maximum of $piccnt or upload " . ($catcnt - $piccnt) . " more pics for this category.";
				}

				if (count($errmsgs['missing']) == 0 and count($errmsgs['badnums']) == 0) {
					foreach ($savecnts as $catid => $catcnt) {
						$catcnt = $catcnt[0];
						if ($this->categories[$catid - 1]['catcnt'] == $catcnt) continue;
						$this->db->prepare_query('UPDATE exampiccategories SET catcnt=# WHERE catid=#', $catcnt, $catid);
						$this->categories[$catid - 1]['catcnt'] = $catcnt;
					}
					$this->cache->put('picmodexam-categories', $this->categories, 60*60*24*7);

					$errmsgs['saved'][] = "- new config numbers saved.";
				}
			}

			$whicherrs = (count($errmsgs['addcategory']) > 0 ?
				'addcategory' : (count($errmsgs['missing']) > 0 ?
					'missing' : (count($errmsgs['badnums']) > 0 ?
						'badnums' : (count($errmsgs['saved']) > 0 ?
							'saved' : null
						)
					)
				)
			);

			$this->pageheader();
?>

<? if (!is_null($whicherrs)): ?>
	<table style="width: 100%; margin-bottom: 15px;" cellpadding="0" cellspacing="0">
		<tr>
			<td class="msg" style="padding: 3px; font-weight: bold;">What happened:</td>
		</tr>
		<tr>
			<td class="msg" style="padding: 3px;"><?= join("<br />", $errmsgs[$whicherrs]); ?></td>
		</tr>
	</table>
<? endif; ?>

<form action="<?= $_SERVER['PHP_SELF']; ?>" method="post">
	<input type="hidden" name="admin" value="1" />
	<input type="hidden" name="page" value="config_catcnt" />

	<table width="100%">
		<tr>
			<td class="body2">
				<strong>New Category:</strong>
				<input type="text" class="body" name="addcategory" value="<?= $addcategory[0]; ?>" size="15" maxlength="40" />
				<input type="submit" class="body" name="sbmt" value="Add Category" />
			</td>
		</tr>
	</table>
</form>

<form action="<?= $_SERVER['PHP_SELF']; ?>" method="post">
	<input type="hidden" name="admin" value="1" />
	<input type="hidden" name="page" value="config_catcnt" />

	<table align="center" style="width: 100%">
		<tr>
			<td class="header" style="padding: 3px;">Category Label</td>
			<td class="header" style="padding: 3px;">Category Count</td>
			<td class="header" style="padding: 3px;">Min/Max Values</td>
		</tr>

		<?
			$mins = $maxs = array();
			foreach (range(1, count($this->categories)) as $index) $mins[$index] = $maxs[$index] = 0;

			$res = $this->db->prepare_query(
				'SELECT modcategory, forceinclude FROM exampics WHERE modanswer IN (?, ?, ?) AND isretired=?',
				'accept', 'deny', 'instant fail', 'n'
			);
			while ($row = $res->fetchrow()) {
				if ($row['forceinclude'] == 'y') ++$mins[$row['modcategory']];
				++$maxs[$row['modcategory']];
			}
		?>

		<? foreach ($this->categories as $cat): ?>
			<tr>
				<td class="body" align="right"><strong><?= ucfirst($cat['catlabel']); ?>:</strong></td>
				<td class="body">
					<? $catcnt = isset($savecnts[$cat['catid']]) ? $savecnts[$cat['catid']][0] : $cat['catcnt']; ?>
					<input type="text" class="body" name="save[<?= $cat['catid']; ?>]" value="<?= $catcnt; ?>" size="3" maxlength="3" />
				</td>
				<td class="body"><?= "{$mins[$cat['catid']]} / {$maxs[$cat['catid']]}"; ?></td>
			</tr>
		<? endforeach; ?>

		<tr>
			<td class="body" colspan="3" style="text-align: center;">
				<input type="submit" class="body" name="sbmt" value="Save Changes" />
			</td>
		</tr>
	</table>

<?
			$this->pagefooter();
		}

		function config_vars ($page) {
			$errmsgs = array();

			list($savevars) = cleanvarsarray(array('save', 0, 255, '0-9.', '', null));

			if (count($savevars) > 0) {
				foreach ($savevars as $var => $data) {
					if (is_null($data[0]) or !isset($this->examconfig[$var]) or in_array($var, $this->restrictconfig)) { unset($savevars[$var]); continue; }
					if ($data[0] !== $data[1]) $errmsgs[] = "- Value for '${var}' contains illegal characters. Modified version provided for resubmit.";
				}

				if (count($errmsgs) === 0) {
					foreach ($savevars as $var => $data) {
						if ((string)$data[0] === (string)$this->examconfig[$var]) continue;
						$this->db->prepare_query('UPDATE examconfig SET data=? WHERE var=?', $data[0], $var);
						$this->examconfig[$var] = $data[0];
					}
					$this->cache->put('picmodexam-config', $this->examconfig, 60*60*24*7);
					$errmsgs[] = "- New configuration set saved.";
				}
			}

			$this->pageheader();
?>

<? if (count($errmsgs) > 0): ?>
	<table style="width: 100%;" cellpadding="0" cellspacing="0">
		<tr>
			<td class="msg" style="padding: 3px; font-weight: bold;">What happened:</td>
		</tr>
		<tr>
			<td class="msg" style="padding: 3px;"><?= join("<br />", $errmsgs); ?></td>
		</tr>
	</table>
<? endif; ?>

<form action="<?= $_SERVER['PHP_SELF']; ?>" method="post">
	<input type="hidden" name="admin" value="1" />
	<input type="hidden" name="page" value="config_vars" />

	<table align="center">
		<tr>
			<td class="header" colspan="2" style="padding: 3px;">Additonal Exam Config</td>
		</tr>

		<? foreach ($this->examconfig as $var => $data): ?>
			<? if (in_array($var, $this->restrictconfig)) continue; ?>
			<tr>
				<td class="body"><strong><?= ucfirst($var); ?></strong></td>
				<td class="body"><input type="text" class="body" name="save[<?= $var ?>]" value="<?= htmlentities(isset($savevars[$var]) ? $savevars[$var][0] : $data); ?>" size="25" /></td>
			</tr>
		<? endforeach; ?>

		<tr>
			<td class="body" colspan="2" style="text-align: center;">
				<input type="submit" class="body" name="sbmt" value="Save Changes" />
			</td>
		</tr>
	</table>

<?
			$this->pagefooter();
		}

		function picmanager_uploadpics($page) {
			$errmsgs = array();

			$uploads = getFILEval('picupload');
			if ($uploads === false) $uploads = array('error' => array());

			foreach ($uploads['error'] as $key => $err) {
				list($fname, $mime, $size, $tmpfile) = array(
					$uploads['name'][$key], $uploads['type'][$key], $uploads['size'][$key], $uploads['tmp_name'][$key]
				);

				$fname = (isset($fname) and (string)$fname !== '') ? basename($fname) : 'unknown-filename';
				$fname = preg_replace('/[^a-zA-Z0-9 .\\-_]+/', '', $fname);

				switch ((int)$err) {
					case UPLOAD_ERR_NO_FILE:
						continue;
						break;
					case UPLOAD_ERR_INI_SIZE:
					case UPLOAD_ERR_FORM_SIZE:
						$errmsgs[] = "- Filesize of '${fname}' exceeds maximum imposed limit. Upload aborted.";
						break;
					case UPLOAD_ERR_PARTIAL:
						$errmsgs[] = "- Partial contents of '${fname}' received. Upload aborted.";
						break;
					case UPLOAD_ERR_NO_TMP_DIR:
						$errmsgs[] = "- Server-side error: missing temp directory.";
						break;
					case UPLOAD_ERR_OK:
						if ((int)$size === 0 or (string)$tmpfile === '') continue;

						$fileid = sha1_file($tmpfile) . '.jpg';
						$res = $this->db->prepare_query('SELECT count(*) AS existing FROM exampics WHERE picfilename=?', $fileid);
						$existingpic = $res->fetchfield();

						if ($existingpic != 0) {
							$errmsgs[] = "- File '${fname}' already exists in picture database, pic id #{$existingpic[0]}. Upload aborted.";
							continue;
						}

						$this->db->prepare_query('INSERT INTO exampics SET picfilename=?, addedtime=#', $fileid, time());

						$dest = "{$this->examconfig['fspicdir']}$fileid";
						move_uploaded_file($tmpfile, $dest);
						@chmod($dest, 0775);
						$errmsgs[] = "- Uploaded '${fname}' as pic id #" . $this->db->insertid() . ".";
				}
			}

			$this->pageheader();

			list($fieldcnt) = cleanvar('fieldcnt', 3, '::digit::', 10, 10);
			$fieldcnt = $fieldcnt < 1 ? 1 : (($fieldcnt > 50 ? 50 : $fieldcnt));
?>

<? if (count($errmsgs) > 0): ?>
	<table style="width: 100%;" cellpadding="0" cellspacing="0">
		<tr>
			<td class="msg" style="padding: 3px; font-weight: bold;">What happened:</td>
		</tr>
		<tr>
			<td class="msg" style="padding: 3px;"><?= join("<br />", $errmsgs); ?></td>
		</tr>
	</table>
<? endif; ?>

<form action="<?= $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data">
	<input type="hidden" name="admin" value="1" />
	<input type="hidden" name="page" value="picmanager_uploadpics" />
	<input type="hidden" name="fieldcnt" value="<?= $fieldcnt ?>" />

	<table cellspacing="10" cellpadding="0" border="0" style="width: 100%;">
		<tr>
			<td colspan="2" class="body">
				<strong>Number of upload fields:</strong>
				<? foreach (range(5, 50, 5) as $cnt): ?>
					<a class="body" href="<?= $_SERVER['PHP_SELF']; ?>?admin=1&amp;page=picmanager_uploadpics&amp;fieldcnt=<?= $cnt; ?>"><?= $cnt ?></a>
				<? endforeach; ?>
			</td>
		</tr>

		<? foreach (range(1, $fieldcnt) as $cnt): ?>
			<tr>
				<td style="font-wight: bold;" class="body">Pic #<?= $cnt ?>:</td>
				<td><input type="file" class="body" name="picupload[]" /></td>
			</tr>
		<? endforeach; ?>

		<tr>
			<td colspan="2" style="text-align: center"><input type="submit" class="body" name="sbmt" value="Upload Pics" /></td>
		</tr>

	</table>
</form>

<?
			$this->pagefooter();
		}

		function picmanager_editpending ($page) {
			$errmsgs = array();

			list($answers, $forceincls, $cats, $reasons, $genders, $ages, $descs) = cleanvarsarray(
				array('answer', 0, 12, '::char::', null, null),
				array('forceinclude', 0, 3, '::char::', null, null),
				array('category', 0, 3, '::digit::', null, null),
				array('reason', 0, 255, '::english_nonewline::', null, null),
				array('gender', 0, 6, '::char::', null, null),
				array('age', 0, 3, '::digit::', null, null),
				array('desc', 0, 255, '::english_nonewline::', null, null)
			);

			foreach ($answers as $picid => $answer) {
				if ((int)$picid < 1 or $answer[0] !== $answer[1] or $answer[0] === 'skip') continue;

				$res = $this->db->prepare_query('SELECT picfilename FROM exampics WHERE picid=#', $picid);
				$filename = $res->fetchrow();
				if ($filename === false) continue;
				$filename = $filename['picfilename'];

				if (!isset($forceincls[$picid])) $forceincls[$picid] = array(null, null);
				if (!isset($cats[$picid])) $cats[$picid] = array(null, null);
				if (!isset($reasons[$picid])) $reasons[$picid] = array(null, null);
				if (!isset($genders[$picid])) $genders[$picid] = array(null, null);
				if (!isset($ages[$picid])) $ages[$picid] = array(null, null);
				if (!isset($descs[$picid])) $descs[$picid] = array(null, null);

				switch ($answer[0]) {
					case 'delete':
						$this->db->prepare_query('DELETE FROM exampics WHERE picid=#', $picid);
						unlink("{$this->examconfig['fspicdir']}{$filename}");
						$errmsgs[] = "- Permanently deleted picid #$picid.";
						break;

					case 'accept':
					case 'deny':
					case 'instant fail':
						if ($forceincls[$picid][0] !== $forceincls[$picid][1]) {
							$errmsgs[] = "- 'Force include' option for picid #$picid is invalid.";
							break;
						}
						if ( ($genders[$picid][0] !== $genders[$picid][1]) or ($genders[$picid][0] !== 'male' and $genders[$picid][0] !== 'female') ) {
							$errmsgs[] = "- Gender selection for picid #$picid is invalid.";
							break;
						}
						if ($ages[$picid][0] !== $ages[$picid][1] or (int)$ages[$picid][0] < 14 or (int)$ages[$picid][0] > 255) {
							$errmsgs[] = "- Age entry for picid #$picid is invalid (must be between 14 and 255).";
							break;
						}
						if ($descs[$picid][0] !== $descs[$picid][1]) {
							$errmsgs[] = "- Descriptive comment for picid #$picid contains invalid characters. Modified version provided for resubmit.";
							break;
						}

						if ( ($cats[$picid][0] !== $cats[$picid][1]) or !isset($this->categories[$cats[$picid][0] - 1]) ) {
							$errmsgs[] = "- Modding category for picid #$picid is invalid.";
							break;
						}

						if ($forceincls[$picid][0] === 'y') {
							$catcnt = $this->categories[$cats[$picid][0] - 1]['catcnt'];

							$res = $this->db->prepare_query(
								'SELECT COUNT(*) AS forced FROM exampics WHERE modcategory=# AND forceinclude=? AND isretired=?',
								$cats[$picid][0], 'y', 'n'
							);
							$forced = $res->fetchfield();

							if ($forced >= $catcnt) {
								$errmsgs[] = "- Force inclusion of picid #$picid not possible with current exam config. " .
									"Lower config number for '{$this->categories[$cats[$picid][0] - 1]['catlabel']}' category, or deselect the 'force include' option.";
								break;
							}
						}
						else {
							$forceincls[$picid][0] = 'n';
						}

						if ($answer[0] === 'accept') {
							if ((int)$cats[$picid][0] !== 1) {
								$errmsgs[] = "- Modding category for picid #$picid must be 'approved pic' if mod answer is 'accept'.";
								break;
							}
							$this->db->prepare_query(
								'UPDATE exampics SET modanswer=?, forceinclude=?, picgender=?, picage=?, piccomment=? WHERE picid=#',
								'accept', $forceincls[$picid][0], $genders[$picid][0], $ages[$picid][0], $descs[$picid][0], $picid
							);

							$errmsgs[] = "- Successfully added picid #$picid to the exam pool.";
							break;
						}
						else {
							if ((int)$cats[$picid][0] === 1) {
								$errmsgs[] = "- Modding category for picid #$picid cannot be 'approved pic' if mod answer is 'deny' or 'instant fail'.";
								break;
							}
							if (strlen($reasons[$picid][0]) < 5 or $reasons[$picid][0] !== $reasons[$picid][1]) {
								$errmsgs[] = "- Deny reason for picid #$picid is invalid. Must be between 5 and 255 characters in length.";
								break;
							}

							$this->db->prepare_query(
								'UPDATE exampics SET modanswer=?, forceinclude=?, modcategory=#, modreason=?, picgender=?, picage=?, piccomment=? WHERE picid=#',
								($answer[0] === 'deny' ? 'deny' : 'instant fail'), $forceincls[$picid][0], $cats[$picid][0], $reasons[$picid][0], $genders[$picid][0], $ages[$picid][0], $descs[$picid][0], $picid
							);

							$errmsgs[] = "- Successfully added picid #$picid to the exam pool.";
							break;
						}

					default:
						$errmsgs[] = "- Invalid op/answer for pic id #$picid. Skipped.";
				}

			}

			$this->pageheader();

			$res = $this->db->prepare_query('SELECT * FROM exampics WHERE modanswer=? ORDER BY picid ASC LIMIT 50', 'unknown');
			$rows = $res->fetchrowset();
?>

<? if (count($errmsgs) > 0): ?>
	<table style="width: 100%; margin-bottom: 10px;" cellpadding="0" cellspacing="0">
		<tr>
			<td class="msg" style="padding: 3px; font-weight: bold;">What happened:</td>
		</tr>
		<tr>
			<td class="msg" style="padding: 3px;"><?= join("<br />", $errmsgs); ?></td>
		</tr>
	</table>
<? endif; ?>

<form action="<?= $_SERVER['PHP_SELF']; ?>" method="post">
	<input type="hidden" name="admin" value="1" />
	<input type="hidden" name="page" value="picmanager_editpending" />

	<table cellspacing="10" cellpadding="10" align="center" style="width: 100%;">
		<? foreach ($rows as $row): ?>
			<?
				$answer = array('skip' => '', 'delete' => '', 'accept' => '', 'deny' => '', 'instant fail' => '');
				$val = isset($answers[$row['picid']]) ? $answers[$row['picid']][0] : 'skip';
				$answer[ isset($answer[$val]) ? $val : 'skip' ] = ' checked="checked"';

				$forceincl = '';
				$val = isset($forceincls[$row['picid']]) ? $forceincls[$row['picid']][0] : 'n';
				if ($val === 'y') $forceincl = ' checked="checked"';

				$cat = array();
				foreach($this->categories as $catid) $cat[$catid['catid']] = '';
				$val = isset($cats[$row['picid']]) ? $cats[$row['picid']][0] : 1;
				$cat[ isset($cat[$val]) ? $val : 1 ] = ' selected="selected"';

				$gender = array('male' => '', 'female' => '');
				$val = isset($genders[$row['picid']]) ? $genders[$row['picid']][0] : 'female';
				$gender[ isset($gender[$val]) ? $val : 'female' ] = ' checked="checked"';

				$reason = isset($reasons[$row['picid']]) ? $reasons[$row['picid']][0] : '';
				$age = isset($ages[$row['picid']]) ? $ages[$row['picid']][0] : '';
				$desc = isset($descs[$row['picid']]) ? $descs[$row['picid']][0] : '';
			?>

			<tr>
				<td colspan="2" class="header" style="text-align: center;">
					<strong>PIC ID: <?= $row['picid']; ?></strong>
				</td>
			</tr>
			<tr>
				<td class="body2" valign="center" align="center"><img src="<?= $this->examconfig['webpicdir'] . $row['picfilename']; ?>" /></td>
				<td class="body" valign="top">

					<table cellspacing="0" cellpadding="3">
						<tr>
							<td colspan="2" class="header"><strong>General Info</strong></td>
						</tr>
						<tr>
							<td class="body"><strong>Pic ID:</strong></td>
							<td class="body"><?= $row['picid']; ?></td>
						</tr>
						<tr>
							<td class="body"><strong>Uploaded:</strong></td>
							<td class="body"><?= strftime('%b %d, %Y @ %I:%M %p', $row['addedtime']); ?></td>
						</tr>
						<tr>
							<td colspan="2" class="header"><strong>Exam Settings</strong></td>
						</tr>
						<tr>
							<td class="body"><strong>Operation:</strong></td>
							<td class="body">
								<input type="radio" name="answer[<?= $row['picid']; ?>]" value="skip"<?= $answer['skip']; ?> /> Skip
								<input type="radio" name="answer[<?= $row['picid']; ?>]" value="delete"<?= $answer['delete']; ?> /> Delete
							</td>
						</tr>
						<tr>
							<td colspan="2" class="body2" style="text-align: center;"><strong>OR</strong></td>
						</tr>
						<tr>
							<td class="body"><strong>Correct Answer:</strong></td>
							<td class="body">
								<input type="radio" name="answer[<?= $row['picid']; ?>]" value="accept"<?= $answer['accept']; ?> /> Accept
								<input type="radio" name="answer[<?= $row['picid']; ?>]" value="deny"<?= $answer['deny']; ?> /> Deny
								<input type="radio" name="answer[<?= $row['picid']; ?>]" value="instant fail"<?= $answer['instant fail']; ?> /> Instant Fail
							</td>
						</tr>
						<tr>
							<td colspan="2" class="header"><strong>If Answer is Deny/Instant Fail</strong></td>
						</tr>
						<tr>
							<td class="body"><strong>Category:</strong></td>
							<td class="body">
								<select class="body" name="category[<?= $row['picid']; ?>]">
									<? foreach ($this->categories as $category): ?>
										<option value="<?= $category['catid']; ?>"<?= $cat[$category['catid']]; ?>><?= ucfirst($category['catlabel']); ?></option>
									<? endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<td class="body"><strong>Specific Reason:</strong></td>
							<td class="body">
								<input type="text" class="body" name="reason[<?= $row['picid'] ?>]" value="<?= htmlentities($reason); ?>" size="40" maxlength="255" />
							</td>
						</tr>
						<tr>
							<td colspan="2" class="header"><strong>Force Inclusion</strong></td>
						</tr>
						<tr>
							<td colspan="2" class="body">
								<input type="checkbox" class="body" name="forceinclude[<?= $row['picid'] ?>]" value="y"<?= $forceincl; ?> /> Yes, all exams will contain this pic
							</td>
						</tr>

						<tr>
							<td colspan="2" class="header"><strong>Picture Information (<em>Possibly Fake</em>)</strong></td>
						</tr>
						<tr>
							<td class="body"><strong>Gender:</strong></td>
							<td class="body">
								<input type="radio" class="body" name="gender[<?= $row['picid']; ?>]" value="female"<?= $gender['female']; ?> /> Female
								<input type="radio" class="body" name="gender[<?= $row['picid']; ?>]" value="male"<?= $gender['male']; ?> /> Male
							</td>
						</tr>
						<tr>
							<td class="body"><strong>Age:</strong></td>
							<td class="body">
								<input type="text" class="body" name="age[<?= $row['picid']; ?>]" value="<?= htmlentities($age); ?>" size="3" maxlength="3" />
							</td>
						</tr>
						<tr>
							<td class="body"><strong>Pic Description:</strong></td>
							<td class="body">
								<input type="text" class="body" name="desc[<?= $row['picid']; ?>]" value="<?= htmlentities($desc); ?>" size="40" maxlength="255" />
							</td>
						</tr>
					</table>

				</td>
			</tr>
		<? endforeach; ?>

		<? if (count($rows) > 0): ?>
			<tr>
				<td colspan="2" class="header" style="text-align: center; width: 100%;"><strong>SUBMIT CHANGES</strong></td>
			</tr>
			<tr>
				<td colspan="2" class="body" style="text-align: center;">
					<input type="submit" class="body" style="width: 200px;" name="sbmt" value="Submit Changes" />
				</td>
			</tr>
		<? else: ?>
			<tr><td class="body">There are no pics waiting to enter the pic pool.</td></tr>
		<? endif; ?>
	</table>

</form>

<?
			$this->pagefooter();
		}

		function picmanager_editcurrent ($page) {
			$errmsgs = array();

			list($step) = cleanvar('step', 3, '::digit::', 25, 25);
			list($gotoid) = cleanvar('gotoid', 10, '::digit::', null, null);
			list($desc) = cleanvar('desc', 1, '::digit::', 0, 0);
			list($desc, $desctxt) = (int)$desc === 1 ? array(1, 'DESC') : array(0, 'ASC');
			list($sort) = cleanvar('sort', 25, '::char::', null, null);
			list($showtype) = cleanvar('showtype', 9, '::char::', null, null);
			list($retireid) = cleanvar('retire', 10, '::digit::', null, null);
			list($last) = cleanvar('last', 12, '0-9\\-', -1, -1);

			list($inclans, $inclcats, $inclgends) = cleanvarsarray(
				array('inclans', 0, 12, 'a-z ', null, null),
				array('inclcats', 0, 3, '::digit::', null, null),
				array('inclgends', 0, 6, '::char::', null, null)
			);

			$prev = array(-2);
			$next = -2;

			$fields = array();

			list($step, $fields['step']) = (int)$step < 1 ? array(5, '5') : ($step > 100 ? array(100, '100') : array((int)$step, (string)$step));
			$fields['desc'] = ($desc === 1 ? ' checked="checked"' : '');

			$sortable = array(
				'picid' => 'picture id', 'picage' => 'given pic age', 'acceptcnt' => 'accept count',
				'denycnt' => 'deny count', 'perccorrect' => 'percent correct'
			);

			if (!isset($sortable[$sort])) $sort = 'picid';
			foreach (array_keys($sortable) as $key) $fields['sort'][$key] = '';
			$fields['sort'][$sort] = ' selected="selected"';

			$gotoid = ((int)$gotoid < 1 or (int)$gotoid > 4294967295) ? null : (int)$gotoid;

			$validans = array('accept', 'deny', 'instant fail');
			foreach (array_values($validans) as $val) $fields['inclans'][$val] = '';
			foreach ($inclans as $key => $val) {
				if (in_array($val[0], $validans) === false) {
					unset($inclans[$key]);
				}
				else {
					$inclans[$key] = $val[0];
					$fields['inclans'][$val[0]] = ' selected="selected";';
				}
			}
			if (count($inclans) === 0) { $inclans[] = 'accept'; $fields['inclans']['accept'] = ' selected="selected"'; }

			foreach (array_values($this->categories) as $val) $fields['inclcats'][$val['catid']] = '';
			foreach ($inclcats as $key => $val) {
				if (!isset($this->categories[$val[0] - 1]))
					unset($inclcats[$key]);
				else
					$inclcats[$key] = $val[0];
					$fields['inclcats'][$val[0]] = ' selected="selected"';
			}
			if (count($inclcats) === 0) { $inclcats[] = '1'; $fields['inclcats']['1'] = ' selected="selected"'; }

			$validgends = array('male', 'female');
			foreach (array_values($validgends) as $val) $fields['inclgends'][$val] = '';
			foreach ($inclgends as $key => $val) {
				if (in_array($val[0], $validgends) === false)
					unset($inclgends[$key]);
				else
					$inclgends[$key] = $val[0];
					$fields['inclgends'][$val[0]] = ' selected="selected";';
			}
			if (count($inclgends) === 0) {
				$inclgends = array('male', 'female');
				$fields['inclgends']['male'] = $fields['inclgends']['female'] = ' selected="selected"';
			}

			$validshowtypes = array('all', 'retired', 'forceincl');
			foreach (array_values($validshowtypes) as $val) $fields['showtype'][$val] = '';
			if (in_array($showtype, $validshowtypes) === false) {
				$showtype = 'all';
				$fields['showtype']['all'] = ' checked="checked"';
			}
			else {
				$fields['showtype'][$showtype] = ' checked="checked"';
			}

			$this->pageheader();

			if ($retireid > 0) {
				$res = $this->db->prepare_query(
					'SELECT picid, picfilename, modcategory FROM exampics WHERE picid=# AND isretired=?',
					$retireid, 'n'
				);
				$row = $res->fetchrow();

				if ($row !== false) {
					$res = $this->db->prepare_query(
						'SELECT COUNT(*) - exampiccategories.catcnt AS allowed FROM exampics, exampiccategories WHERE exampics.modcategory=# AND exampics.modanswer IN(?, ?, ?) AND exampics.isretired=? AND exampiccategories.catid=#',
						$row['modcategory'], 'accept', 'deny', 'instant fail', 'n', $row['modcategory']
					);
					$allowed = $res->fetchfield();

					if ($allowed === null or $allowed <= 0) {
						$errmsgs[] = "- Cannot retire pic id #$retireid with current exam config. Decrease exam inclusion for category '{$this->categories[$row['modcategory'] - 1]['catlabel']}' by 1 or upload 1 new pic for this category.";
					}
					else {
						$res = $this->db->prepare_query(
							'SELECT picfilename FROM exampics WHERE picfilename=?', substr($row['picfilename'], 0, 40) . '.ret.jpg'
						);
						$retiredexists = $res->fetchrow();

						$newfilename = substr($row['picfilename'], 0, 40) . '.ret.jpg';
						$this->db->prepare_query(
							'UPDATE exampics SET isretired=?, picfilename=? WHERE picid=#',
							'y', $newfilename, $row['picid']
						);

						if ($retiredexists !== false) {
							unlink("{$this->examconfig['fspicdir']}{$row['picfilename']}");
						}
						else {
							rename("{$this->examconfig['fspicdir']}{$row['picfilename']}", "{$this->examconfig['fspicdir']}${newfilename}");
						}

						$errmsgs[] = "- Pic id #${retireid} successfully retired.";
					}
				}
			}

?>

<form action="<?= $_SERVER['PHP_SELF']; ?>" method="post">
	<input type="hidden" name="admin" value="1" />
	<input type="hidden" name="page" value="picmanager_editcurrent" />
	<input type="hidden" name="last" value="-1" />

	<table cellpadding="3" cellspacing="0" align="center" style="width: 100%;">
		<tr>
			<td class="body2" colspan="4">
				<strong>Goto Pic ID: </strong>
				<input type="text" class="body" name="gotoid" value="" size="10" maxlength="10" />
				<input type="submit" class="body" name="sbmt" value="Go" />
			</td>
		</tr>
		<tr>
			<td class="body2" valign="top">
				<strong>Show Answers:</strong><br />
				<select class="body" name="inclans[]" size="3" multiple="multiple" style="width: 100%;">
					<option value="accept"<?= $fields['inclans']['accept']; ?>>Accept</option>
					<option value="deny"<?= $fields['inclans']['deny']; ?>>Deny</option>
					<option value="instant fail"<?= $fields['inclans']['instant fail']; ?>>Instant Fail</option>
				</select>
			</td>
			<td class="body2" valign="top">
				<strong>Show Categories:</strong><br />
				<select class="body" name="inclcats[]" size="3" multiple="multiple">
					<? foreach ($this->categories as $val): ?>
						<option value="<?= $val['catid']; ?>"<?= $fields['inclcats'][$val['catid']]; ?> /><?= ucfirst($val['catlabel']); ?></option>
					<? endforeach; ?>
				</select>
			</td>
			<td class="body2" valign="top">
				<strong>Show Genders:</strong><br />
				<select class="body" name="inclgends[]" size="3" multiple="multiple" style="width: 100%;">
					<option value="male"<?= $fields['inclgends']['male']; ?>>Male</option>
					<option value="female"<?= $fields['inclgends']['female']; ?>>Female</option>
				</select>
			</td>
			<td class="body2" valign="top">
				<strong>Only show this type:</strong><br />
				<input type="radio" class="body" id="typeall" name="showtype" value="all"<?= $fields['showtype']['all']; ?> /> <label for="typeall">Show all but retired</label><br />
				<input type="radio" class="body" id="typeretired" name="showtype" value="retired"<?= $fields['showtype']['retired']; ?> /> <label for="typeretired">Show only retired</label><br />
				<input type="radio" class="body" id="typeforced" name="showtype" value="forceincl"<?= $fields['showtype']['forceincl']; ?> /> <label for="typeforced">Show only force-included</label>
			</td>
		</tr>
		<tr>
			<td class="body2" >
				<strong>Sort By:</strong>
				<select name="sort" class="body">
					<? foreach ($sortable as $key => $val): ?>
						<option value="<?= $key; ?>"<?= $fields['sort'][$key]; ?>><?= ucfirst($val); ?></option>
					<? endforeach; ?>
				</select>
			</td>
			<td class="body2">
				<input type="checkbox" class="body" id="descorder" name="desc" value="1"<?= $fields['desc']; ?> /> <label for="descorder">Descending order</label>
			</td>
			<td class="body2">
				<strong>Results Per Page:</strong> <input type="text" class="body" name="step" value="<?= $fields['step']; ?>" size="3" maxlength="3" />
			</td>
			<td class="body2" align="center">
				<input type="submit" class="body" name="sbmt" value="Submit" />
			</td>
		</tr>
	</table>
</form>

<? if (count($errmsgs) > 0): ?>
	<table style="width: 100%; margin-bottom: 10px;" cellpadding="0" cellspacing="0">
		<tr>
			<td class="msg" style="padding: 3px; font-weight: bold;">What happened:</td>
		</tr>
		<tr>
			<td class="msg" style="padding: 3px;"><?= join("<br />", $errmsgs); ?></td>
		</tr>
	</table>
<? endif; ?>

<?
			if (isset($gotoid)) {
				$res = $this->db->prepare_query(
					"SELECT *, IF(modanswer=?, acceptcnt / (acceptcnt + denycnt), denycnt / (acceptcnt + denycnt)) * 100 AS perccorrect FROM exampics WHERE picid=# AND modanswer IN (?, ?, ?)",
					'accept', $gotoid, 'accept', 'deny', 'instant fail'
				);
			}
			else {
				if ($showtype == 'all') {
					$res = $this->db->prepare_query(
						"SELECT *, IF(modanswer=?, acceptcnt / (acceptcnt + denycnt), denycnt / (acceptcnt + denycnt)) * 100 AS perccorrect FROM exampics WHERE modcategory IN (#) AND modanswer IN (?) AND isretired=? AND picgender IN (?) ORDER BY $sort $desctxt, picid ASC",
						'accept', $inclcats, $inclans, 'n', $inclgends
					);
				}
				elseif ($showtype == 'retired') {
					$res = $this->db->prepare_query(
						"SELECT *, IF(modanswer=?, acceptcnt / (acceptcnt + denycnt), denycnt / (acceptcnt + denycnt)) * 100 AS perccorrect FROM exampics WHERE modcategory IN (#) AND isretired=? AND picgender IN (?) ORDER BY $sort $desctxt, picid ASC",
						'accept', $inclcats, 'y', $inclgends
					);
				}
				else {
					$res = $this->db->prepare_query(
						"SELECT *, IF(modanswer=?, acceptcnt / (acceptcnt + denycnt), denycnt / (acceptcnt + denycnt)) * 100 AS perccorrect FROM exampics WHERE modcategory IN (#) AND modanswer IN (?) AND isretired=? AND forceinclude=? AND picgender IN (?) ORDER BY $sort $desctxt, picid ASC",
						'accept', $inclcats, $inclans, 'n', 'y', $inclgends
					);
				}
			}

			$last = (int)$last;

			if ($last != -1) {
				$cnt = 0;
				while ($row = $res->fetchrow()) {
					++$cnt;
					if ($row['picid'] == $last) break;
					$prev[] = $row['picid'];
					if (count($prev) > $step) array_shift($prev);
				}
				if ($cnt >= 1 and $cnt <= $step) $prev[0] = -1;
			}
			$prev = $prev[0];

			$ttlshown = 0;
?>

<table cellspacing="10" cellpadding="10" align="center" style="width: 100%;">

<? while ($row = $res->fetchrow()): ?>
	<? ++$ttlshown; ?>
	<tr>
		<td colspan="2" class="header" style="text-align: center;">
			<strong>PIC ID: <?= $row['picid']; ?></strong>
		</td>
	</tr>
	<tr>
		<td class="body2" valign="center" align="center">
			<img src="<?= $this->examconfig['webpicdir']; ?><?= $row['picfilename'] ?>" /><br /><br />
			<? if ($row['isretired'] == 'n'): ?>
				<?
					$link = "{$_SERVER['PHP_SELF']}?admin=1&amp;page=picmanager_editcurrent&amp;" .
						"sort=${sort}&amp;desc=${desc}&amp;step=${step}&amp;showtype=${showtype}&amp;";
					foreach ($inclgends as $val) $link .= rawurlencode("inclgends[]") . "=${val}&amp;";
					foreach ($inclans as $val) $link .= rawurlencode("inclans[]") . "=" . rawurlencode($val) . "&amp;";
					foreach ($inclcats as $val) $link .= rawurlencode("inclcats[]") . "=${val}&amp;";
				?>
				<a class="body" href="javascript:confirmLink('<?= $link; ?>retire=<?= $row['picid']; ?>&amp;last=<?= $last; ?>', 'retire this pic?');">Retire This Pic</a>
			<? endif; ?>
		</td>
		<td class="body" valign="top">

			<table cellspacing="0" cellpadding="3" style="width: 100%;">
				<tr>
					<td colspan="2" class="header"><strong>General Info</strong></td>
				</tr>
				<tr>
					<td class="body"><strong>Pic ID:</strong></td>
					<td class="body"><?= $row['picid']; ?></td>
				</tr>
				<tr>
					<td class="body"><strong>Uploaded:</strong></td>
					<td class="body"><?= strftime('%b %d, %Y @ %I:%M %p', $row['addedtime']); ?></td>
				</tr>
				<tr>
					<td colspan="2" class="header"><strong>Pic's Exam Stats</strong></td>
				</tr>
				<tr>
					<td class="body"><strong>Correct Answer:</strong></td>
					<td class="body"><?= ucfirst($row['modanswer']); ?></td>
				</tr>
				<tr>
					<td class="body"><strong>Accept Count:</strong></td>
					<td rowspan="2">
						<table cellpadding="0" cellspacing="0">
							<tr>
								<td class="body" style="padding-right: 10px;"><?= $row['acceptcnt']; ?></td>
								<td class="body2" rowspan="2" style="width: 100%; padding: 3px; text-align: center; vertical-align: center;">
									<?= $row['perccorrect'] === null ? 'n/a' : $row['perccorrect'] . '% correct'; ?>
								</td>
							</tr>
							<tr>
								<td class="body" style="padding-right: 10px;"><?= $row['denycnt']; ?></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td class="body"><strong>Deny Count:</strong></td>
				</tr>
				<tr>
					<td colspan="2" class="header"><strong>Deny/Instant Fail Details</strong></td>
				</tr>
				<tr>
					<td class="body"><strong>Category:</strong></td>
					<td class="body">
						<? if ($row['modcategory'] == 0): ?>
							n/a
						<? else: ?>
							<?= ucfirst($this->categories[$row['modcategory'] - 1]['catlabel']); ?>
						<? endif; ?>
					</td>
				</tr>
				<tr>
					<td class="body" style="vertical-align: top;"><strong>Specific Reason:</strong></td>
					<td class="body" style="white-space: nowrap;">
						<? if ($row['modcategory'] == 0): ?>
							N/A
						<? else: ?>
						<?= wordwrap(htmlentities($row['modreason'], ENT_NOQUOTES), 40, "<br />", true); ?>
						<? endif; ?>
					</td>
				</tr>
				<tr>
					<td colspan="2" class="header"><strong>Force Inclusion</strong></td>
				</tr>
				<tr>
					<td colspan="2" class="body">
						<? if ($row['forceinclude'] == 'y'): ?>
							<strong>Yes.</strong> This pic will be shown on all exams.
						<? else: ?>
							<strong>No.</strong> This pic will be shown on random exams.
						<? endif; ?>
					</td>
				</tr>
				<tr>
					<td colspan="2" class="header"><strong>Picture Information (<em>Possibly Fake</em>)</strong></td>
				</tr>
				<tr>
					<td class="body"><strong>Gender:</strong></td>
					<td class="body"><?= ucfirst($row['picgender']); ?></td>
				</tr>
				<tr>
					<td class="body"><strong>Age:</strong></td>
					<td class="body"><?= $row['picage']; ?></td>
				</tr>
				<tr>
					<td class="body" valign="top"><strong>Pic Description:</strong></td>
					<td class="body" style="white-space: nowrap;">
						<?= wordwrap(htmlentities($row['piccomment'], ENT_NOQUOTES), 40, "<br />", true); ?>
					</td>
				</tr>
			</table>
		</td>
	</tr>

	<? if ($ttlshown == $step) { $next = $row['picid']; break; } ?>
<? endwhile; ?>

<? if ($ttlshown == 0): ?>
	<tr><td class="body" colspan="2">The search criteria entered produced no pic results.</td></tr>
<? endif; ?>

	<tr>
		<td colspan="2" class="body" style="text-align: center;">
			<? if ($next !== -2 and $this->db->fetchrow() === false) $next = -2; ?>
			<?
				$link = "{$_SERVER['PHP_SELF']}?admin=1&amp;page=picmanager_editcurrent&amp;" .
					"sort=${sort}&amp;desc=${desc}&amp;step=${step}&amp;showtype=${showtype}&amp;";
				foreach ($inclgends as $val) $link .= rawurlencode("inclgends[]") . "=${val}&amp;";
				foreach ($inclans as $val) $link .= rawurlencode("inclans[]") . "=" . rawurlencode($val) . "&amp;";
				foreach ($inclcats as $val) $link .= rawurlencode("inclcats[]") . "=${val}&amp;";
			?>

			<? if ($prev !== -2): ?>
				<a class="body" href="<?= $link; ?>last=<?= $prev; ?>">&lt;&lt; Previous</a>
			<? else: ?>
				<strike>&lt;&lt; Previous</strike>
			<? endif; ?>

			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:::&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

			<? if ($next !== -2): ?>
				<a class="body" href="<?= $link; ?>last=<?= $next; ?>">Next &gt;&gt;</a>
			<? else: ?>
				<strike>Next &gt;&gt;</strike>
			<? endif; ?>
		</td>
	</tr>
</table>

<?
			$this->pagefooter();
		}

		function usermanager_index ($page) {
			global $messaging;
			$errmsgs = array();

			list($step) = cleanvar('step', 3, '::digit::', 10, 10);
			list($orderupdated) = cleanvar('orderupdated', 1, '::digit::', 0, 0);
			list($commentsupdated) = cleanvar('commentsupdated', 1, '::digit::', 0, 0);
			list($adduser) = cleanvar('adduser', 100000, '::english_nonewline::', null, null);
			list($last) = cleanvar('last', 12, '0-9\\-', -1, -1);
			list($gotoid) = cleanvar('gotoid', 25, '::english_nonewline::', null, null);

			list($statuses, $showtypes, $positions, $comments, $revokes, $invites, $archives, $makemods) = cleanvarsarray(
				array('status', 0, 15, 'a-z ', null, null),
				array('showtype', 0, 1, '::char::', null, null),
				array('pos', 0, 4, '0-9.', null, null),
				array('comments', 0, 255, '::english_newline::', '', ''),
				array('revoke', 0, 12, '::digit::', null, null),
				array('invite', 0, 12, '::digit::', null, null),
				array('archive', 0, 12, '::digit::', null, null),
				array('makemod', 0, 12, '::digit::', null, null)
			);

			$fields = array();

			list($step, $fields['step']) = (int)$step < 1 ? array(10, '10') :
				((int)$step > 100 ? array(100, '100') : array((int)$step, (string)$step));

			$validstatus = array('waiting', 'invite sent', 'invite declined', 'invite revoked', 'exam started', 'passed', 'failed');
			foreach (array_values($validstatus) as $val) $fields['status'][$val] = '';
			foreach ($statuses as $key => $val) {
				if (in_array($val[0], $validstatus) === false) {
					unset($statuses[$key]);
				}
				else {
					$statuses[$key] = $val[0];
					$fields['status'][$val[0]] = ' checked="checked"';
				}
			}
			if (count($statuses) === 0) { $statuses[] = 'waiting'; $fields['status']['waiting'] = ' checked="checked"'; }

			$validshowtypes = array('y', 'n');
			foreach (array_values($validshowtypes) as $val) $fields['showtype'][$val] = '';
			foreach ($showtypes as $key => $val) {
				if (in_array($val[0], $validshowtypes) === false) {
					unset($showtypes[$key]);
				}
				else {
					$showtypes[$key] = $val[0];
					$fields['showtype'][$val[0]] = ' checked="checked"';
				}
			}
			if (count($showtypes) === 0) { $showtypes[] = 'n'; $fields['showtype']['n'] = ' checked="checked"'; }

			foreach ($positions as $key => $val) $positions[$key] = $val[0];

			if ((int)$orderupdated === 1) {
				if (count(array_unique($positions)) !== count($positions)) {
					$errmsgs[] = "- You cannot have the same ordering position number for more than one user. Please fix it.";
				}
				else {
					$userids = array_keys($positions);
					$posids = array_values($positions);
					array_multisort($posids, SORT_NUMERIC, $userids);

					foreach (range(1, count($userids)) as $key)
						$this->db->prepare_query('UPDATE examusers SET posid=# WHERE userid=#', $key, $userids[$key - 1]);

					$positions = array();
					$errmsgs[] = "- Waiting list positioning numbers updated.";
				}
			}

			if ((int)$commentsupdated === 1) {
				foreach ($comments as $examid => $comment) {
					$this->db->prepare_query('UPDATE examresults SET comments=? WHERE examid=#', $comment[0], $examid);
				}
				$errmsgs[] = "- Updated comments for all exams listed on this page.";
			}

			if (!is_null($adduser)) {
				foreach (preg_split("/[\n\r]+/", $adduser) as $newuser) {
					$username = null;
					$userid = false;

					if (is_numeric($newuser)) {
						$username = getUserName($newuser);
						$userid = $newuser;
					}
					else {
						$userid = getUserID($newuser);
						$username = getUserName($userid);
					}

					if ($username !== null and $userid !== false) {
						$res = $this->db->prepare_query('SELECT examstatus FROM examresults WHERE userid=# AND isarchived=?', $userid, 'n');
						$alreadylisted = false;
						while ($row = $res->fetchrow()) {
							switch ($row['examstatus']) {
								case 'waiting':
									$errmsgs[] = "- User '${username}' is already on the waiting list. Addition aborted.";
									$alreadylisted = true;
									break 2;
								case 'invite sent':
									$errmsgs[] = "- User '${username}' has already been sent an invite. Addition aborted.";
									$alreadylisted = true;
									break 2;
								case 'exam started':
									$errmsgs[] = "- User '${username}' has already begun taking the exam. If this exam is stale, please revoke it before adding the user again.";
									$alreadylisted = true;
									break 2;
							}
						}

						if ($alreadylisted === false) {
							$res = $this->db->prepare_query('SELECT COUNT(*) AS userexists FROM examusers WHERE userid=#', $userid);
							$userexists = $res->fetchfield();

							$this->db->prepare_query('INSERT INTO examresults SET userid=#', $userid);
							if ((int)$userexists === 0)
								$this->db->prepare_query('INSERT INTO examusers SELECT #, IF(COUNT(*) = 0, 1, MAX(posid) + 1) FROM examusers', $userid);

							$errmsgs[] = "- Added '${username}' (userid #$userid) to the waiting list.";
						}
					}
					else {
						$errmsgs[] = "- Could not add user '" . htmlentities($newuser) . "': userid or username does not exist.";
					}
				}
			}

			foreach ($revokes as $examid => $userid) {
				if ($userid[0] !== $userid[1] or is_null($userid[0])) continue;

				if (!isset($comments[$examid]) or is_null($comments[$examid][0]) or $comments[$examid][0] === '') {
					$errmsgs[] = "- Cannot revoke exam from '" . getUserName($userid[0]) . "' without comments.";
					continue;
				}

				$res = $this->db->prepare_query('SELECT examstatus FROM examresults WHERE examid=#', $examid);
				$examstatus = $res->fetchfield();

				if ($examstatus === 'waiting') {
//					$messaging->deliverMsg($userid[0], 'exam invite revoked', "You have been removed from the pic mod exam waiting list. You will not be eligible to take the exam. Reason why you were removed:\n\n{$comments[$examid][0]}", 0, "Nexopia", 0, false, false);
					$errmsgs[] = "- Removed '" . getUserName($userid[0]) . "' from the waiting list.";
				}
				elseif ($examstatus === 'invite sent' or $examstatus === 'exam started') {
//					$messaging->deliverMsg($userid[0], 'exam invite revoked', "Your invitation to take the pic mod exam has been revoked. You will not be able to take the exam. Reason why your invitation was revoked:\n\n{$comments[$examid][0]}", 0, "Nexopia", 0, false, false);
					$errmsgs[] = "- Revoked exam invite for '" . getUserName($userid[0]) . "'.";
				}
				else {
					$errmsgs[] = "- Cannot revoke exam invite for '" . getUserName($userid[0]) . "'.";
					continue;
				}

				$this->db->prepare_query('UPDATE examresults SET examstatus=? WHERE examid=#', 'invite revoked', $examid);
				$this->db->prepare_query('UPDATE examusers SET posid=# WHERE userid=#', 0, $userid[0]);
			}

			foreach ($invites as $examid => $userid) {
				if ($userid[0] !== $userid[1] or is_null($userid[0])) continue;
				$invitetxt = getStaticValue('picmodexam_emailinvite');
				$messaging->deliverMsg($userid[0], 'pic mod exam invite', $invitetxt, 0, "Nexopia", 0, false, true);
				$this->db->prepare_query('UPDATE examresults SET examstatus=? WHERE examid=#', 'invite sent', $examid);
				$this->db->prepare_query('UPDATE examusers SET posid=# WHERE userid=#', 0, $userid[0]);
				$errmsgs[] = "- Sent invite to '" . getUserName($userid[0]) . "'.";
			}

			foreach ($archives as $examid => $userid) {
				if ($userid[0] !== $userid[1] or is_null($userid[0])) continue;
				$this->db->prepare_query('UPDATE examresults SET isarchived=? WHERE examid=#', 'y', $examid);
				$errmsgs[] = "- Archived exam id #${examid}, belonging to " . getUserName($userid[0]) . ".";
			}

			if (count($makemods) > 0) {
				$preload = '';
				$modsmade = array();
				foreach ($makemods as $examid => $userid) {
					if ($userid[0] !== $userid[1] or is_null($userid[0]) or isset($modsmade[$userid[0]])) continue;
					$modsmade[$userid[0]] = 1;
					$preload .= getUserName($userid[0]) . "\n";
				}

				if ($preload !== '') {
					$preload = htmlentities(rtrim($preload));
					$errmsgs[] = "- List of users to make pic mods created. <form action=\"/adminaddpicmods.php\" method=\"post\"><input type=\"hidden\" name=\"preload\" value=\"${preload}\" /><input type=\"submit\" class=\"body\" name=\"sbmt\" value=\"Make Mods\" /></form>";
				}
			}

			$prev = -1;
			$next = -1;

			$this->pageheader();
?>

<form action="<?= $_SERVER['PHP_SELF']; ?>" method="post">
	<input type="hidden" name="admin" value="1" />
	<input type="hidden" name="page" value="usermanager_index" />
	<input type="hidden" name="last" value="-1" />

	<table cellpadding="3" cellspacing="0" align="center" style="width: 100%;">
		<tr>
			<td class="body2">
				<strong>Show Statuses:</strong>
			</td>
			<td class="body2" colspan="2">
				<? foreach (array_values($validstatus) as $val): ?>
					<? $htmlid = 'status' . str_replace(' ', '', $val); ?>
					<input type="checkbox" class="body" id="<?= $htmlid; ?>" name="status[]" value="<?= $val; ?>"<?= $fields['status'][$val]; ?> /> <label for="<?= $htmlid; ?>"><?= ucfirst($val); ?></label>
				<? endforeach; ?>
			</td>
		</tr>
		<tr>
			<td class="body2">
				<strong>Show Types:</strong>
			</td>
			<td class="body2">
				<input type="checkbox" class="body" id="showtypen" name="showtype[]" value="n"<?= $fields['showtype']['n']; ?> /> <label for="showtypen">Unarchived</label>
				<input type="checkbox" class="body" id="showtypey" name="showtype[]" value="y"<?= $fields['showtype']['y']; ?> /> <label for="showtypey">Archived</label>
			</td>
			<td class="body2">
				<strong>Results Per Page:</strong> <input type="text" class="body" name="step" value="<?= $fields['step']; ?>" size="3" maxlength="3" />
			</td>
		</tr>
	</table>

	<table cellpadding="3" cellspacing="0" align="center" style="width: 100%;">
		<tr>
			<td class="body2" valign="top">
				<strong>Add Users (ID/Name):</strong><br />
				<textarea class="body" name="adduser" rows="1" cols="25" /></textarea>
			</td>
			<td class="body2" valign="top">
				<strong>Goto Username:</strong><br />
				<input type="text" class="body" name="gotoid" value="" size="15" maxlength="50" />
			</td>
			<td class="body2">
				<input type="submit" class="body" name="sbmt" value="Submit" />
			</td>
		</tr>
	</table>
</form>

<? if (count($errmsgs) > 0): ?>
	<table style="width: 100%; margin-bottom: 10px;" cellpadding="0" cellspacing="0">
		<tr>
			<td class="msg" style="padding: 3px; font-weight: bold;">What happened:</td>
		</tr>
		<tr>
			<td class="msg" style="padding: 3px;"><?= join("<br />", $errmsgs); ?></td>
		</tr>
	</table>
<? endif; ?>

<?
			$allowedit = false;
			if (count($statuses) === 1 and in_array('waiting', $statuses) === true and count($showtypes) === 1 and in_array('n', $showtypes) === true and is_null($gotoid[0])) $allowedit = true;

			$showallrows = false;
			$rows = array();

			if (!is_null($gotoid[0])) {
				$showallrows = true;
				$userid = getUserID($gotoid);
				$res = $this->db->prepare_query(
					"SELECT examusers.posid, examresults.* FROM examusers, examresults WHERE examusers.userid=# AND examresults.userid=# ORDER BY examresults.examid DESC",
					$userid, $userid
				);
			}
			else {
				if ($allowedit === false) {
					$res = $this->db->prepare_query(
						"SELECT examusers.posid, examresults.* FROM examusers, examresults WHERE examusers.userid=examresults.userid AND examresults.examstatus IN (?) AND examresults.isarchived IN (?) ORDER BY posid, examid DESC LIMIT #,#",
						$statuses, $showtypes, ($last > -1 ? $last : 0), $step + 1
					);
				}
				else {
					$showallrows = true;
					$res = $this->db->prepare_query(
						"SELECT examusers.posid, examresults.* FROM examusers, examresults WHERE examusers.userid=examresults.userid AND examresults.examstatus IN (?) AND examresults.isarchived IN (?) ORDER BY posid, examid DESC",
						$statuses, $showtypes
					);
				}
			}

			$rows = $res->fetchrowset();

			global $usersdb;
			foreach ($rows as $key => $data) {
				$rows[$key]['username'] = getUserName($data['userid']);
				$res = $usersdb->prepare_query('SELECT age FROM users WHERE userid=%', $data['userid']);
				$rows[$key]['age'] = $res->fetchfield();
			}

			$lastuser = '';
			$ttlshown = 0;
			$highlight = array('body', 'body2');
?>

<form action="<?= $_SERVER['PHP_SELF']; ?>" method="post">
	<input type="hidden" name="admin" value="1" />
	<input type="hidden" name="page" value="usermanager_index" />
	<? foreach ($statuses as $status): ?><input type="hidden" name="status[]" value="<?= $status; ?>" /><? endforeach; ?>
	<? foreach ($showtypes as $showtype): ?><input type="hidden" name="showtype[]" value="<?= $showtype; ?>" /><? endforeach; ?>
	<input type="hidden" name="step" value="<?= $step; ?>" />
	<input type="hidden" id="orderupdated" name="orderupdated" value="0" />
	<input type="hidden" id="commentsupdated" name="commentsupdated" value="0" />

	<table cellspacing="0" cellpadding="5" align="center" style="width: 100%;">
		<tr>
			<td class="header" style="font-weight: bold;">Action</td>
			<td class="header" style="font-weight: bold;">Position</td>
			<td class="header" style="font-weight: bold;">Username</td>
			<td class="header" style="font-weight: bold;">Age</td>
			<td class="header" style="font-weight: bold;">Exam Status</td>
			<td class="header" style="font-weight: bold;">Exam Score</td>
			<td class="header" style="font-weight: bold;">Exam Results</td>
			<td class="header" style="font-weight: bold;">Time Taken</td>
			<td class="header" style="font-weight: bold;">Comments (not more than 255 chars)</td>
		</tr>

		<? while ($row = array_shift($rows)): ?>
			<tr>
				<td class="<?= $highlight[0]; ?>" valign="top">
					<? if ($row['isarchived'] == 'n'): ?>
						<? switch ($row['examstatus']): ?><? case 'waiting': ?>
								<input type="checkbox" class="body" id="invite[<?= $row['examid']; ?>]" name="invite[<?= $row['examid']; ?>]" value="<?= $row['userid']; ?>" /> <label for="invite[<?= $row['examid']; ?>]">Invite</label><br />
								<input type="checkbox" class="body" id="revoke[<?= $row['examid']; ?>]" name="revoke[<?= $row['examid'] ?>]" value="<?= $row['userid']; ?>" /> <label for="revoke[<?= $row['examid']; ?>]">Revoke</label><br />
								<? break; ?>
							<? case 'invite sent': ?>
							<? case 'exam started': ?>
								<input type="checkbox" class="body" id="revoke[<?= $row['examid']; ?>]" name="revoke[<?= $row['examid'] ?>]" value="<?= $row['userid']; ?>" /> <label for="revoke[<?= $row['examid']; ?>]">Revoke</label><br />
								<? break; ?>
							<? case 'invite declined': ?>
							<? case 'invite revoked': ?>
							<? case 'passed': ?>
							<? case 'failed': ?>
								<input type="checkbox" class="body" id="archive[<?= $row['examid']; ?>]" name="archive[<?= $row['examid'] ?>]" value="<?= $row['userid']; ?>" /> <label for="archive[<?= $row['examid']; ?>]">Archive</label><br />
								<? break; ?>
						<? endswitch; ?>
					<? endif; ?>
					<? global $mods; if (! $mods->isMod($row['userid'], MOD_PICS)): ?>
						<input type="checkbox" class="body" id="makemod[<?= $row['examid']; ?>]" name="makemod[<?= $row['examid']; ?>]" value="<?= $row['userid']; ?>" /> <label for="makemod[<?= $row['examid']; ?>]">Make Mod</label>
					<? endif; ?>
				</td>
				<td class="<?= $highlight[0]; ?>" valign="top">
					<? if ($allowedit == true): ?>
						<? if ($lastuser != $row['userid']): ?>
							<? $lastuser = $row['userid']; ?>
							<? $val = isset($positions[$row['userid']]) ? $positions[$row['userid']] : $row['posid']; ?>
							<input type="text" class="body" name="pos[<?= $row['userid']; ?>]" value="<?= htmlentities($val); ?>" size="4" maxlength="4" onChange="document.getElementById('orderupdated').value = '1';" />
						<? else: ?>
							&nbsp;&nbsp;
						<? endif; ?>
					<? else: ?>
						<? if ($row['posid'] > 0): ?><?= $row['posid']; ?><? else: ?>N/A<? endif; ?>
					<? endif; ?>
				</td>
				<td class="<?= $highlight[0]; ?>" valign="top">
					<a class="body" href="/profile.php?uid=<?= $row['userid']; ?>"><?= htmlentities($row['username'], ENT_NOQUOTES); ?></a>
				</td>
				<td class="<?= $highlight[0]; ?>" valign="top">
					<?= $row['age']; ?>
				</td>
				<td class="<?= $highlight[0]; ?>" valign="top"><?= ucfirst($row['examstatus']); ?></td>
				<td class="<?= $highlight[0]; ?>" valign="top">
					<? if ($row['examtimeend'] > 0): ?>
						<?= sprintf('%.2f', $row['examscore']); ?>%
					<? else: ?>
						N/A
					<? endif; ?>
				</td>
				<td class="<?= $highlight[0]; ?>" valign="top">
					<? if ($row['examtimeend'] > 0): ?>
						<a class="body" href="<?= $_SERVER['PHP_SELF']; ?>?admin=1&amp;page=display_exam&amp;examid=<?= $row['examid']; ?>">Exam #<?= $row['examid']; ?></a>
					<? else: ?>
						N/A
					<? endif; ?>
				</td>
				<td class="<?= $highlight[0]; ?>" valign="top" style="white-space: nowrap;">
					<?= $row['examtimestart'] > 0 ? strftime('%b %d, %Y @ %I:%M %p', $row['examtimestart']) : 'N/A'; ?><br />
					<?= $row['examtimeend'] > 0 ? strftime('%b %d, %Y @ %I:%M %p', $row['examtimeend']) : 'N/A'; ?>
				</td>
				<td class="<?= $highlight[0]; ?>" valign="top">
					<textarea class="body" name="comments[<?= $row['examid']; ?>]" rows="2" cols="25" onChange="document.getElementById('commentsupdated').value = '1';"><?= htmlentities($row['comments'], ENT_NOQUOTES); ?></textarea>
				</td>
			</tr>

			<? $highlight = array_reverse($highlight); ?>
			<? if (++$ttlshown == $step and $showallrows == false) { $next = $ttlshown; break; } ?>
		<? endwhile; ?>

		<? if ($ttlshown == 0): ?>
		<tr><td class="body" colspan="9">The search criteria entered produced no results.</td></tr>
		<? endif; ?>

		<? if ($ttlshown > 0): ?>
			<tr>
				<td colspan="9" class="body" style="text-align: center;">
					<input type="submit" class="body" name="sbmt" value="Submit Changes" />
				</td>
			</tr>
		<? endif; ?>

		<tr>
			<td colspan="9" class="body" style="text-align: center;">
				<hr />
				<?
					$prev = $last - $step;
					if ($last > -1) $next += $last;
					if (count($rows) < 1 or $ttlshown != $step) $next = -1;

					$link = "{$_SERVER['PHP_SELF']}?admin=1&amp;page=usermanager_index&amp;step=${step}&amp;";
					foreach ($statuses as $val) $link .= rawurlencode("status[]") . "=" . rawurlencode($val) . "&amp;";
					foreach ($showtypes as $val) $link .= rawurlencode("showtype[]") . "=" . $val . "&amp;";
				?>
				<? if ($prev > -1): ?>
					<a class="body" href="<?= $link; ?>last=<?= $prev; ?>">&lt;&lt; Previous</a>
				<? else: ?>
					<strike>&lt;&lt; Previous</strike>
				<? endif; ?>

				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:::&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

				<? if ($next > -1): ?>
					<a class="body" href="<?= $link; ?>last=<?= $next; ?>">Next &gt;&gt;</a>
				<? else: ?>
					<strike>Next &gt;&gt;</strike>
				<? endif; ?>
			</td>
		</tr>
	</table>
</form>

<?
			$this->pagefooter();
		}

		function display_exam ($page) {
			global $userData;

			list($examid) = cleanvar('examid', 10, '::digit::', 0, 0);
			if ($examid < 1 or $examid > 4294967295) $examid = 0;

			$res = $this->db->prepare_query('SELECT * FROM examresults WHERE examid=#', $examid);
			$exam = $res->fetchrow();

			$this->pageheader();
			if ($exam === false) {
				echo "The exam results being requested do not exist.";
				return $this->pagefooter();
			}

			if ($exam['examstatus'] != 'passed' and $exam['examstatus'] != 'failed') {
				echo "The exam requested has not yet been completed; therefore, there are no results to display at this time.";
				return $this->pagefooter();
			}

			$picids = array(); $answers = array();
			foreach (split('&', $exam['frozenexam']) as $exampic) {
				list($picid, $answer) = split('=', $exampic);
				$picids[] = $picid;
				$answers[] = $answer;
			}

			$outputcnt = 0;
?>

<table style="margin-bottom: 20px;">
	<tr>
		<td class="body"><strong>Exam Score:</strong></td>
		<td class="body"><?= sprintf('%.2f', $exam['examscore']); ?>%</td>
	</tr>
	<tr>
		<td class="body"><strong>Total Correct:</strong></td>
		<td class="body"><?= $exam['approvegood'] + $exam['denybad']; ?>: <?= $exam['approvegood']; ?> accepts, <?= $exam['denybad']; ?> denys</td>
	</tr>
	<tr>
		<td class="body"><strong>Total Incorrect:</strong></td>
		<td class="body"><?= $exam['approvebad'] + $exam['denygood']; ?>: <?= $exam['approvebad']; ?> lenient, <?= $exam['denygood']; ?> strict</td>
	</tr>
</table>

<table cellpadding="3" cellspacing="3" width="100%">
	<?
		foreach ($picids as $picid):
			$res = $this->db->prepare_query('SELECT * FROM exampics WHERE picid=#', $picid);
			$pic = $res->fetchrow();

			++$outputcnt;
			$correctanswer = $pic['modanswer'] == 'accept' ? 'accept' : 'deny';
			$useranswer = $answers[$outputcnt - 1] == 'a' ? 'accept' : 'deny';
			$correct = false;
			if ( ($correctanswer == 'accept' and $useranswer == 'accept') or ($correctanswer == 'deny' and $useranswer == 'deny') ) $correct = true;
	?>
		<tr>
			<td class="body" valign="center" align="right" style="background-color: <?= $pic['picgender'] == 'male' ? '#AAAAFF' : '#FFAAAA'; ?>; color: #000; border: solid <?= $correct ? '#0f0' : '#f00' ?> 5px;">
				<a href="<?= $_SERVER['PHP_SELF']; ?>?admin=1&amp;page=picmanager_editcurrent&amp;gotoid=<?= $pic['picid']; ?>"><img src="<?= $this->examconfig['webpicdir']; ?><?= $pic['picfilename']; ?>" border="0" /></a>
			</td>
			<td class="body" valign="top" style="background-color: <?= $pic['picgender'] == 'male' ? '#AAAAFF' : '#FFAAAA'; ?>; color: #000; border: solid <?= $correct ? '#0f0' : '#f00' ?> 5px;">
				<strong>Age: <?= $pic['picage']; ?></strong><br />
				<strong>Sex: <?= ucfirst($pic['picgender']); ?></strong><br /><br />
				<?= $pic['piccomment'] == '' ? '' : wordwrap(htmlentities($pic['piccomment'], ENT_NOQUOTES), 40, "<br />", true) . "<br /><br />"; ?>

				<strong>Correct Answer:</strong> <?= ucfirst($correctanswer); ?><br />
				<strong>User's Answer:</strong> <?= ucfirst($useranswer); ?><br /><br />

				<? if ($correct === true): ?>
					<div style="background-color: #000; color: #0f0; font-weight: bold; padding: 3px;">Correct answer given.</div>
				<? else: ?>
					<div style="background-color: #000; color: #f00; font-weight: bold; padding: 3px;">
						<? if ($correctanswer == 'accept'): ?>
							Pic is acceptable and should<br />not have been denied.
						<? else: ?>
							Deny Category:<br />- <?= ucfirst($this->categories[$pic['modcategory'] - 1]['catlabel']); ?><br /><br />
							Details:<br />
							- <?= wordwrap(htmlentities($pic['modreason'], ENT_NOQUOTES), 40, "<br />", true); ?>
							<? if ($pic['modanswer'] == 'instant fail'): ?>
								<br /><br /><strong>* * * * * * * * * *<br />
								This pic is absolutely unacceptable.<br />
								Instant fail given on this exam for<br />
								approving this pic.<br />
								* * * * * * * * * *</strong>
							<? endif; ?>
						<? endif; ?>

						<? if ($pic['isretired'] == 'y'): ?>
							<br /><br /><strong>* * * * * * * * * *<br />
							PIC IS RETIRED ! ! !<br />
							* * * * * * * * * *</strong>
						<? endif; ?>
					</div>
				<? endif; ?>
			</td>
		</tr>
			<tr><td colspan="2">&nbsp;<br />&nbsp;</td></tr>
		<? endforeach; ?>
	</table>

<?
			$this->pagefooter();
		}

	}



	class picmodexamuser extends picmodexamcommon {
		function __construct () {
			parent::__construct();

			$this->registerSubHandler(__FILE__, new varsubhandler(
				$this, 'exam_timeout', REQUIRE_LOGGEDIN,
				varargs('page', 'exam_timeout', 'request', true)
			));

			$this->registerSubHandler(__FILE__, new varsubhandler(
				$this, 'exam_prepare', REQUIRE_LOGGEDIN,
				varargs('page', 'exam_prepare', 'request', false, 'exam_prepare')
			));
		}

		function pageheader () {
			incHeader();
?>

<table style="width: 600px;" align="center" border="0">
	<tr>
		<td class="header" style="padding: 5px; font-weight: bold;">Pic Mod Exam</td>
	</tr>
	<tr>
		<td class="body" style="passing: 5px;">
<?
		}

		function pagefooter() {
			echo "</td></tr></table>";
			incFooter();
		}

		function exam_prepare ($page) {
			global $userData, $messaging;

			list($action) = cleanvar('action', 7, '::char::', null, null);
			list($beginexam) = cleanvar('beginexam', 3, '::char::', 'no', 'no');
			list($votes) = cleanvarsarray(
				array('vote', 0, 6, '::char::', null, null)
			);
			foreach ($votes as $id => $val) $votes[$id] = $val[0];

			$userexam = false;

			$res = $this->db->prepare_query('SELECT * FROM examresults WHERE userid=#', $userData['userid']);
			$rows = $res->fetchrowset();

			$which = array(
				'waiting' => array(), 'invite sent' => array(), 'invite declined' => array(), 'invite revoked' => array(),
				'exam started' => array(), 'passed' => array(), 'failed' => array()
			);

			foreach ($rows as $row) $which[$row['examstatus']][] = $row;

			if (count($which['invite sent']) > 0) {
				$userexam = $which['invite sent'][0];
			}
			elseif (count($which['waiting']) > 0) {
				$userexam = $which['waiting'][0];
			}
			elseif (count($which['exam started']) > 0) {
				$userexam = $which['exam started'][0];
			}
			elseif (count($which['passed']) > 0) {
				$userexam = $which['passed'][0];
			}
			else {
				$latest = false;
				foreach ($which as $key => $vals) {
					if (count($vals) > 0) {
						foreach ($vals as $val) {
							if ($latest === false or $val['examid'] > $latest['examid']) $latest = $row;
						}
					}
				}
				$userexam = $latest;
			}

			if ($userexam === false) return $this->exam_error('no invite');

			if (isset($action) and $action === 'decline' and $userexam['examstatus'] === 'invite sent') {
				$this->db->prepare_query('UPDATE examresults SET examstatus=? WHERE examid=#', 'invite declined', $userexam['examid']);
				return $this->exam_error('successfull decline');
			}

			switch ($userexam['examstatus']) {
				case 'waiting':
					return $this->exam_error('waiting list');
				case 'invite declined':
					return $this->exam_error('invite declined');
				case 'invite revoked':
					return $this->exam_error('invite revoked');
				case 'passed':
					return $this->exam_error('passed');
				case 'failed':
					return $this->exam_error('failed');
				case 'invite sent':
					if ($beginexam === 'yes') {
						$catcnts = array();
						foreach ($this->categories as $cat) $catcnts[$cat['catid']] = $cat['catcnt'];

						$res = $this->db->prepare_query(
							'SELECT picid, modcategory FROM exampics WHERE modanswer IN (?, ?, ?) AND forceinclude=? AND isretired=?',
							'accept', 'deny', 'instant fail', 'y', 'n'
						);

						$exampicids = array();
						while ($row = $res->fetchrow()) {
							$catcnts[$row['modcategory']]--;
							$exampicids[] = $row['picid'];
						}

						foreach ($catcnts as $catid => $catcnt) {
							if ($catcnt == 0) continue;

							$res = $this->db->prepare_query(
								'SELECT DISTINCT picid FROM exampics WHERE modcategory=# AND modanswer IN (?, ?, ?) AND isretired=? AND forceinclude=? ORDER BY RAND() LIMIT #',
								$catid, 'accept', 'deny', 'instant fail', 'n', 'n', $catcnt
							);
							while ($row = $res->fetchrow()) $exampicids[] = $row['picid'];
	 					}

						shuffle($exampicids);

						$frozen = '';
						foreach ($exampicids as $picid) $frozen .= '&' . $picid . '=u';
						$frozen = ltrim($frozen, '&');

						$examtimestart = time();
						$this->db->prepare_query(
							'UPDATE examresults SET examstatus=?, examtimestart=#, frozenexam=? WHERE examid=#',
							'exam started', $examtimestart, $frozen, $userexam['examid']
						);

						$userexam['examstatus'] = 'exam started';
						$userexam['examtimestart'] = $examtimestart;
						$userexam['frozenexam'] = $frozen;

						return $this->exam_screen($userexam, $votes);
					}
					else {
						return $this->exam_error('invite sent');
					}
				case 'exam started':
					if (time() - $userexam['examtimestart'] > $this->examconfig['exammaxtime']) {
						return $this->exam_error('timeout');
					}
					else {
						$this->examconfig['exammaxtime'] -= (time() - $userexam['examtimestart']);

						$errids = $answers = array();
						$score = array(
							'score' => 100, 'acceptgood' => 0, 'denybad' => 0, 'acceptbad' => 0, 'denygood' => 0
						);

						$outputcnt = 0;
						foreach (split('&', $userexam['frozenexam']) as $exampic) {
							list($picid, $answer) = split('=', $exampic);
							++$outputcnt;
							$answers[] = array($picid, isset($votes[$picid]) && $votes[$picid] === 'accept' ? 'accept' : 'deny');

							if (!isset($votes[$picid]) or ($votes[$picid] !== 'accept' and $votes[$picid] !== 'deny'))
								$errids[] = $outputcnt;
						}

						if (count($errids) > 0) return $this->exam_screen(
							$userexam, $votes, array('You need to provide answers for the following pics:', join(', ', $errids))
						);

						$ordered = array(); foreach (array_values($answers) as $val) $ordered[] = $val[0];
						$res = $this->db->prepare_query('SELECT picid, modanswer FROM exampics WHERE picid IN (#)', $ordered);

						while ($row = $res->fetchrow()) {
							$picid = $row['picid'];
							$modanswer = $row['modanswer'];

							if ($votes[$picid] === 'accept' and $modanswer === 'instant fail') {
								$score['score'] = -1;
								$score['acceptbad']++;
							}
							elseif ($votes[$picid] === 'deny' and $modanswer === 'instant fail') {
								$score['denybad']++;
							}
							elseif ($votes[$picid] === 'accept' and $modanswer === 'accept') {
								$score['acceptgood']++;
							}
							elseif ($votes[$picid] === 'deny' and $modanswer === 'deny') {
								$score['denybad']++;
							}
							elseif ($votes[$picid] === 'accept' and $modanswer === 'deny') {
								$score['acceptbad']++;
								$score['score'] -= $this->examconfig['deductacceptbad'];
							}
							elseif ($votes[$picid] === 'deny' and $modanswer === 'accept') {
								$score['denygood']++;
								$score['score'] -= $this->examconfig['deductdenygood'];
							}
						}

						$frozen = '';
						foreach ($answers as $val) {
							list($picid, $answer) = $val;
							$col = $answer === 'accept' ? 'acceptcnt' : 'denycnt';
							$this->db->prepare_query("UPDATE exampics SET ${col}=${col}+1 WHERE picid=#", $picid);
							$frozen .= '&' . $picid . '=' . substr($answer, 0, 1);
						}
						$frozen = substr($frozen, 1);

						if ($score['score'] < 0) $score['score'] = 0;
						$score['score'] = sprintf('%.2f', $score['score']);
						$passorfail = $score['score'] >= $this->examconfig['exampassmark'] ? 'passed' : 'failed';
						$this->db->prepare_query(
							'UPDATE examresults SET examtimeend=#, examstatus=?, examscore=?, approvegood=#, denybad=#, approvebad=#, denygood=#, frozenexam=? WHERE examid=#',
							time(), $passorfail, $score['score'], $score['acceptgood'], $score['denybad'], $score['acceptbad'], $score['denygood'], $frozen, $userexam['examid']
						);

						$user = getUserName($userexam['userid']);
						$messaging->deliverMsg(
							$this->examconfig['adminnotify'],
							"picmodexam - $user ($passorfail w/{$score['score']}%)",
							"$user has completed the exam. S/he $passorfail with a score of {$score['score']}%.\n\n[url={$_SERVER['PHP_SELF']}?admin=1&amp;page=display_exam&amp;examid={$userexam['examid']}]view the results[/url]\n",
							0, "Nexopia", 0, false, false
						);

						$this->pageheader();
						echo getStaticValue('picmodexam_examdone');
						$this->pagefooter();
					}
			}
		}

		function exam_error ($errmsg = 'unknown error') {
?>

<? switch ($errmsg): ?><? case 'no invite': ?>
		<? $this->pageheader(); ?>

		You have not yet taken the pic mod exam, nor have you been invited to take the exam. If you want
		to take the exam, you will need to be placed on the waiting list.
		<!-- Contact details for waiting list placement -->

		<? $this->pagefooter(); return; ?>
	<? case 'successfull decline': ?>
		<? $this->pageheader(); ?>

		You have successfully declined the invitation to take the pic mod exam.
		If you want to take the exam, you will need to be placed back on the waiting list.
		<!-- Contact details for waiting list placement -->

		<? $this->pagefooter(); break; ?>
	<? case 'waiting list': ?>
		<? $this->pageheader(); ?>

		You have been placed on the waiting list for the pic mod exam. An invitation has not yet
		been issued, so you can't take the exam just yet. Be patient; you will be sent an invitation
		to take the exam when we need the additional help.

		<? $this->pagefooter(); break; ?>
	<? case 'invite declined': ?>
		<? $this->pageheader(); ?>

		An invitation to take the pic mod exam was sent to you, but you chose to decline the invitation.
		If you want to take the exam, you will need to be placed back on the waiting list.
		<!-- Contact details for waiting list placement -->

		<? $this->pagefooter(); break; ?>

	<? case 'invite revoked': ?>
		<? $this->pageheader(); ?>

		An invitation to take the pic mod exam was sent to you, but was later revoked. A message
		was sent to you explaining why your invitation was revoked. If you want to take the exam, you
		will need to be placed back on the waiting list.
		<!-- Contact details for waiting list placement -->

		<? $this->pagefooter(); break; ?>
	<? case 'passed': ?>
	<? case 'failed': ?>
		<? $this->pageheader(); ?>

		You have already completed the pic mod exam. If you have not already received a message
		back from the admin team, please be patient: we will contact you!

		<? $this->pagefooter(); break; ?>
	<? case 'timeout': ?>
		<? $this->pageheader(); ?>

		Sorry, you have exceeded the maximum time limit imposed on the pic mod exam. This means
		you took too long modding the pics! Unfortunately, this means that you have forefeited the
		exam. If you want to take the exam again, you will need to be placed back on the waiting list.
		<!-- Contact details for waiting list placement -->

		<? $this->pagefooter(); break; ?>
	<? case 'invite sent': ?>
		<?
			$res = $this->db->prepare_query('SELECT SUM(catcnt) AS ttlpics FROM exampiccategories');
			$ttlpics = $res->fetchfield();

			$examstart = getStaticValue('picmodexam_examstart');
			$examstart = str_ireplace('%ttlpics%', $ttlpics, $examstart);
			$this->pageheader();
		?>

		<?= $examstart; ?>
		<form action="<?= $_SERVER['PHP_SELF']; ?>" method="post">
			<input type="hidden" name="page" value="exam_prepare" />
			<input type="hidden" name="beginexam" value="yes" />
			<input type="submit" class="body" name="sbmt" value="Begin Exam" />
		</form>

		<? $this->pagefooter(); break; ?>
<? endswitch ?>

<?
		}

		function exam_screen ($userexam, $votes = array(), $errmsgs = array()) {
			$exampics = array();
			foreach(split('&', $userexam['frozenexam']) as $exampic) {
				list($picid, $answer) = split('=', $exampic);
				$res = $this->db->prepare_query('SELECT * FROM exampics WHERE picid=#', $picid);
				$exampics[] = $res->fetchrow();
			}

			$outputcnt = 0;

			$this->pageheader();
?>

<? if (count($errmsgs) > 0): ?>
	<table style="width: 100%; margin-bottom: 10px;" cellpadding="0" cellspacing="0">
		<tr>
			<td class="msg" style="padding: 3px; font-weight: bold;">Your exam is incomplete.</td>
		</tr>
		<tr>
			<td class="msg" style="padding: 3px;"><?= join("<br />", $errmsgs); ?></td>
		</tr>
	</table>
<? endif; ?>

<script language="javascript">
	function jump(i) { location.href='#pic' + i; }

	var maxtime = <?= $this->examconfig['exammaxtime']; ?>;
	var warningtime = 10;

	function initialCheck () {
		if (maxtime <= 60 * 2.5) {
			alert('You need to submit your exam immediately or your exam will be forfeited!!!');
			warningtime = -1;
		}
		else if (maxtime <= 60 * 5) {
			alert('You have less than 5 minutes remaining to complete the exam!');
			warningtime = 3;
		}
		else if (maxtime <= 60 * 10) {
			alert('You have less than 10 minutes remaining to complete the exam!');
			warningtime = 5;
		}

		setInterval('timeisticking()', 1000);
	}

	function timeisticking() {
		maxtime -= 1;
		if (maxtime <= -10) location.href = "<?= $_SERVER['PHP_SELF']; ?>?page=exam_timeout";

		if (warningtime == 10 && maxtime <= 60 * 10) {
			alert('You have less than 10 minutes remaining to complete the exam!');
			warningtime = 5;
		}
		if (warningtime == 5 && maxtime <= 60 * 5) {
			alert('You have less than 5 minutes remaining to complete the exam!');
			warningtime = 3;
		}
		if (warningtime == 3 && maxtime <= 60 * 2.5) {
			alert('You need to submit your exam immediately or your exam will be forfeited!!!');
			warningtime = -1;
		}
	}

	setTimeout('initialCheck()', 2000);
</script>
<form action="<?= $_SERVER['PHP_SELF']; ?>" method="post">
	<input type="hidden" name="page" value="exam_prepare" />

	<table cellpadding="3" cellspacing="3" width="100%">

		<? foreach ($exampics as $pic): ?>
			<?
				$checked = array(
					'accept' => (isset($votes[$pic['picid']]) and $votes[$pic['picid']] == 'accept') ? ' checked="checked"' : '',
					'deny'   => (isset($votes[$pic['picid']]) and $votes[$pic['picid']] == 'deny') ? ' checked="checked"' : ''
				);
			?>
			<tr>
				<td colspan="2" class="header" style="font-weight: bold; padding: 3px;">
					<a name="pic<?= ++$outputcnt ?>"></a>
					#<?= $outputcnt; ?>
				</td>
			</tr>
			<tr>
				<td class="body" valign="center" align="right" style="background-color: <?= $pic['picgender'] == 'male' ? '#AAAAFF' : '#FFAAAA'; ?>; color: #000;">
					<img src="<?= $this->examconfig['webpicdir']; ?><?= $pic['picfilename']; ?>" />
				</td>
				<td class="body" valign="top" style="background-color: <?= $pic['picgender'] == 'male' ? '#AAAAFF' : '#FFAAAA'; ?>; color: #000;">
					Age: <strong><?= $pic['picage']; ?></strong><br />
					Sex: <strong><?= ucfirst($pic['picgender']); ?></strong><br /><br />

					<input type="radio" name="vote[<?= $pic['picid']; ?>]" id="accept<?= $outputcnt; ?>" value="accept" onClick="setTimeout('jump(<?= $outputcnt + 1; ?>)', 100);"<?= $checked['accept']; ?>>
					<label for="accept<?= $outputcnt; ?>" class="side"><strong>Accept</strong></label><br />
					<input type="radio" name="vote[<?= $pic['picid']; ?>]" id="deny<?= $outputcnt; ?>" value="deny" onClick="setTimeout('jump(<?= $outputcnt + 1; ?>)', 100);"<?= $checked['deny']; ?>>
					<label for="deny<?= $outputcnt; ?>" class="side"><strong>Deny</strong></label><br /><br />
					<?= wordwrap(htmlentities($pic['piccomment'], ENT_NOQUOTES), 40, "<br />", true); ?><br /><br />

					<?= sprintf( '%.2f', (rand(0, 32767) / 32767 * (9 - 0) + 0) ); ?> hours
				</td>
			</tr>
			<tr><td colspan="2">&nbsp;<br />&nbsp;</td></tr>
		<? endforeach; ?>

		<tr>
			<td colspan="2" class="body" align="center">
				When you have completed the exam, simply click the button below to submit your results.
				Your exam will be saved and submitted to the admin team for review.
				<br /><br />
				<input type="submit" class="body" name="sbmt" value="Submit Exam" />
			</td>
		</tr>

	</table>

</form>

<?
			$this->pagefooter();
		}

		function exam_timeout($page) {
			return $this->exam_error('timeout');
		}
	}

	$page = isset($_REQUEST['admin']) ? new picmodexamadmin() : new picmodexamuser();
	return $page->runPage();







	function cleanvar ($field, $maxlen, $whitelist, $emptydefault = null, $nulldefault = null) {
		list($result) = cleanvarsarray(
			array($field, 1, $maxlen, $whitelist, $emptydefault, $nulldefault)
		);
		return array_values(array_shift($result));
	}

	function cleanvarsarray () {
		// only use Single Quotes around the characters sequences. the ONLY chars that
		// need escaping are listed in the below table. make sure the escaping is followed
		// strictly, nasty bugs appear if this table is not followed exactly.

		/*
			special char  |  escape sequence
			--------------------------------
			-				\\-
			[				\\[
			]				\\]
			'				\'   (only single backslash)
			/				\\/
			\				\\\\ (four backslashes to permit single backslash)
			\r				no escaping necessary
			\n				no escaping necessary
		*/

		$presets = array(
			'any' 				=> '::any::',
			'english_nonewline'	=> 'a-zA-Z0-9 !@#$%^&*()`\\-=~_+\\[\\]{}:;"\'<>,.\\/?\\\\|',
			'english_newline'	=> 'a-zA-Z0-9 !@#$%^&*()`\\-=~_+\\[\\]{}:;"\'<>,.\\/?\\\\|\r\n',
			'char'				=> 'a-zA-Z',
			'digit'				=> '0-9',
			'alphanumeric'		=> 'a-zA-Z0-9',
			'french'			=> '',
			'spanish'			=> '',
			'german'			=> ''
		);

		$french = array(
			159, 192, 194, 196, 199, 200, 201, 202, 203, 206, 207, 212, 140, 217, 219, 220, 128,
			224, 226, 228, 231, 232, 233, 234, 235, 238, 239, 244, 156, 249, 251, 252, 255
		);
		foreach ($french as $ord)
			$presets['french'] .= chr($ord);

		$spanish = array(128, 193, 201, 205, 211, 218, 209, 220, 225, 233, 237, 243, 250, 241, 252);
		foreach ($spanish as $ord)
			$presets['spanish'] .= chr($ord);

		$german = array(196, 228, 214, 246, 220, 252, 223, 128);
		foreach ($german as $ord)
			$presets['german'] .= chr($ord);

		$res = array();

		foreach (func_get_args() as $mainkey => $args) {
			@list($fields, $resultcnt, $maxlen, $whitelist, $emptydefault, $nulldefault) = $args;
			if (isset($fields[0]) and is_array($fields[0])) $fields = $fields[0];

			if (isset($whitelist) and strpos($whitelist, '::') !== false) {
				$matches = array();
				preg_match_all('/::([^:]+)::/', $whitelist, $matches, PREG_SET_ORDER);
				foreach ($matches as $match) {
					if (!isset($presets[$match[1]]))
						trigger_error("preset character class '{$match[0]}' is not defined.", E_USER_ERROR);
					$whitelist = str_replace($match[0], $presets[$match[1]], $whitelist);
				}
			}

			if (is_string($fields)) $fields = isset($_REQUEST[$fields]) ? $_REQUEST[$fields] : array();
			if (!is_array($fields)) $fields = array($fields);

			if ($resultcnt > 0) {
				for ($i = count($fields) + 1; $i <= $resultcnt; $i++) $fields[] = null;
				$ttlcnt = 0; foreach (array_keys($fields) as $key) if (++$ttlcnt > $resultcnt) unset($fields[$key]);
			}

			$modified = $fields;
			foreach ($fields as $key => &$request) {
				$mod = &$modified[$key];
				if (is_null($request)) {
					$request = $mod = $nulldefault;
				}
				elseif ($request === '') {
					$request = $mod = $emptydefault;
				}
				else {
					if (isset($whitelist) and $whitelist != '::any::') $mod = preg_replace("/[^$whitelist]+/", '', $mod);
					if (strlen($mod) > $maxlen) $mod = substr($mod, 0, $maxlen);
					if ($mod === '') $mod = $emptydefault;
				}
				unset($mod);
				unset($request);
			}

			foreach ($fields as $key => $val) $res[$mainkey][$key] = array($modified[$key], $val);
			if (count($fields) === 0) $res[$mainkey] = array();
		}

		return $res;
	}
?>
