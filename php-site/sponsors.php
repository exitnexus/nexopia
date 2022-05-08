<?

	$login=0;
	
	require_once("include/general.lib.php");

	$query = "SELECT * FROM bannerclients WHERE enablesponsorship='y'";
	$result = mysql_query($query);
	
	$clientinfo = array();
	while($line = mysql_fetch_assoc($result))
		$clientinfo[] = $line;
	
	$time = time();
	$query = "SELECT * FROM banners WHERE (maxviews<1 || views<maxviews) && startdate < $time && (enddate > $time || enddate=0) && moded='y'";
	$result = mysql_query($query);

	$bannerinfo = array();
	while($line = mysql_fetch_assoc($result))
		$bannerinfo[] = $line;

	incHeader();
	
	echo "<table width=100%>";

	foreach($clientinfo as $client){
		echo "<tr><td class=header align=center> <font size=4>$client[clientname]</font></td></tr>";
		if($client['nlocation'] != ""){
			echo "<tr><td class=body><b>Location:</b></td></tr>";
			echo "<tr><td class=body>$client[nlocation]</td></tr>";
		}
		if($client['ncontact'] != ""){
			echo "<tr><td class=body><b>Contact:</b></td></tr>";
			echo "<tr><td class=body>$client[ncontact]</td></tr>";
		}
		if($client['ndescription'] != ""){
			echo "<tr><td class=body><b>Description:</b></td></tr>";
			echo "<tr><td class=body>$client[ndescription]</td></tr>";
		}
		if($client['nsponsorship'] != ""){
			echo "<tr><td class=body><b>Sponsorship:</b></td></tr>";
			echo "<tr><td class=body>$client[nsponsorship]</td></tr>";
		}
		
		foreach($bannerinfo as $banner){
			if($banner['clientid']==$client['id'])
				echo "<tr><td class=body>" . banner($banner['id']) . "</td></tr>";
		}
		echo "\n";
	}
	
	echo "</table>";
	
	incFooter();
