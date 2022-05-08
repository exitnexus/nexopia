<?

	$login=0;

	require_once("include/general.lib.php");

	$default = "";
	$base = 'Contest/Skin';

	class skincontestpage extends pagehandler {
		function __construct() {

			$this->registerSubHandler('/skincontest',
				new urisubhandler($this, 'varhandler', REQUIRE_ANY,
					uriargs('addr', 'string')
				)
			);

			$this->registerSubHandler('/skincontest',
				new urisubhandler($this, 'varhandler', REQUIRE_ANY
				)
			);
			
			$this->registerSubHandler('/skincontest.php',
				new urisubhandler($this, 'varhandler', REQUIRE_ANY,
					uriargs('addr', 'string')
				)
			);

			$this->registerSubHandler('/skincontest.php',
				new urisubhandler($this, 'varhandler', REQUIRE_ANY
				)
			);
		}

		function varhandler($addr = ''){
			global $wiki, $default, $base, $db, $userData, $mods;

			if ($addr == 'voteadmin') {
				if ($mods->isAdmin($userData['userid'])) {
					incHeader();

					$sth = $db->prepare_query('SELECT COUNT(userid) AS numvoters FROM designvoters');
					$numvoters = $sth->fetchfield();
					echo "<strong>${numvoters} users have submitted their votes.</strong><br><br><br>";

					$sth = $db->prepare_query('SELECT skinid, votes FROM designvoting ORDER BY votes DESC');
					while ( ($row = $sth->fetchrow()) !== false )
						echo "<strong>Skin ID {$row['skinid']} ({$row['votes']} votes)</strong><br><img src=\"http://users.nexopia.com/uploads/1106/1106759/wiki/Contest/skins3/skin{$row['skinid']}-full.jpg\"><br><br>";

					incFooter();
					exit;
				}

				else
					die("Permission denied.");
			}

			elseif ($addr == 'vote') {
				if (! $userData['loggedIn'])
					$addr = 'vote_plslogin';

				else {
					$sth = $db->prepare_query('SELECT COUNT(userid) AS voted FROM designvoters WHERE userid = #', $userData['userid']);
					if ( ($voted = $sth->fetchfield()) > 0 )
						$addr = 'vote_alreadyvoted';

					elseif ($userData['jointime'] > 1177037707)
						die("Sorry, your account was created too recently to be eligible to vote on the skins. To prevent cheating, new accounts are not able to participate in this phase of the contest.");

					else {
						$votes = getPOSTval('votes', 'string', '');

						if (preg_match('/^(?:\d{1,2},){4}\d{1,2}$/', $votes)) {
							$db->prepare_query('INSERT INTO designvoters SET userid = #', $userData['userid']);
							$db->prepare_query('UPDATE designvoting SET votes = votes + 1 WHERE skinid IN (#)', explode(',', $votes));
							$addr = 'vote_thanks';
						}
					}
				}
			}


			$addr = trim($addr, '/');

			if(strpos($addr, '/') || !$addr) //invalid
				$addr = $default;

			$entry = $wiki->getPage("/$base/$addr");

			incHeader(602);
			
			echo $entry['output'];

			incFooter();
			exit;
		}
	}

	$skincontestpage = new skincontestpage();
	return $skincontestpage->runPage();
