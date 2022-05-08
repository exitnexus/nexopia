<?

	$login = 0;

	require_once("include/general.lib.php");
	require_once('include/mail/emailMessage.php');

	$defaults = array(
		'companyname' => "",
		'website' => "http://",
		'industry' => "",
		'contactname' => "",
		'jobtitle' => "",
		'email' => "",
		'phone' => "",
		'city' => "",
		'country' => "",
		'startday' => 0,
		'startmonth' => 0,
		'startyear' => 0,
		'endday' => 0,
		'endmonth' => 0,
		'endyear' => 0,
		'budget' => "",
		'adbefore' => '',
		'targetdemo' => '',
		'description' => "",
		);

	$data = getPOSTarray($defaults, 'data');

	for($i=1;$i<=12;$i++)
		$months[$i] = gmdate("F", gmmktime(0,0,0,$i,1,0));

	if($action && !blank($data['contactname'], $data['email']) && isValidEmail($data['email']) && preg_match("/^[-_a-zA-Z0-9 ]*$/", $data['contactname'])){
		$str = "";

		if($data['companyname'])
			$str .= "Company Name: $data[companyname]\n";
		if($data['website'] && $data['website'] != $defaults['website'])
			$str .= "Website: $data[website]\n";
		if($data['industry'])
			$str .= "Industry: $data[industry]\n";
		$str .= "\n";

		if($data['contactname'])
			$str .= "Contact Name: $data[contactname]\n";
		if($data['jobtitle'])
			$str .= "Job title:	$data[jobtitle]\n";
		if($data['email'])
			$str .= "Email: $data[email]\n";
		if($data['phone'])
			$str .= "Phone: $data[phone]\n";
		if($data['city'])
			$str .= "City: $data[city]\n";
		if($data['country'])
			$str .= "Country: $data[country]\n";
		$str .= "\n";

		if($data['startday'] || $data['startmonth'] || $data['startyear']){
			$str .= "Start date: ";
			if($data['startmonth'] && isset($data['startmonth']))
				$str .= $months[$data['startmonth']];
			if($data['startday'])
				$str .= " $data[startday]";
			if($data['startyear'])
				$str .= ", $data[startyear]";
			$str .= "\n";
		}
		if($data['endday'] || $data['endmonth'] || $data['endyear']){
			$str .= "End date: ";
			if($data['endmonth'] && isset($data['endmonth']))
				$str .= $months[$data['endmonth']];
			if($data['endday'])
				$str .= " $data[endday]";
			if($data['endyear'])
				$str .= ", $data[endyear]";
			$str .= "\n";
		}
		if($data['budget'])
			$str .= "Budget: $data[budget]\n";
		if($data['adbefore'])
			$str .= "Advertised online before: $data[adbefore]\n";
		$str .= "\n";
		if($data['targetdemo'])
			$str .= "Target Demographics:\n$data[targetdemo]\n\n";
		if($data['description'])
			$str .= "Campaign Description:\n$data[description]\n\n";

		$msg = new emailMessage();
		$msg->setSMTPParams($config['smtp_host'], 25);
		$msg->setFrom("$data[contactname] <$data[email]>");
		$msg->setSubject("Sales Email");
		$msg->setText($str);
		$msg->send(array('sales@nexopia.com'), 'smtp');


		$template = new template("advertise/complete");
		$template->display();
		exit;
	}


	$template = new template("advertise/advertise");
	$template->set('data', $data);
	$template->set('startday', make_select_list(range(1,31), $data['startday']));
	$template->set('startmonth', make_select_list_key($months, $data['startmonth']));
	$template->set('startyear', make_select_list(range(gmdate("Y"),gmdate("Y")+2), $data['startyear']));
	$template->set('endday', make_select_list(range(1,31), $data['endday']));
	$template->set('endmonth', make_select_list_key($months, $data['endmonth']));
	$template->set('endyear', make_select_list(range(gmdate("Y"),gmdate("Y")+2), $data['endyear']));
	$template->display();

