<?php
	$login=1;

	require_once("include/general.lib.php");

	$databases["officegames"]= array(	"host" => "$dbserv",
										"login" => "root",
										"passwd" => "Hawaii",
										"db" => "officegames" );
	$ogdb = new sql_db($databases['officegames']);

	class officegames extends pagehandler
	{
		function __construct()
		{
			$this->registerSubHandler(__FILE__,
				new varsubhandler($this, 'declareWinner', REQUIRE_LOGGEDIN,
					varargs('gameid', 'integer', 'post'),
					varargs('username', array('string'), 'post')
				)
			);
			$this->registerSubHandler(__FILE__,
				new varsubhandler($this, 'displayGame', REQUIRE_LOGGEDIN,
					varargs('gameid', 'integer', 'request')
				)
			);
			$this->registerSubHandler(__FILE__,
				new varsubhandler($this, 'displayGameByName', REQUIRE_LOGGEDIN,
					varargs('gamename', 'string', 'request')
				)
			);
			$this->registerSubHandler(__FILE__,
				new varsubhandler($this, 'displayGames', REQUIRE_LOGGEDIN)
			);

			$this->registerSubHandler('/games',
				new urisubhandler($this, 'displayGameByName', REQUIRE_LOGGEDIN,
					uriargs('gamename', 'string')
				)
			);
			$this->registerSubHandler('/games',
				new urisubhandler($this, 'displayGames', REQUIRE_LOGGEDIN)
			);
		}

		function declareWinner($gameid, $username)
		{
			global $userData, $ogdb, $msgs;

			if (count($username) < 2)
			{
				$msgs->addMsg("Did not pass enough usernames in.");
			} else {
				$userids = array(
					0 => getUserID($username[0]),
					1 => getUserID($username[1])
				);
				if (!$userids[0] || !$userids[1])
				{
					$msgs->addMsg("Could not find one of the usernames entered.");
				} else {
					$ogdb->prepare_query("INSERT IGNORE INTO wins (gameid, userid1, userid2, wins) VALUES (#, #, #, 1)", $_POST['gameid'], $userids[0], $userids[1]);
					if (!$ogdb->affectedrows())
					{
						$ogdb->prepare_query("UPDATE wins SET wins = wins + 1 WHERE gameid = # AND userid1 = # AND userid2 = #", $_POST['gameid'], $userids[0], $userids[1]);
					}
				}
			}
			return $this->reRunPage(array('gameid' => $gameid));
		}

		function outputGame($gameid, $gamename)
		{
			global $ogdb;

			$playerids = array();
			$wins = array();
			$totalwins = array();
			$losses = array();
			$totallosses = array();

			// find out who's played this game and how many times they've won against someone.
			$playerresult = $ogdb->prepare_query("SELECT * FROM wins WHERE gameid = #", $gameid);
			while ($player = $ogdb->fetchrow($playerresult))
			{
				$playerids[$player['userid1']] = 1;
				$playerids[$player['userid2']] = 1;

				if (!isset($wins[$player['userid1']]))
					$wins[$player['userid1']] = array();
				if (!isset($losses[$player['userid2']]))
					$losses[$player['userid2']] = array();

				if (!isset($totalwins[$player['userid1']]))
					$totalwins[$player['userid1']] = 0;
				if (!isset($totallosses[$player['userid2']]))
					$totallosses[$player['userid2']] = 0;

				$wins[$player['userid1']][$player['userid2']] = $player['wins'];
				$totalwins[$player['userid1']] += $player['wins'];
				$losses[$player['userid2']][$player['userid1']] = $player['wins'];
				$totallosses[$player['userid2']] += $player['wins'];
			}

			$playerids = array_keys($playerids);
			$playernames = getUserName($playerids);
			$playershortnames = array();
			foreach ($playernames as $id => $name) { $playershortnames[$id] = substr($name, 0, 3); }
			$cols = count($playerids) + 1;

			echo "<table valign=top>";
			echo "<tr><td class=header colspan=$cols align=center><b><a class=header href=/games/$gamename>$gamename</a></b></td></tr>";
			echo "<tr><td class=header align=center>Username</td><td class=header align=center>" . implode('</td><td class=header align=center>', $playershortnames) . "</td></tr>";
			foreach ($playerids as $id)
			{
				echo "<tr><td class=header align=center><b>{$playernames[$id]}</b></td>";
				foreach ($playerids as $id2)
				{
					if ($id == $id2)
					{
						echo "<td style=\"width: 2.5em; height: 2.5em; background-color: black; color: red\" align=center>X</td>";
					} else if (isset($wins[$id][$id2]) || isset($losses[$id][$id2])) {
						$userwins = (isset($wins[$id][$id2])? $wins[$id][$id2] : "0");
						$userlosses = (isset($losses[$id][$id2])? $losses[$id][$id2] : "0");

						echo "<td class=body2 style=\"width: 2.5em; height: 2.5em;\" align=center>";
						echo "<div style=\"color: green\">$userwins</div>";
						echo "<div style=\"color: red\">$userlosses</div>";
						echo "</td>";
					} else {
						echo "<td class=body style=\"width: 2.5em; height: 2.5em;\" align=center>&nbsp;</td>";
					}
				}
				echo "</tr>";
			}
			echo "<tr><td class=header align=center><b>Total:</b></td>";
			foreach ($playerids as $id)
			{
				if (isset($totalwins[$id]) || isset($totallosses[$id]))
				{
					$userwins = (isset($totalwins[$id])? $totalwins[$id] : 0);
					$userlosses = (isset($totallosses[$id])? $totallosses[$id] : 0);

					echo "<td class=body2 style=\"width: 2.5em; height: 2.5em;\" align=center>";
					echo "<div style=\"color: green\">$userwins</div>";
					echo "<div style=\"color: red\">$userlosses</div>";
					echo "</td>";
				} else {
					echo "<td class=body style=\"width: 2.5em; height: 2.5em;\" align=center>&nbsp;</td>";
				}
			}
			echo "<tr><td class=header align=center colspan=$cols><b>New:</b></td></tr></table>";
			echo "<div class=body><form action=\"/officegames.php\" method=post class=body><input type=hidden name=gameid value=$gameid /><input type=text class=body name=username[0] /> beat <input type=text class=body name=username[1] /><input type=submit name=submit value=Submit class=body /></div>";
		}

		function displayGameByName($gamename)
		{
			global $ogdb;

			$gameresult = $ogdb->prepare_query("SELECT gameid FROM games WHERE gamename = ?", $gamename);
			if ($idrow = $ogdb->fetchrow($gameresult))
			{
				$args = $idrow;
				$this->displayGame($args);
			} else {
				$this->displayGames(array());
			}
		}

		function displayGames()
		{
			global $ogdb;

			// get the list of games
			$gameresult = $ogdb->prepare_query("SELECT * FROM games");

			incHeader();
			echo "<center>";

			while ($game = $ogdb->fetchrow($gameresult))
			{
				$gameid = $game['gameid'];
				$gamename = $game['gamename'];

				$this->outputGame($gameid, $gamename);
			}

			echo "</center>";
			incFooter();
		}

		function displayGame($gameid)
		{
			global $ogdb;

			incHeader();
			echo "<center>";

			$gameresult = $ogdb->prepare_query("SELECT * FROM games WHERE gameid = #", $gameid);
			if ($game = $ogdb->fetchrow($gameresult))
			{
				$gameid = $game['gameid'];
				$gamename = $game['gamename'];

				$this->outputGame($gameid, $gamename);
			}

			echo "</center>";
			incFooter();
		}
	}
	$officegames = new officegames();
	return $officegames->runPage();
