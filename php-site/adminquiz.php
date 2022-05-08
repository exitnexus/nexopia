<?
	$login = 0;
	require_once('include/general.lib.php');

	if (! $mods->isAdmin($userData['userid'], 'contests'))
		die('Permission denied.');

	$entries_per_page = 2000;

	$action = getREQval('action', 'string', 'listquizzes');
	$quizid = getREQval('quizid', 'integer', 0);

	switch ($action) {
		case 'listquizzes':
			$formdata = array();

			foreach ($quizzes->fetchall() as $quiz) {
				$info = array(
					'quizid'	=> $quiz->quizid,
					'title'		=> strlen($quiz->title) > 30 ? substr($quiz->title, 0, 30) . '...' : $quiz->title,
					'enabled'	=> $quiz->enabled == 'y' ? 'Yes' : 'No',
					'running'	=> ($quiz->starttime > 0 ? date('M d, Y', $quiz->starttime) : 'Indefinite') . ' - ' . ($quiz->endtime > 0 ? date('M d, Y', $quiz->endtime) : 'Indefinite'),
					'allowanon'	=> $quiz->allowanon == 'y' ? 'Yes' : 'No',
					'maxperuser'=> $quiz->maxperuser == 0 ? 'Unlimited' : $quiz->maxperuser
				);

				$info['totalfields'] = count($quiz->fields->fields);
				$info['questioncount'] = $quiz->questioncount == 0 ? $info['totalfields'] : $quiz->questioncount;

				$info['entrycount'] = $quiz->entries->entrycount();

				$formdata['quizzes'][] = $info;
			}

			$template = new template('quizzes/adminlistquizzes');
			$template->set('formdata', $formdata);
			$template->display();

			break;

		case 'newquiz':
			$template = new template('quizzes/admineditquiz');

			$template->set('formdata', array(
				'quiz'	=> array(
					'quizid'		=> 0,
					'title'			=> '',
					'starttime'		=> date('r'),
					'endtime'		=> '',
					'enabled'		=> 'n',
					'allowanon'		=> 'n',
					'maxperuser'	=> 1,
					'questioncount'	=> 0,
					'starttext'		=> '',
					'endtext'		=> ''
				),

				'chkenabled'	=> ' disabled="disabled"',
				'chkallowanon'	=> ''
			));

			$template->display();
			break;
	
		case 'editquiz':
			$quiz = $quizzes->fetch($quizid);

			$template = new template('quizzes/admineditquiz');

			$template->set('formdata', array(
				'quiz'	=> array(
					'quizid'		=> $quiz->quizid,
					'title'			=> $quiz->title,
					'starttime'		=> $quiz->starttime > 0 ? date('r', $quiz->starttime) : date('r'),
					'endtime'		=> $quiz->endtime > 0 ? date('r', $quiz->endtime) : '',
					'enabled'		=> $quiz->enabled,
					'allowanon'		=> $quiz->allowanon,
					'maxperuser'	=> $quiz->maxperuser,
					'questioncount'	=> $quiz->questioncount,
					'starttext'		=> $quiz->starttext,
					'endtext'		=> $quiz->endtext
				),

				'chkenabled'	=> $quiz->enabled == 'y' ? ' checked="checked"' : '',
				'chkallowanon'	=> $quiz->allowanon == 'y' ? ' checked="checked"' : ''
			));

			$template->display();
			break;

		case 'savequiz':
			$formdata = array(
				'title'			=> strlen($title = getPOSTval('title', 'string', '')) > 0 ? $title : 'Untitled Quiz',
				'enabled'		=> strlen($enabled = getPOSTval('enabled', 'string', '')) > 0 ? 'y' : 'n',
				'starttime'		=> ($starttime = strtotime(getPOSTval('starttime', 'string', ''))) !== false ? $starttime : time(),
				'endtime'		=> ($endtime = strtotime(getPOSTval('endtime', 'string', ''))) !== false ? $endtime : 0,
				'starttext'		=> getPOSTval('starttext', 'string', ''),
				'endtext'		=> getPOSTval('endtext', 'string', ''),
				'allowanon'		=> strlen($allowanon = getPOSTval('allowanon', 'string', '')) > 0 ? 'y' : 'n',
				'maxperuser'	=> ($maxperuser = getPOSTval('maxperuser', 'integer', 0)) > 0 ? $maxperuser : 0,
				'questioncount'	=> ($questioncount = getPOSTval('questioncount', 'integer', 0)) > 0 ? $questioncount : 0
			);

			// new quiz
			if ($quizid == 0)
				$quiz = $quizzes->fetch($formdata);

			// saving changes to existing quiz
			else {
				$quiz = $quizzes->fetch($quizid);

				$quiz->title		= $formdata['title'];
				$quiz->starttime	= $formdata['starttime'];
				$quiz->endtime		= $formdata['endtime'];
				$quiz->starttext	= $formdata['starttext'];
				$quiz->endtext		= $formdata['endtext'];
				$quiz->enabled		= $formdata['enabled'];
				$quiz->allowanon	= $formdata['allowanon'];
				$quiz->maxperuser	= $formdata['maxperuser'];
				$quiz->questioncount= $formdata['questioncount'];
			}

			$quiz->save();

			header("Location: {$_SERVER['PHP_SELF']}?action=listquizzes");
			break;

		case 'deletequiz':
			$quizzes->fetch($quizid)->remove();

			header("Location: {$_SERVER['PHP_SELF']}?action=listquizzes");
			break;

		case 'listfields':
			$quiz = $quizzes->fetch($quizid);

			$formdata = array('quizid' => $quizid, 'fields' => array());

			foreach ($quiz->fields->fields as $field) {
				$info = array(
					'fieldid'		=> $field->fieldid,
					'question'		=> strlen($field->question) > 30 ? substr($field->question, 0, 30) . '...' : $field->question,
					'fieldtype'		=> $field->fieldtype
				);

				$info['totaloptions'] = count($field->options->options);
				$info['answercount'] = $field->answercount;

				$formdata['fields'][] = $info;
			}

			$template = new template('quizzes/adminlistfields');
			$template->set('formdata', $formdata);
			$template->display();
			break;

		case 'newfield':
			$formdata = array(
				'quizid'	=> $quizid,

				'field'		=> array(
					'fieldid'		=> 0,
					'question'		=> '',
					'fieldtype'		=> 'radio',
					'options'		=> array(),
					'answercount'	=> 1
				),

				'chkfieldtyperadio'		=> ' checked="checked"',
				'chkfieldtypecheckbox'	=> '',
				'chkfieldtypeselect'	=> '',
				'chkfieldtypetext'		=> ''
			);

			$template = new template('quizzes/admineditfield');
			$template->set('formdata', $formdata);
			$template->display();
			break;

		case 'editfield':
			$quiz = $quizzes->fetch($quizid);
			$fieldid = getREQval('fieldid', 'integer', 0);

			$formdata = array(
				'quizid'	=> $quizid,

				'field'		=> array(
					'fieldid'		=> 0,
					'question'		=> '',
					'fieldtype'		=> 'radio',
					'options'		=> array(),
					'answercount'	=> 1
				),

				'chkfieldtyperadio'		=> ' checked="checked"',
				'chkfieldtypecheckbox'	=> '',
				'chkfieldtypeselect'	=> '',
				'chkfieldtypetext'		=> ''
			);

			foreach ($quiz->fields->fields as $field) {
				if ($field->fieldid == $fieldid) {
					$formdata['field'] = array_merge($formdata['field'], array(
						'fieldid'		=> $field->fieldid,
						'question'		=> $field->question,
						'fieldtype'		=> $field->fieldtype,
						'answercount'	=> $field->answercount
					));

					$formdata['chkfieldtype' . $field->fieldtype] = ' checked="checked"';

					foreach($field->options->options as $option) {
						$formdata['field']['options'][] = array(
							'optionid'	=> $option->optionid,
							'option'	=> strlen($option->option) > 20 ? substr($option->option, 0, 20) . '...' : $option->option,
							'correctans'=> $option->correctans
						);
					}

					break;
				}
			}

			$template = new template('quizzes/admineditfield');
			$template->set('formdata', $formdata);
			$template->display();
			break;

		case 'savefield':
			$quiz = $quizzes->fetch($quizid);

			$formdata = array(
				'question'		=> strlen($question = getPOSTval('question', 'string', '')) > 0 ? $question : 'No question entered!',
				'fieldtype'		=> strlen($fieldtype = getPOSTval('fieldtype', 'string', '')) > 0 ? $fieldtype : 'radio',
				'answercount'	=> getPOSTval('answercount', 'integer', 1)
			);

			// new field
			if ( ($fieldid = getPOSTval('fieldid', 'integer', 0)) == 0 )
				$quiz->fields->add($formdata);

			// saving changes to existing field
			else {
				foreach ($quiz->fields->fields as $field) {
					if ($field->fieldid == $fieldid) {
						$field->question	= $formdata['question'];
						$field->fieldtype	= $formdata['fieldtype'];
						$field->answercount	= $formdata['answercount'];

						$field->save();
						break;
					}
				}
			}

			header("Location: {$_SERVER['PHP_SELF']}?action=listfields&quizid={$quizid}");
			break;

		case 'deletefield':
			$quiz = $quizzes->fetch($quizid);
			$fieldid = getPOSTval('fieldid', 'integer', 0);

			foreach ($quiz->fields->fields as $field) {
				if ($field->fieldid == $fieldid) {
					$quiz->fields->remove($field);
					break;
				}
			}

			header("Location: {$_SERVER['PHP_SELF']}?action=listfields&quizid={$quizid}");
			break;

		case 'listoptions':
			$quiz = $quizzes->fetch($quizid);
			$fieldid = getREQval('fieldid', 'integer', 0);

			$formdata = array('quizid' => $quizid, 'fieldid' => $fieldid, 'options' => array());

			foreach ($quiz->fields->fields as $field) {
				if ($field->fieldid == $fieldid) {
					foreach ($field->options->options as $option)
						$formdata['options'][] = array(
							'optionid'	=> $option->optionid,
							'option'	=> strlen($option->option) > 30 ? substr($option->option, 0, 30) . '...' : $option->option,
							'correctans'=> $option->correctans == 'y' ? 'Yes' : 'No'
						);

					break;
				}
			}

			$template = new template('quizzes/adminlistoptions');
			$template->set('formdata', $formdata);
			$template->display();
			break;

		case 'newoption':
			$quiz = $quizzes->fetch($quizid);
			$fieldid = getREQval('fieldid', 'integer', 0);

			$formdata = array(
				'quizid'	=> $quizid,
				'fieldid'	=> $fieldid,

				'option'	=> array(
					'optionid'	=> 0,
					'option'	=> '',
					'correctans'=> 'n'
				),

				'chkcorrectansy'	=> '',
				'chkcorrectansn'	=> ' checked="checked"'
			);

			$template = new template('quizzes/admineditoption');
			$template->set('formdata', $formdata);
			$template->display();
			break;

		case 'editoption':
			$quiz = $quizzes->fetch($quizid);
			$fieldid = getREQval('fieldid', 'integer', 0);
			$optionid = getREQval('optionid', 'integer', 0);

			$formdata = array(
				'quizid'	=> $quizid,
				'fieldid'	=> $fieldid,

				'option'	=> array(
					'optionid'	=> 0,
					'option'	=> '',
					'correctans'=> 'n'
				),

				'chkcorrectansy'	=> '',
				'chkcorrectansn'	=> ''
			);

			foreach ($quiz->fields->fields as $field) {
				if ($field->fieldid == $fieldid) {
					foreach ($field->options->options as $option) {
						if ($option->optionid == $optionid) {
							$formdata['option'] = array_merge($formdata['option'], array(
								'optionid'	=> $optionid,
								'option'	=> $option->option,
								'correctans'=> $option->correctans
							));

							$formdata['chkcorrectans' . $option->correctans] = ' checked="checked"';
						}
						}
				}
			}
								
			$template = new template('quizzes/admineditoption');
			$template->set('formdata', $formdata);
			$template->display();
			break;

		case 'saveoption':
			$quiz = $quizzes->fetch($quizid);
			$fieldid = getPOSTval('fieldid', 'integer', 0);
			$optionid = getPOSTval('optionid', 'integer', 0);

			$formdata = array(
				'option'		=> strlen($option = getPOSTval('option', 'string', '')) > 0 ? $option : 'No option entered!',
				'correctans'	=> strlen($correctans = getPOSTval('correctans', 'string', '')) > 0 ? $correctans : 'n'
			);

			// new option
			if ($optionid == 0) {
				foreach ($quiz->fields->fields as $field) {
					if ($field->fieldid == $fieldid) {
						$field->options->add($formdata);
						break;
					}
				}
			}

			// changes to existing option
			else {
				foreach ($quiz->fields->fields as $field) {
					if ($field->fieldid == $fieldid) {
						foreach ($field->options->options as $option) {
							if ($option->optionid == $optionid) {
								$option->option	= $formdata['option'];
								$option->correctans	= $formdata['correctans'];

								$option->save();
								break 2;
							}
						}
					}
				}
			}

			header("Location: {$_SERVER['PHP_SELF']}?action=listoptions&quizid={$quizid}&fieldid={$fieldid}");
			break;

		case 'deleteoption':
			$quiz = $quizzes->fetch($quizid);
			$fieldid = getPOSTval('fieldid', 'integer', 0);
			$optionid = getPOSTval('optionid', 'integer', 0);

			foreach ($quiz->fields->fields as $field) {
				if ($field->fieldid == $fieldid) {
					foreach ($field->options->options as $option) {
						if ($option->optionid == $optionid) {
							$field->options->remove($option);
							break 2;
						}
					}
				}
			}

			header("Location: {$_SERVER['PHP_SELF']}?action=listoptions&quizid={$quizid}&fieldid={$fieldid}");
			break;

		case 'listentries':
			$quiz = $quizzes->fetch($quizid);
			$page = getREQval('page', 'integer', 1);

			$entries = array();

			$result = $quiz->entries->fetchrange(($page - 1) * $entries_per_page, $entries_per_page);

			foreach ($result['entries'] as $entry) {
				$entries[] = array(
					'entryid'	=> $entry->entryid,
					'userid'	=> $entry->userid,
					'email'		=> $entry->email,
					'status'	=> $entry->status,
					'starttime'	=> date('M d/y H:i', $entry->starttime),
					'endtime'	=> date('M d/y H:i', $entry->endtime),
					'right'		=> $entry->right,
					'wrong'		=> $entry->wrong,
					'score'		=> sprintf('%.2f', $entry->score),
					'chkwinner'	=> $entry->status == 'winner' ? ' checked="checked"' : ''
				);
			}

			$template = new template('quizzes/adminlistentries');
			$template->setMultiple(array(
				'quizid'	=> $quizid,
				'entries'	=> $entries,
				'pages'		=> array(
					'nums'	=> range(1, ceil($result['totalrows'] / $entries_per_page)),
					'cur'	=> $page,
					'prev'	=> $page > 1 ? $page - 1 : 0,
					'next'	=> ceil($result['totalrows']) > $page * $entries_per_page ? $page + 1 : 0,
				)
			));
			$template->display();
			break;

		case 'viewquiz':
			$quiz = $quizzes->fetch($quizid);

			$entryid = getREQval('entryid', 'integer', 0);
			$entry = $quiz->entries->fetch($entryid);


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

			$template = new template('quizzes/viewquiz');
			$template->setMultiple(array(
				'quizid'	=> $quizid,
				'entry'		=> array(
					'entryid'	=> $entry->entryid,
					'userid'	=> $entry->userid,
					'email'		=> $entry->email,
					'starttime'	=> date('M d/y H:i', $entry->starttime),
					'endtime'	=> date('M d/y H:i', $entry->endtime),
					'right'		=> $entry->right,
					'wrong'		=> $entry->wrong,
					'score'		=> sprintf('%.2f', $entry->score),
					'winner'	=> $entry->status == 'winner' ? 'Yes' : 'No'
				),
				'fields'	=> $fields
			));
			$template->display();
			break;

		case 'updateentry':
			$entryid = getREQval('entryid', 'integer', 0);
			$winner = getREQval('winner', 'string', 'y');

			$quiz = $quizzes->fetch($quizid);
			$entry = $quiz->entries->fetch($entryid);

			if ($entry->status == 'winner' || $entry->status == 'complete') {
				$entry->status = $winner == 'y' ? 'winner' : 'complete';
				$entry->save();
			}

			exit;

		default:
			errorpage('Unknown action for this script.');
	}
