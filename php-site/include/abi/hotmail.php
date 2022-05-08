<?php
/********************************************************************************
Hotmail & MSN contacts importer

Copyright 2007 Octazen Solutions
All Rights Reserved

You may not reprint or redistribute this code without permission from Octazen Solutions.

WWW: http://www.octazen.com
Email: support@octazen.com
V: 1.3
********************************************************************************/
include_once("abimporter.php");

/////////////////////////////////////////////////////////////////////////////////////////
//HotmailImporter
/////////////////////////////////////////////////////////////////////////////////////////
class HotmailImporter extends WebRequestor {
 
	var $LOGINFORM_REGEX = "/<form name=\"f1\" [^>]*action=\"([^\"]*)\"[^>]*>(.*)<\/form>/im";
	var $HIDDENFIELD_REGEX = "/<input type=\"hidden\"[^>]* name=\"([^\"]*)\"[^>]*value=\"([^\"]*)\"[^>]*>/im";
	var $JSREDIRECT_REGEX = "/window.location.replace\\(\"([^\"]*)\"\\)/i";
	var $REDIRECT_REGEX = "/url='?([^\"]*)'?/i";
	var $JSREDIRECT2_REGEX = "/.*window.location.replace\\(\"http://www.hotmail.msn.com/cgi-bin/sbox\\?([^\"]*)\"\\).*/i";
	var $CONTACT_REGEX = "/<option value=\"([^\"]*)\"[^>]*>(.*?)&lt;[^<]*<\/option>/i";
	//New hotmail style. 
	var $CONTACT_REGEX2 = "/<td[^>]*?onclick=\"AddToList\('([^']*?)'[^>]*?>.*?<font[^>]*?class=\"DD\"[^>]*>([^<]*).*?<\/td>/i";
	//<td onmouseover="DoHL()" onmouseout="DoLL()" onclick="AddToList('achoo_sneeze@hotmail.com');"><font class="DD">achoo_sneeze</font> &lt;achoo_sneeze@hotmail...&gt;</td>

    var $LIVEHOST_REGEX = "/(http:\/\/.*?mail\\.live\\.com\/mail\/).*/ims";
    var $LIVECONTACT_REGEX = "/<tr>.*?<td class=\"dContactPickerBodyNameCol\">.*?&#x200f;\\s*(.*?)\\s*&#x200f;.*?<\/td>\\s*<td class=\"dContactPickerBodyEmailCol\">\\s*([^<]*?)\\s*<\/td>.*?<\/tr>/ims";
    
    function countChars ($str, $chr) {
		$n = strlen($str);
		$c = 0;
		for ($i=0; $i<$n; $i++) {
			if ($str[$i]==$chr) $c++;
		}
		return $c++;
	}

	function login ($html, $login, $password) {
	 
        if (preg_match($this->LOGINFORM_REGEX,$html,$matches)==0) {
		 	$this->close();
			return abi_set_error(_ABI_FAILED,'Cannot find login form');
		}
        $loginaction = $matches[1];
        $loginform = $matches[2];

        //Extract all hidden fields
        $form = new HttpForm;
        preg_match_all($this->HIDDENFIELD_REGEX, $loginform, $matches, PREG_SET_ORDER);
		foreach ($matches as $val) {
            $form->addField(html_entity_decode($val[1]),html_entity_decode($val[2]));
		}
        //1 = remember, 2= remember email, 3=do not remember
        $form->addField("LoginOptions","3");
        $form->addField("SI","    Sign in    ");
        $form->addField("login",$login);
        $form->addField("passwd",$password);
        $postData = $form->buildPostData();
    	return $this->httpPost($loginaction, $postData);
	}

	function fetchContacts ($login, $password) {

		$html = $this->httpPost("https://login.live.com/ppsecure/post.srf?id=2&svc=mail");
		$html = $this->Login($html, $login, $password);
		if ($html==_ABI_FAILED) {
		 	$this->close();
			return _ABI_FAILED;
		}
		if (strpos($html, 'The e-mail address or password is incorrect')>0 ||
			strpos($html, 'The password is incorrect')>0 ||
			strpos($html, 'Please type your e-mail address in the following format')>0 ||
			strpos($html, 'The .NET Passport or Windows Live ID you are signed into is not supported')>0 ||
			strpos($html, 'srf_fError=1')>0) {
		 	$this->close();
			return abi_set_error(_ABI_AUTHENTICATION_FAILED,'Bad user name or password');
		}

//	DUMP("c:/tmp/live0.html",$html);

		/////////////////////////////////////////////////////
		//HANDLE REDIRECT TO MAIN PAGE (MBOX page)
		/////////////////////////////////////////////////////
		//@hotmail.com uses javascript redirect
		$location = null;
		if (preg_match($this->JSREDIRECT_REGEX,$html,$matches)==0) {
			//@msn.com uses refresh redirect
			if (preg_match($this->REDIRECT_REGEX,$html,$matches)==0) {
				$this->close();
				return abi_set_error(_ABI_FAILED,'Cannot find redirect instruction');
			}
			$location = $matches[1];
			$html = $this->httpGet($location);
		}
		else {
			$location = $matches[1];
			$html = $this->httpGet($location);
		}
		
//		DUMP("c:/tmp/live1.html",$html);

		//At this point, we can either be in a html sending a redirect to the mailbox page, or
		//an intermediary page (newsletter update, etc). If it's intermediary page, then we
		//won't be able to know if this is a live or non-live account.
		//NOTE! Hotmail sometimes redirect to "http://newsletter.....?ru=theactualurl".
		$newUrl = $this->lastUrl;
		if (preg_match($this->LIVEHOST_REGEX,$newUrl,$matches)==0) {
			if (preg_match($this->JSREDIRECT_REGEX,$html,$matches)!=0 || 
				preg_match($this->REDIRECT_REGEX,$html,$matches)!=0) {
				$newUrl = $matches[1];
			}
		}
		$isLive = false;
		//Build live url if need to
		if (preg_match($this->LIVEHOST_REGEX,$newUrl,$matches)!=0) {
			//Is hotmail live!
			$isLive = true;
			//$location = $matches[1]."PrintShell.aspx?type=contact";
			//$location = '/mail/GetContacts.aspx';
			//$location = $this->makeAbsolute($newUrl,'/mail/GetContacts.aspx');
		}
		else {
		}

//		echo "NOW GOING TO $location<br>";

		if ($isLive) {
			$html = $this->httpGet('http://mail.live.com/mail/ContactPickerLight.aspx?n='.rand(0,20000));
			$al = array();	
	        preg_match_all($this->LIVECONTACT_REGEX, $html, $matches, PREG_SET_ORDER);
			foreach ($matches as $val) {
	            $name = htmlentities2utf8(trim($val[1]));
	            $email = htmlentities2utf8(trim($val[2]));

	            //if (abi_valid_email($email)) {
				if (abi_valid_email($email)) {
					$contact = new Contact($name,$email);
					$al[] = $contact;
				}
			}
			return $al;

		 
			/* CANNOT BE USED DUE TO HOTMAIL SERVER BUGS (HOTMAIL TRUNCATING RETURNED CSV))
			//Fetch from hotmail live (Hotmail has bugs with gzip compression!)
			$this->supportGzip = false;
			$location = 'http://mail.live.com/mail/GetContacts.aspx';
			$html = $this->httpGet($location);
			$this->supportGzip = true;
		 	$this->close();
		 	//Unfortunately, our code does not throw an exception if HTTP status is not 2xx.
		 	//Legacy code did not have statusCode (which is now available). 
			//Hotmail returns a 500 server error if there are no contacts to export (!)
			//This affects the hotmail gzip bug that we encounter in the above section.
		 	//What we do instead is look for the error page content.
		 	if (strpos($html,'<TITLE>Error</TITLE>')>0) {
				$html = '';
			}

		 	$lines = explode("\n",$html,1);
		 	$c1 = $this->countChars($lines[0],';');
		 	$c2 = $this->countChars($lines[0],',');
		 	if ($c1 > $c2) {
			 	return $this->extractContactsFromCsv($html,';');
			}
			else {
			 	return $this->extractContactsFromCsv($html,',');
			}
			*/
		 	
		 	
		
		}
		else {
			//Go to address book page for new mail composition
	        //$location = $this->makeAbsolute($this->lastUrl, "/cgi-bin/AddressPicker?&Context=InsertAddress&_HMaction=Edit");
	        //$location = 'http://www.hotmail.msn.com/cgi-bin/AddressPicker?Context=InsertAddress&_HMaction=Edit&qF=to';
	        $location = 'http://www.hotmail.msn.com/cgi-bin/AddressPicker?Context=InsertAddress&_HMaction=Edit&qF=to';
	        $html = $this->httpGet($location);
	
	        /////////////////////////////////////////////////////
	        //EXTRACT!
	        /////////////////////////////////////////////////////
	        //get rid of html &nbsp; in the contacts table
			$al = array();	
	        preg_match_all($this->CONTACT_REGEX, $html, $matches, PREG_SET_ORDER);
			foreach ($matches as $val) {
	            $email = htmlentities2utf8(trim($val[1]));
	            $name = htmlentities2utf8(trim($val[2]));
				$contact = new Contact($name,$email);
				$al[] = $contact;
			}
			//New hotmail style 20061205
	        preg_match_all($this->CONTACT_REGEX2, $html, $matches, PREG_SET_ORDER);
			foreach ($matches as $val) {
	            $email = htmlentities2utf8(trim($val[1]));
	            $name = htmlentities2utf8(htmlentities2utf8($val[2]));
				$contact = new Contact($name,$email);
				$al[] = $contact;
			}
		 	$this->close();
			return $al;
		}
	

//		DUMP("c:/tmp/live2.html",$html);

		//Seems to require this close function in hotmail
		//$this->close();
/*		
		$html = $this->httpGet($location);




		//If returned url does not points to mailbox, etc, then follow the link..
		if (strpos($html,".hotmail.msn.com")==false) {

			
		}
		            //Hotmail live will cause us to redirect to the new live site
            match = JSREDIRECT_REGEX.Match(html);
            //@msn.com uses refresh redirect
            if (!match.Success)
                match = REDIRECT_REGEX.Match(html);
            if (match.Success)
            {
                location = match.Groups[1].Value;
                match = LIVEHOST_REGEX.Match(location);
                if (match.Success) {
                    location = match.Groups[1].Value;
                    location = location+"PrintShell.aspx?type=contact";
                    html = httpGet(location);
                }
            }
*/	


		
        
	}
}

?>
