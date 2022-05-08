<?

	$login=1;

	require_once("include/general.lib.php");

	if(isset($action)){
		$subject = "V8Less Nights tickets draw";
	
		if(!isset($text))
			$text = "";

		$text .= "\n------------------------------\n\nUsername: $userData[username]\nEmail: $userData[email]";
		mail("Information <info@enternexus.com>", $config['contactsubjectPrefix'] . " " . $subject, $text, "From: $userData[username] <$userData[email]>") or die("Error sending email");
	
		incHeader();
		
		echo "Thanks for entering";
		
		incFooter();
		exit;
	}


	incHeader();
	
	
	echo "<table><form action=$_SERVER[PHP_SELF] method=post>";
	echo "<tr><td class=body>Thanks to our friends at V8less, we've received a pair of event tickets for the V8less Nights & Urban Battle Showcase show. The draw you are currently entering for will be for this pair of tickets. <br><br>
 
Here is some brief information of the v8less event: <br>
V8less Nights is geared to be the ultimate indoor event of 2003. Based out of the world-class facilities of the Northlands Sportex, V8less Nights will unleash 60,000 square feet of pure excitement on July 5th. Nothing can connect people like their automotive passions and V8less Nights will showcase the fastest, shiniest, loudest automotive eye-candy in Western Canada. Enhancing the indoor car show, V8less Nights adds another 40,000 square feet of outdoor space that will feature a \"Horsepower\" dyno competition and a \"Street-Style\" car audio contest.  <br><br>
 
The scene doesn't stop there. Backed by the Urban Battle Showcase, V8less Nights is a media giant to say the least. Only an event like V8less Nights can bring this magnitude of mainstream visibility to the hip urban lifestyle. July 5th will be hailed as the largest Urban Performer Showcase in Alberta. Hip hop performances, break dance, MC freestylers and turntablist engage in an fierce battle for glory from the crowds and respect from their peers. <br><br><br>
 
 
Good luck!</td></tr>";
	echo "<tr><td class=body align=center><input class=body type=submit name=action value=\"Enter to Win Free Tickets\"></td></tr>";
	echo "<tr><td class=body align=center>" . banner(5) . "</td></tr>";
	echo "</form></table>";
	
	
	incFooter();
	
