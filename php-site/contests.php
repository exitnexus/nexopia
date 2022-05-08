<?
	$login = 0;
	require_once('include/general.lib.php');
	require_once('include/mail/emailMessage.php');
	
	class contestspage extends pagehandler {
		function __construct () {
			$this->registerSubHandler('/contest', new urisubhandler(
				$this, 'contests_main', REQUIRE_ANY,
				uriargs('contest', 'string'),
				uriargs('subpage', 'string'),
				uriargs('submit', 'string')
			));

			$this->registerSubHandler('/contest', new urisubhandler(
				$this, 'contests_main', REQUIRE_ANY,
				uriargs('contest', 'string'),
				uriargs('subpage', 'string')
			));
	
			$this->registerSubHandler('/contest', new urisubhandler(
				$this, 'contests_main', REQUIRE_ANY,
				uriargs('contest', 'string')
			));

			$this->registerSubHandler('/contest', new urisubhandler(
				$this, 'contests_main', REQUIRE_ANY
			));
		}

		function contests_main ($contest = '__none', $subpage = '', $submit = '') {
			global $wiki, $userData, $config;

			$main = $wiki->getPage("/Contests/${contest}");
			if ($main['id'] == 0) {
				incHeader();
				echo "The contest page you are looking for does not exist.";
				incFooter();
			}

			else {
				list($open_to, $sort_by) = $this->comments($main['comment']);

				$page = $subpage == '' ? $main : $wiki->getPage("/Contests/${contest}/${subpage}");
				if ($page['id'] == 0) {
					incHeader();
					echo "The contest page you are looking for does not exist.";
					incFooter();
				}

				elseif ($open_to == 'loggedin' && ! $userData['loggedIn'] && strtolower($subpage) != 'login')
					return $this->contests_main($contest, 'login');

				else {
					$text = ($userData['loggedIn'] ? "userid {$userData['userid']}, username {$userData['username']}" : "User not logged in") . "\n----------\n\n";
					foreach ($_POST as $key => $val)
						$text .= "${key}:\n---\n${val}\n----------\n\n";

					if (substr(strtolower($submit), 0, 6) == 'submit') {
						$msg = new emailMessage();
						$msg->setSMTPParams($config['smtp_host'], 25);
						$msg->setFrom('Contests <contests@nexopia.com>');
						$msg->setSubject(
							"${contest} [${sort_by}: " . (isset($_POST[$sort_by]) ? urlencode($_POST[$sort_by]) : 'UNKNOWN') . '] ' .
							($userData['loggedIn'] ? "{$userData['userid']}/{$userData['username']}" : 'Anonymous')
						);
						$msg->setText($text);

						$msg->send(array('contest@nexopia.com'), 'smtp');
					}

					incHeader(602);
					echo $page['output'];
					incFooter();
				}
			}

			return true;
		}

		function comments ($str) {
			$open_to = 'loggedin';
			$sort_by = 'userid';

			$lines = strlen($str) > 0 ? preg_split('/[\r\n]+/', $str) : array();
			foreach ($lines as $line) {
				if (preg_match('/open/i', $line) && preg_match('/(anybody|anyone)/i', $line))
					$open_to = 'anyone';

				elseif (preg_match('/sort\s*(?:by)?\s*(.+)\s*/i', $line, $match))
					$sort_by = $match[1];
			}

			return array($open_to, $sort_by);
		}
	}

	$handler = new contestspage;
	return $handler->runPage();
?>
