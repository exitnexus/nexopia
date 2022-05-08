<?
	class quizzes {
		private $db;

		function __construct ($db) {
			$this->db = $db;
		}

		function fetch ($quiz_start = null) {
			return new quiz($this->db, $quiz_start);
		}

		function fetchall () {
			$quizzes = array();

			$sth = $this->db->prepare_query('SELECT quizid FROM quizzes ORDER BY quizid');
			while ( ($row = $sth->fetchrow()) !== false )
				$quizzes[] = new quiz($this->db, $row['quizid']);

			return $quizzes;
		}
	}


	class quiz {
		private $db, $quizinfo_empty, $quizinfo, $modified, $fields, $entries;

		function __construct ($db, $quiz_start = null) {
			$this->db = $db;

			$this->quizinfo_empty = array(
				'quizid'		=> 0,
				'title'			=> 'Untitled Quiz',
				'enabled'		=> 'n',
				'starttime'		=> 0,
				'endtime'		=> 0,
				'starttext'		=> '',
				'endtext'		=> '',
				'allowanon'		=> 'n',
				'maxperuser'	=> 1,
				'questioncount'	=> 0
			);

			$this->modified = false;

			if ($quiz_start === null) {
				$this->quizinfo = $this->quizinfo_empty;
				$this->fields = new quizfields($this->db, 0);
				$this->entries = new quizentries($this->db, 0);
				$this->modified = true;
			}

			elseif (is_array($quiz_start)) {
				$this->quizinfo = $this->quizinfo_empty;
				$this->fields = new quizfields($this->db, 0);
				$this->entries = new quizentries($this->db, 0);
				$this->modified = true;

				foreach ($quiz_start as $key => $val)
					$this->quizinfo[$key] = $val;
			}

			else {
				$sth = $this->db->prepare_query('SELECT * FROM quizzes WHERE quizid = #', $quiz_start);

				if ( ($row = $sth->fetchrow()) === false ) {
					$this->quizinfo = $this->quizinfo_empty;
					$this->fields = new quizfields($this->db, 0);
					$this->entries = new quizentries($this->db, 0);
				}

				else {
					$this->quizinfo = $row;
					$this->fields = new quizfields($this->db, $this->quizinfo['quizid']);
					$this->entries = new quizentries($this->db, $this->quizinfo['quizid']);
				}
			}
		}

		function __get ($name) {
			if (isset($this->quizinfo[$name]))
				return $this->quizinfo[$name];

			elseif ($name == 'fields')
				return $this->fields;

			elseif ($name == 'entries')
				return $this->entries;

			else
				return null;
		}

		function __set ($name, $val) {
			switch ($name) {
				case 'title':
					$val = substr($val, 0, 255);
					break;

				case 'enabled':
				case 'allowanon':
					if ($val != 'y' && $val != 'n')
						$val = 'n';
					break;

				case 'starttime':
				case 'endtime':
				case 'maxperuser':
				case 'questioncount':
					$val = (int)$val;
					break;

				case 'starttext':
				case 'endtext':
					$val = substr($val, 0, 65536);
					break;
			}

			if (in_array($name, array(
				'title', 'enabled', 'allowanon', 'starttime', 'endtime',
				'maxperuser', 'questioncount', 'starttext', 'endtext'
			)) && $this->quizinfo[$name] != $val) {
				$this->quizinfo[$name] = $val;
				$this->modified = true;
			}
		}

		function save () {
			if (! $this->modified)
				return;

			// add new quiz
			if ($this->quizinfo['quizid'] == 0) {
				$this->db->prepare_query(
					'INSERT INTO quizzes SET title = ?, enabled = ?, starttime = #, endtime = #, ' .
					'starttext = ?, endtext = ?, allowanon = ?, maxperuser = #, questioncount = #',

					$this->quizinfo['title'], $this->quizinfo['enabled'], $this->quizinfo['starttime'],
					$this->quizinfo['endtime'], $this->quizinfo['starttext'], $this->quizinfo['endtext'],
					$this->quizinfo['allowanon'], $this->quizinfo['maxperuser'], $this->quizinfo['questioncount']
				);

				$this->quizinfo['quizid'] = $this->db->insertid();
			}

			// saving changes to existing quiz
			else {
				$this->db->prepare_query(
					'UPDATE quizzes SET title = ?, enabled = ?, starttime = #, endtime = #, ' .
					'starttext = ?, endtext = ?, allowanon = ?, maxperuser = #, questioncount = # ' .
					'WHERE quizid = #',

					$this->quizinfo['title'], $this->quizinfo['enabled'], $this->quizinfo['starttime'],
					$this->quizinfo['endtime'], $this->quizinfo['starttext'], $this->quizinfo['endtext'],
					$this->quizinfo['allowanon'], $this->quizinfo['maxperuser'], $this->quizinfo['questioncount'],
					$this->quizinfo['quizid']
				);
			}

			$this->modified = false;
		}

		function remove () {
			$this->fields->removeall();	
			$this->entries->removeall();
			$this->db->prepare_query('DELETE FROM quizzes WHERE quizid = #', $this->quizid);
		}
	}


	class quizfields {
		private $db, $quizid, $fields;

		function __construct ($db, $quizid) {
			$this->db = $db;
			$this->quizid = $quizid;
			$this->fields = array();

			if ($quizid > 0) {
				$sth = $this->db->prepare_query('SELECT fieldid FROM quizfields WHERE quizid = # ORDER BY fieldid', $quizid);
				while ( ($row = $sth->fetchrow()) !== false )
					$this->fields[] = new quizfield($this->db, $quizid, $row['fieldid']);
			}
		}

		function __get ($name) {
			if ($name == 'fields')
				return $this->fields;

			else
				return null;
		}

		function add ($fieldinfo) {
			$field = new quizfield($this->db, $this->quizid, $fieldinfo);
			$field->save();

			return $field;
		}

		function remove ($rmfield) {
			if ( ($key = array_search($rmfield, $this->fields)) !== false ) {
				$this->fields[$key]->remove();
				array_splice($this->fields, $key, 1);
			}
		}

		function removeall () {
			foreach ($this->fields as $field)
				$this->remove($field);
		}
	}


	class quizfield {
		private $db, $fieldinfo_empty, $fieldinfo, $modified, $options;

		function __construct ($db, $quizid, $field_start = null) {
			$this->db = $db;

			$this->fieldinfo_empty = array(
				'fieldid'		=> 0,
				'quizid'		=> $quizid,
				'question'		=> 'No Question entered!',
				'fieldtype'		=> 'radio',
				'answercount'	=> 1
			);
			$this->modified = false;

			if ($field_start === null) {
				$this->fieldinfo = $this->fieldinfo_empty;
				$this->options = new quizoptions($this->db, $quizid, 0);
				$this->modified = true;
			}

			elseif (is_array($field_start)) {
				$this->fieldinfo = $this->fieldinfo_empty;
				$this->options = new quizoptions($this->db, $quizid, 0);
				$this->modified = true;

				foreach ($field_start as $key => $val)
					$this->fieldinfo[$key] = $val;
			}

			else {
				$sth = $this->db->prepare_query('SELECT * FROM quizfields WHERE fieldid = #', $field_start);

				if ( ($row = $sth->fetchrow()) === false ) {
					$this->fieldinfo = $this->fieldinfo_empty;
					$this->options = new quizoptions($this->db, $quizid, 0);
				}

				else {
					$this->fieldinfo = $row;
					$this->options = new quizoptions($this->db, $quizid, $field_start);
				}
			}
		}

		function __get ($name) {
			if (isset($this->fieldinfo[$name]))
				return $this->fieldinfo[$name];
		   
			elseif ($name == 'options')
				return $this->options;
		
			else
				return null;
		}

		function __set ($name, $val) {
			switch ($name) {
				case 'question':
					$val = substr($val, 0, 65536);
					break;

				case 'fieldtype':
					if (! in_array($val, array('text', 'radio', 'checkbox', 'select')))
						$val = 'radio';
					break;

				case 'answercount':
					$val = (int)$val;
					break;
			}

			if (in_array($name, array('question', 'fieldtype', 'answercount')) && $this->fieldinfo[$name] != $val) {
				$this->fieldinfo[$name] = $val;
				$this->modified = true;
			}
		}

		function remove () {
			$this->options->removeall();
			$this->db->prepare_query('DELETE FROM quizfields WHERE fieldid = #', $this->fieldid);
		}

		function save () {
			if (! $this->modified)
				return;

			// add new field
			if ($this->fieldinfo['fieldid'] == 0) {
				$this->db->prepare_query(
					'INSERT INTO quizfields SET quizid = #, question = ?, fieldtype = ?, answercount = #',

					$this->fieldinfo['quizid'], $this->fieldinfo['question'], $this->fieldinfo['fieldtype'], $this->fieldinfo['answercount']
				);

				$this->fieldinfo['fieldid'] = $this->db->insertid();
			}

			// saving changes to existing field
			else {
				$this->db->prepare_query(
					'UPDATE quizfields SET question = ?, fieldtype = ?, answercount = # WHERE fieldid = #',
					
					$this->fieldinfo['question'], $this->fieldinfo['fieldtype'], $this->fieldinfo['answercount'], $this->fieldinfo['fieldid']
				);
			}

			$this->modified = false;
		}
	}


	class quizoptions {
		private $db, $quizid, $fieldid, $options;

		function __construct ($db, $quizid, $fieldid) {
			$this->db = $db;
			$this->quizid = $quizid;
			$this->fieldid = $fieldid;
			$this->options = array();

			if ($fieldid > 0) {
				$sth = $this->db->prepare_query('SELECT optionid FROM quizoptions WHERE fieldid = # ORDER BY optionid', $fieldid);
				while ( ($row = $sth->fetchrow()) !== false )
					$this->options[] = new quizoption($this->db, $quizid, $fieldid, $row['optionid']);
			}
		}

		function __get ($name) {
			if ($name == 'options')
				return $this->options;

			else
				return null;
		}

		function add ($optioninfo) {
			$option = new quizoption($this->db, $this->quizid, $this->fieldid, $optioninfo);
			$option->save();
			return $option;
		}

		function remove ($rmoption) {
			if ( ($key = array_search($rmoption, $this->options)) !== false ) {
				$this->options[$key]->remove();
				array_splice($this->options, $key, 1);
			}
		}

		function removeall () {
			foreach ($this->options as $option)
				$this->remove($option);
		}
	}


	class quizoption {
		private $db, $optioninfo_empty, $optioninfo, $modified;

		function __construct ($db, $quizid, $fieldid, $option_start = null) {
			$this->db = $db;

			$this->optioninfo_empty = array(
				'optionid'	=> 0,
				'fieldid'	=> $fieldid,
				'quizid'	=> $quizid,
				'option'	=> 'No Option',
				'correctans'=> 'y'
			);
			$this->modified = false;

			if ($option_start === null) {
				$this->optioninfo = $this->optioninfo_empty;
				$this->modified = true;
			}

			elseif (is_array($option_start)) {
				$this->optioninfo = $this->optioninfo_empty;
				$this->modified = true;

				foreach ($option_start as $key => $val)
					$this->optioninfo[$key] = $val;
			}

			else {
				$sth = $this->db->prepare_query('SELECT * FROM quizoptions WHERE optionid = #', $option_start);

				if ( ($row = $sth->fetchrow()) === false )
					$this->optioninfo = $this->optioninfo_empty;

				else
					$this->optioninfo = $row;
			}
		}

		function __get ($name) {
			return isset($this->optioninfo[$name]) ? $this->optioninfo[$name] : null;
		}

		function __set ($name, $val) {
			switch ($name) {
				case 'option':
					$val = substr($val, 0, 255);
					break;

				case 'correctans':
					if ($val != 'y' && $val != 'n')
						$val = 'y';
					break;
			}

			if (in_array($name, array('option', 'correctans')) && $this->optioninfo[$name] != $val) {
				$this->optioninfo[$name] = $val;
				$this->modified = true;
			}
		}

		function remove () {
			$this->db->prepare_query('DELETE FROM quizoptions WHERE optionid = #', $this->optionid);
		}

		function save () {
			if (! $this->modified)
				return;

			// add new option
			if ($this->optioninfo['optionid'] == 0) {
				$this->db->prepare_query(
					'INSERT INTO quizoptions SET fieldid = #, `option` = ?, correctans = ?',

					$this->optioninfo['fieldid'], $this->optioninfo['option'], $this->optioninfo['correctans']
				);

				$this->optioninfo['optionid'] = $this->db->insertid();
			}

			// saving changes to existing field
			else {
				$this->db->prepare_query(
					'UPDATE quizoptions SET `option` = ?, correctans = ? WHERE optionid = #',
					
					$this->optioninfo['option'], $this->optioninfo['correctans'], $this->optioninfo['optionid']
				);
			}

			$this->modified = false;
		}

	}


	class quizentries {
		private $db, $quizid;

		function __construct ($db, $quizid) {
			$this->db = $db;
			$this->quizid = $quizid;
		}

		function entrycount () {
			$sth = $this->db->prepare_query('SELECT COUNT(*) as thecount FROM quizentries WHERE quizid = #', $this->quizid);
			$row = $sth->fetchrow();
			return $row['thecount'];
		}

		function fetch ($entryid) {
			return new quizentry($this->db, $this->quizid, $entryid);
		}

		function fetchrange ($start, $count) {
			$result = array('totalrows' => 0, 'entries' => array());

			$sth = $this->db->prepare_query('SELECT SQL_CALC_FOUND_ROWS entryid FROM quizentries WHERE quizid = # AND status IN (?) ORDER BY score DESC, entryid LIMIT #, #', $this->quizid, array('complete', 'winner'), $start, $count);
			$result['totalrows'] = $sth->totalrows();

			while ( ($row = $sth->fetchrow()) !== false )
				$result['entries'][] = $this->fetch($row['entryid']);

			return $result;
		}

		function search ($ipaddr, $email, $userid = 0) {
			$sth = null;

			if ($userid == 0)
				$sth = $this->db->prepare_query("SELECT entryid FROM quizentries WHERE quizid = # AND email = ?", $this->quizid, $email);
			else
				$sth = $this->db->prepare_query("SELECT entryid FROM quizentries WHERE quizid = # AND (email = ? OR userid = #)", $this->quizid, $email, $userid);

			$entries = array();
			while ( ($row = $sth->fetchrow()) !== false )
				$entries[] = $this->fetch($row['entryid']);

			return $entries;
		}

		function add ($entryinfo) {
			$entry = new quizentry($this->db, $this->quizid, $entryinfo);
			$entry->save();

			return $entry;
		}

		function remove ($rmentry) {
			$this->db->prepare_query('DELETE FROM quizentries WHERE entryid = #', $rmentry->entryid);
		}

		function removeall () {
			$this->db->prepare_query('DELETE FROM quizentries WHERE quizid = #', $this->quizid);
		}
	}

	class quizentry {
		private $db, $entryinfo_empty, $entryinfo, $modified;

		function __construct ($db, $quizid, $entry_start = null) {
			$this->db = $db;

			$this->entryinfo_empty = array(
				'entryid'	=> 0,
				'quizid'	=> $quizid,
				'userid'	=> 0,
				'ipaddr'	=> 0,
				'email'		=> '',
				'status'	=> 'pending',
				'starttime'	=> time(),
				'endtime'	=> 0,
				'right'		=> 0,
				'wrong'		=> 0,
				'score'		=> 0.00,
				'data'		=> array()
			);
			$this->modified = false;

			if ($entry_start === null) {
				$this->entryinfo = $this->entryinfo_empty;
				$this->modified = true;
			}

			elseif (is_array($entry_start)) {
				$this->entryinfo = $this->entryinfo_empty;
				$this->modified = true;

				foreach ($entry_start as $key => $val)
					$this->entryinfo[$key] = $val;
			}

			else {
				$sth = $this->db->prepare_query('SELECT * FROM quizentries WHERE entryid = #', $entry_start);

				if ( ($row = $sth->fetchrow()) === false ) {
					$this->entryinfo = $this->entryinfo_empty;
				}

				else {
					$this->entryinfo = $row;
					$this->entryinfo['data'] = unserialize($this->entryinfo['data']);
				}
			}
		}

		function __get ($name) {
			if (isset($this->entryinfo[$name]))
				return $this->entryinfo[$name];
		   
			else
				return null;
		}

		function __set ($name, $val) {
			switch ($name) {
				case 'userid':
				case 'ipaddr':
				case 'starttime':
				case 'endtime':
				case 'right':
				case 'wrong':
					$val = (int)$val;
					break;

				case 'email':
					$val = substr($val, 0, 255);
					break;

				case 'status':
					if (! in_array($val, array('pending', 'complete', 'winner')))
						$val = 'pending';
					break;

				case 'score':
					$val = (float)$val;
					break;
			}

			if (in_array($name, array('userid', 'ipaddr', 'starttime', 'endtime', 'right', 'wrong', 'email', 'status', 'score', 'data')) && $this->entryinfo[$name] != $val) {
				$this->entryinfo[$name] = $val;
				$this->modified = true;
			}
		}

		function save ($force = false) {
			global $quizzes;

			if (! $this->modified && ! $force)
				return;

			// add new entry
			if ($this->entryinfo['entryid'] == 0) {
				$quiz = $quizzes->fetch($this->entryinfo['quizid']);
				$fields = $quiz->fields->fields;

				$data = array();
				if ( ($questioncount = $quiz->questioncount) == 0 ) {
					foreach ($fields as $field)
						$data[ $field->fieldid ] = null;
				}

				else {
					shuffle($fields);
					for ($i = 0; $i < $questioncount; $i++)
						$data[ $fields[$i]->fieldid ] = null;
				}

				$this->entryinfo['data'] = $data;

				$this->db->prepare_query(
					'INSERT INTO quizentries SET quizid = #, userid = #, ipaddr = #, email = ?, status = ?, starttime = #, endtime = #, `right` = #, wrong = #, score = ?, data = ?',

					$this->entryinfo['quizid'], $this->entryinfo['userid'], $this->entryinfo['ipaddr'], $this->entryinfo['email'],
					$this->entryinfo['status'], $this->entryinfo['starttime'], $this->entryinfo['endtime'], $this->entryinfo['right'],
					$this->entryinfo['wrong'], $this->entryinfo['score'], serialize($this->entryinfo['data'])
				);

				$this->entryinfo['entryid'] = $this->db->insertid();				
			}

			// saving changes to existing entry
			else {
				$this->db->prepare_query(
					'UPDATE quizentries SET status = ?, endtime = #, `right` = #, wrong = #, score = ?, data = ? WHERE entryid = #',
					
					$this->entryinfo['status'], $this->entryinfo['endtime'], $this->entryinfo['right'], $this->entryinfo['wrong'],
					$this->entryinfo['score'], serialize($this->entryinfo['data']), $this->entryinfo['entryid']
				);
			}

			$this->modified = false;
		}
	}

