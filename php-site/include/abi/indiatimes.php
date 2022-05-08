<?php
/********************************************************************************
Indiatimes contacts importer

Copyright 2007 Octazen Solutions
All Rights Reserved

You may not reprint or redistribute this code without permission from Octazen Solutions.

WWW: http://www.octazen.com
Email: support@octazen.com
V: 1.0
********************************************************************************/
include_once("abimporter.php");

/////////////////////////////////////////////////////////////////////////////////////////
//IndiatimesImporter
/////////////////////////////////////////////////////////////////////////////////////////
class IndiatimesImporter extends WebRequestor {

 	var $ACTION_REGEX = "/action=\"(http:\/\/integra.indiatimes.com\/Times\/Logon.aspx[^\"]*)\"/ims";
 	var $LOGINID_REGEX = "/<input[^>]*?name=\"?login\"?\\s+value=\"([^\"]*)\"/ims";
 	var $BETACONTACT_REGEX = "/<input\\s+[^>]*?name=\"addTo\"[^>]*?value=\"&#034;([^&]*)&#034; &lt;([^&]*)&gt;\"[^>]*>/ims";

	function fetchContacts ($loginemail, $password) {

		// Get login form
		$html = $this->httpGet("http://infinite.indiatimes.com/");
        if (preg_match($this->ACTION_REGEX,$html,$matches)==0) {
		 	$this->close();
			return _ABI_FAILED;
		}
		$action = $matches[1];
		$action = html_entity_decode($action);
		$action = str_replace("\r","",$action);
		$action = str_replace("\n","",$action);

		$parts = $this->getEmailParts($loginemail);
		$form = new HttpForm;
		$form->addField("login", $parts[0]);
		$form->addField("passwd", $password);
		$postData = $form->buildPostData();
		$html = $this->httpPost($action, $postData);
		if (strpos($html, 'Invalid User Name or Password')!=false) {
		 	$this->close();
			return abi_set_error(_ABI_AUTHENTICATION_FAILED,'Bad user name or password');
		}
		
		if (strpos($this->lastUrl, 'indiatimes.com/cgi-bin/gateway')!=false) {
		 	//Indiatimes classic
			// Next, export address book
	        if (preg_match($this->LOGINID_REGEX,$html,$matches)==0) {
			 	$this->close();
				return abi_set_error(_ABI_FAILED,'Cannot find logon id');
			}
			$logonId = $matches[1];
	
			$location = $this->makeAbsolute($this->lastUrl,"/cgi-bin/infinitemail.cgi/addressbook.csv");
			$location .= "?login=".urlencode($logonId);
			$location .= "&command=addimpexp";
			$location .= "&button=Export+to+CSV+Format";
			$html = $this->httpGet($location);
	        $res = $this->extractContactsFromCsv($html);
	        $this->close();
			return $res;
		 
		}
		else {
		 	//Indiatimes beta
		 	
            //Indiatimes mail beta
			$form = new HttpForm;

            //Search for contacts with "." in them (email!)
            $form->addField("contactSearchQuery", ".");
            $form->addField("contactLocation", "contact");
            $form->addField("actionContactSearch", "Search Contacts");
            $form->addField("to", "");
            $form->addField("cc", "");
            $form->addField("bcc", "");
            $form->addField("pendingTo", "");
            $form->addField("pendingCc", "");
            $form->addField("pendingBcc", "");
            $form->addField("subject", "");
            $form->addField("body", "");
            $form->addField("replyto", "");
            $form->addField("from", "");
            $form->addField("inreplyto", "");
            $form->addField("messageid", "");
            $form->addField("replytype", "");
            $form->addField("draftid", "");
            $form->addField("inviteReplyVerb", "");
            $form->addField("inviteReplyInst", "0");
            $form->addField("inviteReplyAllDay", "false");
			$postData = $form->buildPostArray();
			$html = $this->httpPost("/h/search?action=compose", $postData);

			$list = array();
	        preg_match_all($this->BETACONTACT_REGEX, $html, $matches, PREG_SET_ORDER);
			foreach ($matches as $val) {
	            $name = htmlentities2utf8(trim($val[1]));
	            $email = htmlentities2utf8(trim($val[2]));
	            $c = new Contact($name,$email);
	            $list[] = $c;
			}
			return $list;
		}
	}
}


?>
