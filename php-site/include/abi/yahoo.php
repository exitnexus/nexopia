<?php
/********************************************************************************
Yahoo contacts importer

Copyright 2007 Octazen Solutions
All Rights Reserved

You may not reprint or redistribute this code without permission from Octazen Solutions.

WWW: http://www.octazen.com
Email: support@octazen.com
V: 1.1
********************************************************************************/
//include_once(dirname(__FILE__).'/abimporter.php');
if (!defined('__ABI')) die('Please include abi.php to use this importer!');

/////////////////////////////////////////////////////////////////////////////////////////
//YahooImporter
/////////////////////////////////////////////////////////////////////////////////////////
class YahooImporter extends WebRequestor {

 	var $CRUMB_REGEX = "/<input\\s+type=\"hidden\"\\s+name=\"\\.crumb\"[^>]*?value=\"([^\"]*)\"[^>]*>/ims";


	function fetchCsv ($loginemail, $password, $fmt='yahoo') {

		//If login email ends with ".yahoo" only, then we remove it
		$loginemail = preg_replace("/^(.*?)(\.yahoo)$/ims", '${1}', $loginemail);

	 	//Bug with yahoo. If user id part contains ".", we cannot use full email address.
	 	//We'll have to assume it's for the domain yahoo.com.
	 	$parts = $this->getEmailParts ($loginemail);
	 	$login = $parts[0];
	 	if (strpos($login,'.')>0) {
	 	 	$loginemail = $login;
		}

		//Yahoo fails at times. Retry few times
		//for ($times=0; $times<2; ++$times) {
	        $form = new HttpForm;
	        $form->addField('.tries','1');
	        $form->addField('.src','ab');
	        $form->addField('.md5','');
	        $form->addField('.hash','');
	        $form->addField('.js','');
	        $form->addField('.last','');
	        $form->addField('promo','');
	        $form->addField('.intl','us');
	        $form->addField('.bypass','');
	        $form->addField('.partner','');
	        $form->addField('.v','0');
	        $form->addField('.yplus','');
	        $form->addField('.emailCode','');
	        $form->addField('pkg','');
	        $form->addField('stepid','');
	        $form->addField('.ev','');
	        $form->addField('hasMsgr','0');
	        $form->addField('.chkP','Y');
	        $form->addField('.done','http://address.yahoo.com');
	        $form->addField('.pd','ab_ver=0');
	        $form->addField('submit','Sign In');
	        $form->addField('abc','xyz');
	        $form->addField('abc','xyz');
	        $form->addField("login",$loginemail);
	        $form->addField("passwd",$password);
			$form->addField("_authtrkcde", "{#TRKCDE#}");
	        $postData = $form->buildPostData();
	    	$html = $this->httpPost('https://login.yahoo.com/config/login?', $postData);
	    	
			if (strpos($html,'name=".secword"')>0) {
			 	$this->close();
				return abi_set_error(_ABI_CAPTCHA_RAISED,'Captcha challenge was issued. Please login through Yahoo mail manually.');
			}
	    	
			//if (strpos($html,'This ID is not yet taken')===false && strpos($html,'Invalid ID or password')===false && strpos($html,'yregertxt')===false)
			//	break;
		//}


		if (strpos($html,'This ID is not yet taken')!==false || strpos($html,'Invalid ID or password')!==false || strpos($html,'yregertxt')!==false) {
		 	$this->close();
			return abi_set_error(_ABI_AUTHENTICATION_FAILED,'Bad user name or password');
		}
		
        $form = new HttpForm;
        
        //Add crumb support
		$html2 = $this->httpGet("http://address.yahoo.com/?1&VPC=import_export");
		if (preg_match($this->CRUMB_REGEX,$html2,$matches)) {
		 	$crumb = html_entity_decode($matches[1]);
			$form->addfield('.crumb',$crumb);
		}
        
        $form->addField('VPC','import_export');
        $form->addField('A','B');
        //submit[action_export_outlook]
        if ($fmt=='outlook') {
	        $form->addField('submit[action_export_outlook]','Export Now');
		}
		else {
	        $form->addField('submit[action_export_yahoo]','Export Now');
		}
        $postData = $form->buildPostData();
    	$html = $this->httpPost('http://address.yahoo.com/index.php', $postData);

        $this->close();
		return $html;
	}

	function fetchContacts ($loginemail, $password) {

		$html = $this->fetchCsv($loginemail,$password,'yahoo');
		if (!is_string($html)) {
			return $html;
		}
        $res = $this->extractContactsFromYahooCsv($html);
		return $res;
	}
	
	
	function fetchContacts2 ($loginemail, $password) {
		$html = $this->fetchCsv($loginemail,$password,'outlook');
		if (!is_string($html)) {
			return $res;
		}
		$ce = new CsvExtractor;
		return $ce->extract($html);
	}	
}

?>
