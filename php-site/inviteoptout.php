<?

	$login = 1;

	include_once("include/general.lib.php");

	$email = getPOSTval('email');

	if(!$email){
		$email = getREQval('email');
		$k = getREQval('k');

		if(!checkKey($email, $k, -1))
			$email = '';
	}

	if($email && isValidEmail($email)){
		$db->prepare_query("INSERT IGNORE INTO inviteoptout SET email = ?", $email);

		$template = new template('invite/inviteoptoutcomplete');
		$template->display();
		exit;
	}

	$template = new template('invite/inviteoptout');
	$template->display();

