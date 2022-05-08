<?php
/********************************************************************************
Mac.com (now Mobileme - me.com) contacts importer

Copyright 2008 Octazen Solutions
All Rights Reserved

You may not reprint or redistribute this code without permission from Octazen Solutions.

WWW: http://www.octazen.com
Email: support@octazen.com
********************************************************************************/
//include_once(dirname(__FILE__).'/abimporter.php');
if (!defined('__ABI')) die('Please include abi.php to use this importer!');

/////////////////////////////////////////////////////////////////////////////////////////
//MeDotComImporter
/////////////////////////////////////////////////////////////////////////////////////////
class MeDotComImporter extends WebRequestor {

	var $MAX_CONTACTS = 200;

	function login($loginemail, $password)  {

		$this->enableHttp1_1Features(true);
		//$this->useHttp1_1 = true;

	 	$login = $this->getEmailParts($loginemail);
	 	$login = $login[0];
		$form = new HttpForm;
		$form->addField("returnURL","aHR0cDovL3d3dy5tZS5jb20vd28vV2ViT2JqZWN0cy9Eb2NrU3RhdHVzLndvYS93YS90cmFtcG9saW5lP2Rlc3RpbmF0aW9uVXJsPS9tYWls");
		$form->addField("service", "DockStatus");
		$form->addField("realm", "primary-me");
		$form->addField("cancelURL", "http://www.me.com/mail");
		$form->addField("formID", "loginForm");
		$form->addField("username", $login); // email is fine too
		$form->addField("password", $password);
		$form->addField("keepLoggedIn", "Login");
		$form->addField("_authtrkcde", "{#TRKCDE#}");
		$postData = $form->buildPostData();
		$html = $this->httpPost("https://auth.apple.com/authenticate", $postData);
		if (strpos($html, 'id="error"')!==false) {
		 	$this->close();
			return abi_set_error(_ABI_AUTHENTICATION_FAILED,'Bad user name or password');
		}
		
		if (strpos($this->lastUrl,'http://www.me.com')===false) {
		 	$this->close();
			return abi_set_error(_ABI_FAILED,'Unable to login');
		}
		
		return abi_set_success();
	}

	function fetchContacts ($loginemail, $password) {

		$res = $this->login($loginemail, $password);
		if ($res!=_ABI_SUCCESS) return $res;

		// ////////////////////////////////////////////////////////////////////////////
		// Fetch JSON contacts list
		// ////////////////////////////////////////////////////////////////////////////
		$guids = array();
		$html = $this->httpPost("http://www.me.com/wo/WebObjects/Contacts.woa/wa/ScriptAction/loadContacts?lang=en", '');
		$res = oz_json_decode($html,true);
		if (is_array($res) && isset($res['crecords'])) {
			$records = null;
		 	if (isset($res['crecords'])) $records = $res['crecords'];
			if ($records==null && isset($res['records'])) $records = $res['records'];
		 	if (is_array($records)) {
			 	foreach ($records as $rec) {
			 	 	$guid = $rec['guid'];
			 	 	$type = $rec['type'];
			 	 	if ('Contact'==$type) {
			 	 	 	$guids[]=$guid;
					}
				}
			}
		}

		// ////////////////////////////////////////////////////////////////////////////
		// Fetch each contact details
		// ////////////////////////////////////////////////////////////////////////////
		$al = array();	
		
		$n = count($guids);
		for ($cc=0; $cc<$this->MAX_CONTACTS && $cc<$n; $cc++) {
			$g = $guids[$cc];
			//$form = new HttpForm;
			//$form->addField("guid", $g);
			//$postData = $form->buildPostData();
			$html = $this->httpPost("http://www.me.com/wo/WebObjects/Contacts.woa/wa/ScriptAction/refreshContactDetails?lang=en", "guid=$g");
			
			$emails = array();
			$contacts = array();
			$res = oz_json_decode($html,true);
			$records = null;
		 	if (isset($res['crecords'])) $records = $res['crecords'];
			if ($records==null && isset($res['records'])) $records = $res['records'];
			if (is_array($res) && $records!=null) {
			 	//$records = $res['crecords'];
			 	if (is_array($records)) {
				 	foreach ($records as $rec) {
				 	 	$guid = $rec['guid'];
				 	 	$type = $rec['type'];
				 	 	$value = isset($rec['associationValue']) ? $rec['associationValue'] : null;
				 	 	if ('Contact'==$type) {
							$contacts[] = $rec;
						}
						else if ('Email'==$type) {
							$emails[$guid] = $value;
						}
						//Others...
					}
				}
			}
			
			foreach ($contacts as $c) {
			 	if (isset($c['emailAddresses'])) {
					$emailList = $c['emailAddresses'];
					if (is_array($emailList)) {
					 
						$fname = isset($c['firstName']) ? $c['firstName'] : '';
						$lname = isset($c['lastName']) ? $c['lastName'] : '';
						$name = trim(abi_reduceWhitespace($fname.' '.$lname));
						
					 	foreach ($emailList as $emailGuid) {
					 	 	if (isset($emails[$emailGuid])) {
					 	 		$email = $emails[$emailGuid];
					 	 		$name2 = empty($name) ? $email : $name;
					 	 		//abi_valid_email ??
					 	 		$al[] = new Contact($name2,$email);
							}
						}
					}
				}
			}
		}
	
	 	$this->close();
		return $al;
	}
}

//For legacy support
class MacMailImporter extends MeDotComImporter {}

?>
