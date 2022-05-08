<?
	$login = 0;
	require_once('include/general.lib.php');
	require_once('include/mail/emailMessage.php');

	$valid_exts = array('doc', 'odt', 'sxw', 'txt', 'rtf', 'pdf');

	$positions = array();
	foreach ($wiki->getPageChildren('/Jobs/Listings/') as $child) {
		$entry = $wiki->getPage("/Jobs/Listings/${child}");
		$lines = preg_split('/[\r\n]+/', $entry['comment']);

		$positions[ $entry['pageid'] ] = array(
			'title'	=> array_shift($lines),
			'url'	=> "/wiki/Jobs/Listings/${child}",
			'email'	=> $lines
		);
	}

	// show initial form
	if (strlen(getPOSTval('submit', 'string', '')) == 0)
		application_form();


	// form was submitted - validate input
	$errmsgs = array();

	$formdata = array(
		'fullname'		=> str_replace(array("\r", "\n", "\t"), "", getPOSTval('fullname', 'string', '')),
		'emailaddr'		=> str_replace(array("\r", "\n", "\t"), "", getPOSTval('emailaddr', 'string', '')),
		'phonenumber'	=> str_replace(array("\r", "\n", "\t"), "", getPOSTval('phonenumber', 'string', '')),
		'phonetimes'	=> str_replace("\n", "; ", str_replace(array("\r", "\t"), "", getPOSTval('phonetimes', 'string', ''))),
		'contactpref'	=> str_replace(array("\r", "\n", "\t"), "", getPOSTval('contactpref', 'string', '')),
		'positions'		=> getPOSTval('positions', 'array', array()),
		'coverdata'		=> getPOSTval('coverdata', 'string', ''),
		'coverext'		=> getPOSTval('coverext', 'string', ''),
		'resumedata'	=> getPOSTval('resumedata', 'string', ''),
		'resumeext'		=> getPOSTval('resumeext', 'string', '')
	);

	if (! preg_match('/\S/', $formdata['fullname']))
		$errmsgs[] = 'Provide your full name so we know who we\'re talking to.';

	if (! preg_match('/^[^@]+@[^.]+\\./', $formdata['emailaddr']))
		$errmsgs[] = 'A valid e-mail address is required, so we are able to contact you.';

	$phone = &$formdata['phonenumber'];
	$phone = preg_replace('/[^\d]+/', '', $phone);

	if (strlen($phone) == 11 && $phone{0} == '1')
		$phone = substr($phone, 1);

	if (strlen($phone) == 10)
		$phone = '(' . substr($phone, 0, 3) . ') ' . substr($phone, 3, 3) . '-' . substr($phone, 6);

	else {
		$errmsgs[] = 'A telephone number (with area code) is required, so we are able to contact you.';

		if (strlen($phone) == 7)
			$phone = substr($phone, 0, 3) . '-' . substr($phone, 3);
	}

	unset($phone);

	if (! preg_match('/\S/', $formdata['phonetimes']))
		$errmsgs[] = 'Please indicate when it is appropriate for us to contact you by telephone.';

	if ($formdata['contactpref'] != 'email' && $formdata['contactpref'] != 'phone')
		$errmsgs[] = 'Select the primary method of contact you would prefer us to use.';

	foreach ($formdata['positions'] as $key => $positionid) {
		if (! isset($positions[$positionid]))
			unset($formdata['positions'][$key]);
	}

	if (count($formdata['positions']) == 0)
		$errmsgs[] = 'Select one or more job positions you are applying for.';

	if ( ($coverletter = isset($_FILES['coverletter']) ? $_FILES['coverletter'] : false) !== false ) {
		switch ($coverletter['error']) {
			case UPLOAD_ERR_NO_FILE:
				break;

			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				$errmsgs[] = 'The file size of the cover letter document you submitted is too large.';
				break;

			case UPLOAD_ERR_PARTIAL:
				$errmsgs[] = 'The file you submitted for a cover letter was not received. Try again.';
				break;

			case UPLOAD_ERR_OK:
				if ($coverletter['size'] == 0) {
					$errmsgs[] = 'The cover letter document you submitted is empty or does not exist. Try again.';
					break;
				}

				$formdata['coverdata'] = chunk_split(base64_encode(file_get_contents($coverletter['tmp_name'])), 76, "\n");
				$formdata['coverext'] = substr(basename($coverletter['name']), ($pos = strrpos($coverletter['name'], '.')) !== false ? $pos + 1 : 0);

				break;
		}
	}

	if (strlen($formdata['coverdata']) > 0 && ! in_array($formdata['coverext'], $valid_exts)) {
		$formdata['coverdata'] = $formdata['coverext'] = '';
		$errmsgs[] = 'Invalid file extension for cover letter document. Please read the <em>Attach Documents</em> section.';
	}

	if ( ($resume = isset($_FILES['resume']) ? $_FILES['resume'] : false) !== false ) {
		switch ($resume['error']) {
			case UPLOAD_ERR_NO_FILE:
				if (strlen($formdata['resumedata']) == 0)
					$errmsgs[] = 'A résumé document must be submitted with your application.';

				break;

			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				$errmsgs[] = 'The file size of the résumé document you submitted is too large.';
				break;

			case UPLOAD_ERR_PARTIAL:
				$errmsgs[] = 'The file you submitted for a résumé document was not received. Try again.';
				break;

			case UPLOAD_ERR_OK:
				if ($resume['size'] == 0) {
					$errmsgs[] = 'The résumé document you submitted is empty or does not exist. Try again.';
					break;
				}

				$formdata['resumedata'] = chunk_split(base64_encode(file_get_contents($resume['tmp_name'])), 76, "\n");
				$formdata['resumeext'] = substr(basename($resume['name']), ($pos = strrpos($resume['name'], '.')) !== false ? $pos + 1 : 0);

				break;
		}
	}

	if (strlen($formdata['resumedata']) > 0 && ! in_array($formdata['resumeext'], $valid_exts)) {
		$formdata['resumedata'] = $formdata['resumeext'] = '';
		$errmsgs[] = 'Invalid file extension for résumé document. Please read the <em>Attach Documents</em> section.';
	}

	if (count($errmsgs))
		application_form($formdata, $errmsgs);


	foreach ($formdata['positions'] as $positionid) {
		$mail = new emailMessage();
		$mail->setSMTPParams($config['smtp_host'], 25);
		$mail->setFrom('Job Apps <jobs@nexopia.com>');
		$mail->setSubject("JobID ${positionid} [{$positions[$positionid]['title']}] - {$formdata['fullname']}");
		$mail->setText("Applicant: {$formdata['fullname']}\n\nPreferred Contact: {$formdata['contactpref']}\n\nE-mail: {$formdata['emailaddr']}\n\nPhone #: {$formdata['phonenumber']}\n[ {$formdata['phonetimes']} ]");

		if (strlen($formdata['coverdata']) > 0)
			$mail->addAttachment(new stringAttachment(base64_decode(str_replace("\n", "", $formdata['coverdata'])), "CoverLetter.{$formdata['coverext']}"));

		$mail->addAttachment(new stringAttachment(base64_decode(str_replace("\n", "", $formdata['resumedata'])), "Résumé.{$formdata['resumeext']}"));
		$mail->send($positions[$positionid]['email'], 'smtp');
	}

	application_complete();


	function application_form ($formdata = array(), $errmsgs = array()) {
		global $positions;

		$formdata = array_merge(array(
			'fullname'		=> '',
			'emailaddr'		=> '',
			'phonenumber'	=> '',
			'phonetimes'	=> 'any day of the week, 9 AM - 9 PM',
			'contactpref'	=> '',
			'positions'		=> array(),
			'chk'			=> array( 'contactpref' => array(), 'positions' => array() )
		), $formdata);

		$formdata['chk']['contactpref']['email'] = $formdata['contactpref'] == 'email' ? ' checked="checked"' : '';
		$formdata['chk']['contactpref']['phone'] = $formdata['contactpref'] == 'phone' ? ' checked="checked"' : '';

		foreach ($positions as $positionid => $position)
			$formdata['chk']['positions'][$positionid] = in_array($positionid, $formdata['positions']) ? ' checked="checked"' : '';

		$template = new template('jobs/application');

		$template->setMultiple(array(
			'errmsgs'	=> $errmsgs,
			'formdata'	=> $formdata,
			'positions'	=> $positions
		));

		$template->display();
		exit;
	}

	function application_complete () {
		$template = new template('jobs/complete');
		$template->display();
	}
