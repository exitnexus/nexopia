<?php
/***************************************************************************
 *                              smtp.php
 *                       -------------------
 *   begin                : Wed May 09 2001
 *   copyright            : (C) 2001 The phpBB Group
 *   email                : support@phpbb.com
 *
 *   $Id: smtp.php,v 1.16.2.9 2003/07/18 16:34:01 acydburn Exp $
 *
 ***************************************************************************/

/***************************************************************************
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/

define('SMTP_INCLUDED', 1);

//
// This function has been modified as provided
// by SirSir to allow multiline responses when
// using SMTP Extensions
//
function server_parse($socket, $response){
	global $msgs;
	do{
		if(!($server_response = fgets($socket, 256))){
			$msgs->addMsg("Couldn't get mail server response codes");
			return false;
		}
	}while(substr($server_response, 3, 1) != ' ');

	if(!(substr($server_response, 0, 3) == $response)){
		$msgs->addMsg("Ran into problems sending Mail. Response: $server_response");
		return false;
	}
	return true;
}

// Replacement or substitute for PHP's mail command
function smtpmail($mail_to, $subject, $message, $headers = ''){
	global $config, $msgs;

	// Fix any bare linefeeds in the message to make it RFC821 Compliant.
	$message = preg_replace("#(?<!\r)\n#si", "\r\n", $message);

	if($headers != '')	{
		if(is_array($headers)){
			if(sizeof($headers) > 1){
				$headers = join("\n", $headers);
			}else{
				$headers = $headers[0];
			}
		}
		$headers = chop($headers);

		// Make sure there are no bare linefeeds in the headers
		$headers = preg_replace('#(?<!\r)\n#si', "\r\n", $headers);

		// Ok this is rather confusing all things considered,
		// but we have to grab bcc and cc headers and treat them differently
		// Something we really didn't take into consideration originally
		$header_array = explode("\r\n", $headers);
		@reset($header_array);

		$headers = '';
		while(list(, $header) = each($header_array)){
			if(preg_match('#^cc:#si', $header)){
				$cc = preg_replace('#^cc:(.*)#si', '\1', $header);
			}elseif(preg_match('#^bcc:#si', $header)){
				$bcc = preg_replace('#^bcc:(.*)#si', '\1', $header);
				$header = '';
			}
			$headers .= ($header != '') ? $header . "\r\n" : '';
		}

		$headers = chop($headers);
		$cc = "";
		$bcc = "";
		$cc = explode(', ', $cc);
		$bcc = explode(', ', $bcc);
	}

	if(trim($subject) == ''){
		$msgs->addMsg("No email Subject specified");
		return false;
	}

	if(trim($message) == ''){
		$msgs->addMSg("Email message was blank");
		return false;
	}

	// Ok we have error checked as much as we can to this point let's get on
	// it already.
	if( !( $socket = fsockopen($config['smtp_host'], 25, $errno, $errstr, 20) ) ){
		$msgs->addMsg("Could not connect to smtp host : $errno : $errstr");
		return false;
	}

	// Wait for reply
	if(!server_parse($socket, "220")){
		fclose($socket);
		return false;
	}


	// Do we want to use AUTH?, send RFC2554 EHLO, else send RFC821 HELO
	// This improved as provided by SirSir to accomodate
	if( !empty($config['smtp_username']) && !empty($config['smtp_password']) )	{
		fputs($socket, "EHLO " . $config['smtp_host'] . "\r\n");
		if(!server_parse($socket, "250")){
			fclose($socket);
			return false;
		}

		fputs($socket, "AUTH LOGIN\r\n");
		if(!server_parse($socket, "334")){
			fclose($socket);
			return false;
		}

		fputs($socket, base64_encode($config['smtp_username']) . "\r\n");
		if(!server_parse($socket, "334")){
			fclose($socket);
			return false;
		}

		fputs($socket, base64_encode($config['smtp_password']) . "\r\n");
		if(!server_parse($socket, "235")){
			fclose($socket);
			return false;
		}
	}else{
		fputs($socket, "HELO " . $config['smtp_host'] . "\r\n");
		if(!server_parse($socket, "250")){
			fclose($socket);
			return false;
		}
	}

	// From this point onward most server response codes should be 250
	// Specify who the mail is from....
	fputs($socket, "MAIL FROM: <" . $config['email'] . ">\r\n");
	if(!server_parse($socket, "250")){
		fclose($socket);
		return false;
	}

	// Specify each user to send to and build to header.
	$to_header = '';

	// Add an additional bit of error checking to the To field.
	$mail_to = (trim($mail_to) == '') ? 'Undisclosed-recipients:;' : trim($mail_to);
	if (preg_match('#[^ ]+\@[^ ]+#', $mail_to))	{
		fputs($socket, "RCPT TO: <$mail_to>\r\n");
		if(!server_parse($socket, "250")){
			fclose($socket);
			return false;
		}
	}

	// Ok now do the CC and BCC fields...
	@reset($bcc);
	while(list(, $bcc_address) = each($bcc)){
		// Add an additional bit of error checking to bcc header...
		$bcc_address = trim($bcc_address);
		if (preg_match('#[^ ]+\@[^ ]+#', $bcc_address))	{
			fputs($socket, "RCPT TO: <$bcc_address>\r\n");
			if(!server_parse($socket, "250")){
				fclose($socket);
				return false;
			}
		}
	}

	@reset($cc);
	while(list(, $cc_address) = each($cc))	{
		// Add an additional bit of error checking to cc header
		$cc_address = trim($cc_address);
		if (preg_match('#[^ ]+\@[^ ]+#', $cc_address))		{
			fputs($socket, "RCPT TO: <$cc_address>\r\n");
			if(!server_parse($socket, "250")){
				fclose($socket);
				return false;
			}
		}
	}

	// Ok now we tell the server we are ready to start sending data
	fputs($socket, "DATA\r\n");

	// This is the last response code we look for until the end of the message.
	if(!server_parse($socket, "354")){
		fclose($socket);
		return false;
	}

	// Send the Subject Line...
	fputs($socket, "Subject: $subject\r\n");

	// Now the To Header.
	fputs($socket, "To: $mail_to\r\n");

	// Now any custom headers....
	fputs($socket, "$headers\r\n\r\n");

	// Ok now we are ready for the message...
	fputs($socket, "$message\r\n");

	// Ok the all the ingredients are mixed in let's cook this puppy...
	fputs($socket, ".\r\n");
	if(!server_parse($socket, "250")){
		fclose($socket);
		return false;
	}

	// Now tell the server we are done and close the socket...
	fputs($socket, "QUIT\r\n");
	fclose($socket);

	return true;
}

