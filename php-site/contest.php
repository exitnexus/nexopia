<?

	$login=0;

	require_once("include/general.lib.php");

	if(empty($id))
		die("Bad Contest ID");

	$contest = $contests->getContest($id);

	if(!$userData['loggedIn'] && $contest['anonymous'] == 'n'){
		header("location: /login.php?referer=" . urlencode($REQUEST_URI));
		exit;
	}

	if($contact = getPOSTval('contact', 'array')){

		$str = "";
		foreach($contact as $name => $v)
			$str .= "$name: $v\n";

		$final = $contests->addEntry($id, $userData['userid'], $str);

		incHeader();

		echo $final;

		incFooter();
		exit;
	}

	incHeader();

	echo "<form action=$_SERVER[PHP_SELF] method=post>";
	echo "<input type=hidden name=id value=$id>";

	echo $contest['content'];

	echo "</form>";

	incFooter();

