<?
	$login = 1;
	require_once('include/general.lib.php');

	class massdelmods extends pagehandler {
		function __construct () {
			$this->registerSubHandler(__FILE__, new varsubhandler(
				$this, 'entrypage', array(REQUIRE_LOGGEDIN_ADMIN, 'editmods')
			));
		}

		function entrypage () {
			global $mods, $forums, $messaging, $cache, $forumdb, $abuselog;
			$errmsgs = array( 'other' => array(), 'cannotdel' => array(), 'deleted' => array(), 'notpicmod' => array(), 'notexist' => array() );

			$users = getREQval('usernames', null);
			$subject = getPOSTval('subject', null);
			$body = getPOSTval('msg', null);

			if (! is_null($users)) {
				if (is_null($subject) || is_null($body)) {
					$errmsgs['other'][] = "A subject line and a message body are required before submitting.";
				}
				else {
					$deletedids = $deletednames = $seen = array();

					foreach (preg_split("/\s+/", $users) as $user) {
						$userid = getUserId($user);
						$username = $userid === false ? null : getUserName($userid);

						if (! is_null($username)) {
							if (in_array($userid, $seen))
								continue;
							$seen[] = $userid;

							if ($mods->isMod($userid, MOD_PICS)) {
								$allowdel = true;
								if (! $mods->isAdmin($userid)) {
									$res = $forumdb->prepare_query('SELECT COUNT(*) AS modcnt FROM forummods, forums WHERE forummods.userid=# AND forummods.forumid=forums.id AND forums.official=?', $userid, 'y');
									$isforummod = $res->fetchfield();

									if (! is_null($isforummod) and $isforummod > 0) {
										$errmsgs['cannotdel'][] = "'${username}' (userid #${userid}) - forum mod";
										$allowdel = false;
									}
								}
								else {
									$errmsgs['cannotdel'][] = "'${username}' (userid #${userid}) - admin";
									$allowdel = false;
								}

								if ($allowdel) {
									$deletedids[] = $userid;
									$errmsgs['deleted'][] = "'${username}' (userid #${userid})";
									$mods->adminlog("delete mod", "Delete 1 mod: $username");
									$abuselog->addAbuse($userid, ABUSE_ACTION_NOTE, ABUSE_REASON_OTHER, 'Removed as pic mod', 'User stripped of pic mod privileges.');
								}
							}
							else {
								$errmsgs['notpicmod'][] = "'${username}' (userid #${userid})";
							}
						}
						else {
							$errmsgs['notexist'][] = "'{$user}'";
						}
					}

					if (count($deletedids) > 0) {
						$mods->deleteMod($deletedids, MOD_PICS);
						$forums->unInvite($deletedids, 203);
						$forums->unInvite($deletedids, 139);
						$messaging->deliverMsg($deletedids, $subject, $body, 0, false, false, false);
					}
				}
				
			}

			$errcnt = 0;
			foreach ($errmsgs as $errmsg)
				$errcnt += count($errmsg);

			incHeader();
?>

<? if ($errcnt > 0): ?>
	<table cellpadding="5" cellspacing="5">
		<tr><td class="msg" style="font-weight: bold;">Results for all operations executed:</td></tr>

		<? if (count($errmsgs['other']) > 0): ?>
			<tr><td class="msg"><?= join("<br />", $errmsgs['other']); ?></td></tr>
		<? endif; ?>
		<? if (count($errmsgs['notexist']) > 0): ?>
			<tr><td class="msg">
				The following users could not be de-modded, because the userids/usernames do not exist:<br />
				<? foreach ($errmsgs['notexist'] as $user): ?>&nbsp;&nbsp;&nbsp;&nbsp;- <?= htmlentities($user); ?><br /><? endforeach; ?>
			</td></tr>
		<? endif; ?>
		<? if (count($errmsgs['notpicmod']) > 0): ?>
			<tr><td class="msg">
				The following users could not be de-modded, because the users are not pic mods:<br />
				<? foreach ($errmsgs['notpicmod'] as $user): ?>&nbsp;&nbsp;&nbsp;&nbsp;- <?= htmlentities($user); ?><br /><? endforeach; ?>
			</td></tr>
		<? endif; ?>
		<? if (count($errmsgs['cannotdel']) > 0): ?>
			<tr><td class="msg">
				The following users could not be de-modded, because they have admin and/or forum privileges:<br />
				<? foreach ($errmsgs['cannotdel'] as $user): ?>&nbsp;&nbsp;&nbsp;&nbsp;- <?= htmlentities($user); ?><br /><? endforeach; ?>
			</td></tr>
		<? endif; ?>
		<? if (count($errmsgs['deleted']) > 0): ?>
			<tr><td class="msg">
				The following users have been successfully de-modded:<br />
				<? foreach ($errmsgs['deleted'] as $user): ?>&nbsp;&nbsp;&nbsp;&nbsp;- <?= htmlentities($user); ?><br /><? endforeach; ?>
			</td></tr>
		<? endif; ?>
	</table>
<? endif; ?>

<form action="<?= $_SERVER['PHP_SELF']; ?>" method="post" name="editbox">
	<table cellspacing="10">
		<tr><td class="header">Remove Pic Mods</td></tr>
		<tr><td class="body2">Users to remove:</td></tr>
		<tr><td class="body"><textarea name="usernames" rows="10" cols="50"><?= htmlentities($users); ?></textarea></td></tr>
		<tr><td class="body2">Message to Send:</td></tr>
		<tr><td class="body">Subject: <input type="text" class="body" name="subject" value="<?= htmlentities($subject); ?>" size="50" /></td></tr>
		<tr><td class="body"><? editbox($body); ?></td></tr>
		<tr><td class="body"><input type="submit" class="body" name="sbmt" value="Remove Mods" /></td></tr>
	</table>
</form>

<?
			incFooter();
		}
	}

	$page = new massdelmods();
	return $page->runPage();
?>
