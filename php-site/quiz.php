<?
	$login = 0;
	require_once('include/general.lib.php');

	$userid = $userData['loggedIn'] ? $userData['userid'] : 0;
	$ipaddr = getREQval('ipaddr', 'integer', rand(1, time()));//ip2long($_SERVER['REMOTE_ADDR']);
	$email = $userData['loggedIn'] ? $useraccounts->getEmail($userid) : getPOSTval('email', 'string', '');


	$quizid = getREQval('quizid', 'integer', 0);

	if ($quizid <= 0)
		errorpage("The quiz you are attempting to access does not exist.");

	$quiz = $quizzes->fetch($quizid);
	if ($quiz->quizid == 0 || $quiz->enabled == 'n')
		errorpage("The quiz you are attempting to access does not exist.");

	if ($quiz->starttime > time())
		errorpage("The quiz you are attempting to access does not open until " . date('D, M d, Y h:i A T', $quiz->starttime) . ".");

	if ($quiz->endtime < time() && $quiz->endtime > 0)
		errorpage("The quiz you are attempting to access closed " . date('D, M d, Y h:i A T', $quiz->endtime) . ".");

	if ($quiz->allowanon == 'n' && ! $userData['loggedIn'])
		errorpage("The quiz you are attempting to access does not permit anonymous entries. Please <a class=\"body\" href=\"/login.php?referer=%2Fquiz.php%3Faction%3Dbeginquiz%26quizid%3D{$quizid}\">login to an account</a>.");

	$entry = null;
	$entryid = getREQval('entryid', 'integer', 0);

	if ($entryid > 0) {
		$entry = $quiz->entries->fetch($entryid);

		if (
			$entry->entryid == 0 ||
			$entry->userid != $userid ||
			$entry->ipaddr != $ipaddr ||
			$entry->email != $email
		)
			errorpage("The quiz entry you are attempting to access does not exist.");
	}


	$entrycount = count( $userid == 0 ? $quiz->entries->search($ipaddr, $email) : $quiz->entries->search($ipaddr, $email, $userid) );

	$action = getREQval('action', 'string', 'beginquiz');

	switch ($action) {
		case 'beginquiz':
			beginquiz($quiz);

		case 'newquiz':
			newquiz($quiz);

		case 'submitquiz':
			submitquiz($quiz, $entry);
	}


	function errorpage ($errmsg) {
		$template = new template('quizzes/errorpage');
		$template->set('errmsg', $errmsg);
		$template->display();

		exit;
	}

	function beginquiz ($quiz, $errmsg = '') {
		global $userData;

		$template = new template('quizzes/beginquiz');

		$template->setMultiple(array(
			'quiz'			=> $quiz,
			'questioncount'	=> $quiz->questioncount == 0 ? count($quiz->fields->fields) : $quiz->questioncount,
			'loggedin'		=> $userData['loggedIn'],
			'errmsg'		=> $errmsg
		));

		$template->display();
		exit;
	}

	function newquiz ($quiz) {
		global $userid, $ipaddr, $email, $entrycount;

		if (! preg_match('/^[^@]+@[^.]+\./', $email)) {
			beginquiz($quiz, 'Invalid e-mail address. You must provide a valid address before continuing.');
			return;
		}

		if ($quiz->maxperuser > 0 && $entrycount >= $quiz->maxperuser)
			errorpage("This quiz is limited to a maximum of {$quiz->maxperuser} " . ($quiz->maxperuser == 1 ? 'entry' : 'entries') . " per user. You appear to have reached this limit.");

		$info = array(
			'userid'	=> $userid,
			'ipaddr'	=> $ipaddr,
			'email'		=> $email
		);

		$entry = $quiz->entries->add($info);
		writequiz($quiz, $entry);
	}

	function writequiz ($quiz, $entry, $errmsg = "") {
		global $ipaddr;

		if ($entry === null)
			errorpage("The quiz entry you are attempting to access does not exist.");

		if ($entry->status != 'pending')
			errorpage("The quiz entry you are trying to access has already been submitted.");

		$fields = array();
		foreach (array_keys($entry->data) as $fieldid) {
			foreach ($quiz->fields->fields as $field) {
				if ($field->fieldid == $fieldid) {
					$info = array(
						'fieldid'	=> $field->fieldid,
						'question'	=> $field->question,
						'fieldtype'	=> $field->fieldtype,
						'options'	=> array(),
						'answer'	=> $entry->data[$fieldid]
					);

					foreach ($field->options->options as $option)
						$info['options'][] = array(
							'optionid'	=> $option->optionid,
							'option'	=> $option->option,
							'chkradio'	=> $field->fieldtype == 'radio' && $entry->data[$fieldid] == $option->option ? ' checked="checked"' : '',
							'chkcheckbox'=> $field->fieldtype == 'checkbox' && array_search($option->option, $entry->data[$fieldid]) !== false ? ' checked="checked"' : '',
							'selected'	=> $field->fieldtype == 'select' && $entry->data[$fieldid] == $option->option ? ' selected="selected"' : ''
						);

					$fields[] = $info;
				}
			}
		}

		$template = new template('quizzes/writequiz');
		$template->setMultiple(array(
			'quiz'		=> $quiz,
			'ipaddr'	=> $ipaddr,
			'entry'		=> $entry,
			'fields'	=> $fields,
			'errmsg'	=> $errmsg
		));
		$template->display();
		exit;
	}

	function submitquiz ($quiz, $entry) {
		if ($entry === null)
			errorpage("The quiz entry you are attempting to access does not exist.");

		if ($entry->status != 'pending')
			errorpage("The quiz entry you are trying to access has already been submitted.");

		$submitted = getREQval('field', 'array', array());
		$required = array();
		$missing = array();

		foreach (array_keys($entry->data) as $fieldid) {
			foreach ($quiz->fields->fields as $field) {
				if ($field->fieldid == $fieldid)
					$required[] = $field;
			}
		}

		// loop through the questions that the user should be providing answers to
		foreach ($required as $field) {
			if (! isset($submitted[$field->fieldid])) {
				$missing[] = $field->fieldid;
				continue;
			}

			$submit = is_array( ($submit = $submitted[$field->fieldid]) ) ? $submit : array($submit);

			if ($field->fieldtype == 'radio' || $field->fieldtype == 'select') {
				$submit = array_shift($submit);

				$found = false;
				foreach ($field->options->options as $option) {
					if (strtolower($option->option) == strtolower($submit)) {
						$entry->data[ $field->fieldid ] = $option->option;
						$found = true;
						break;
					}
				}

				if (! $found)
					$missing[] = $field->fieldid;
			}

			else if ($field->fieldtype == 'checkbox') {
				foreach ($submit as $key => $val) {
					$found = false;

					foreach ($field->options->options as $option) {
						if (strtolower($option->option) == strtolower($val)) {
							$submit[$key] = $option->option;
							$found = true;
							break;
						}
					}

					if (! $found)
						unset($submit[$key]);
				}

				$entry->data[ $field->fieldid ] = count($submit) > 0 ? array_values($submit) : null;
			}

			else if ($field->fieldtype == 'text') {
				$submit = trim(array_shift($submit));

				if (strlen($submit) == 0) {
					$missing[] = $field->fieldid;
					continue;
				}

				$entry->data[ $field->fieldid ] = $submit;
			}
		}

		foreach ($missing as $missed)
			$entry->data[$missed] = null;

		$entry->save(true);

		if (count($missing) > 0)
			writequiz($quiz, $entry, count($missing) . " questions were not answered. Please complete the entire quiz before submitting.");


		foreach ($required as $field) {
			if ($field->fieldtype == 'radio' || $field->fieldtype == 'select') {
				foreach ($field->options->options as $option) {
					if ($entry->data[$field->fieldid] == $option->option) {
						if ($option->correctans == 'y')
							$entry->right++;
						else
							$entry->wrong++;

						break;
					}
				}
			}

			elseif ($field->fieldtype == 'checkbox') {
				$right = $wrong = 0;

				foreach ($field->options->options as $option) {
					if (array_search($option->option, $entry->data[$field->fieldid]) !== false) {
						if ($option->correctans == 'y')
							$right++;
						else
							$wrong++;
					}
				}

				if ($wrong == 0 && $right >= $field->answercount)
					$entry->right++;
				else
					$entry->wrong++;
			}

			else {
				foreach ($field->options->options as $option) {
					if (strtolower($entry->data[$field->fieldid]) == strtolower($option->option)) {
						if ($option->correctans == 'y')
							$entry->right++;
						else
							$entry->wrong++;

						break;
					}
				}
			}
		}

		$entry->endtime = time();
		$entry->status = 'complete';
		$entry->score = sprintf('%.2f', $entry->right / ($entry->right + $entry->wrong) * 100);
		$entry->save();

		$template = new template('quizzes/donequiz');
		$template->set('quiz', $quiz);
		$template->display();
	}

