<?php
/********************************************************************************
Copyright 2007 Octazen Solutions
All Rights Reserved

You may not reprint or redistribute this code without permission from Octazen Solutions.

WWW: http://www.octazen.com
Email: support@octazen.com
Version: 1.0.0
Date: 01 Jan 2008
********************************************************************************/

//--------MAPPING START--------
global $_ABI_FIELDMAPS;
$_ABI_FIELDMAPS = array();
//#==========================================================================
//[OutlookEnglish]
//#==========================================================================
//#first name=FirstName
//#firstname=FirstName
//#middle name=MiddleName
//#middlename=MiddleName
//#last name=LastName
//#lastname=LastName
//#nick=NickName
//#nickname=NickName
//#name=DisplayName
//#email=EmailAddress
//#e-mail=EmailAddress
//#e-mail address=EmailAddress
//#e-mail 2 address=Email2Address
//#e-mail 3 address=Email3Address
//########################
//#WAB/OE6 english
//########################
$map["First Name"]="FirstName";
$map["Last Name"]="LastName";
$map["Middle Name"]="MiddleName";
$map["Name"]="DisplayName";
$map["Nickname"]="NickName";
$map["E-mail Address"]="EmailAddress";
$map["Home Street"]="HomeStreet";
$map["Home City"]="HomeCity";
$map["Home Postal Code"]="HomePostalCode";
$map["Home State"]="HomeState";
$map["Home Country/Region"]="HomeCountry";
$map["Home Phone"]="HomePhone";
$map["Home Fax"]="HomeFax";
$map["Mobile Phone"]="MobilePhone";
$map["Personal Web Page"]="PersonalWebSite";
$map["Business Street"]="BusinessStreet";
$map["Business City"]="BusinessCity";
$map["Business Postal Code"]="BusinessPostalCode";
$map["Business State"]="BusinessState";
$map["Business Country/Region"]="BusinessCountry";
$map["Business Web Page"]="BusinessWebSite";
$map["Business Phone"]="BusinessPhone";
$map["Business Fax"]="BusinessFax";
$map["Pager"]="Pager";
$map["Company"]="Company";
$map["Job Title"]="JobTitle";
$map["Department"]="Department";
$map["Office Location"]="OfficeLocation";
$map["Notes"]="Notes";
//########################
//#Outlook 2003 english
//########################
$map["Title"]="Title";
$map["First Name"]="FirstName";
$map["Middle Name"]="MiddleName";
$map["Last Name"]="LastName";
$map["Suffix"]="Suffix";
$map["Company"]="Company";
$map["Department"]="Department";
$map["Job Title"]="JobTitle";
$map["Business Street"]="BusinessStreet";
$map["Business Street 2"]="BusinessStreet2";
$map["Business Street 3"]="BusinessStreet3";
$map["Business City"]="BusinessCity";
$map["Business State"]="BusinessState";
$map["Business Postal Code"]="BusinessPostalCode";
$map["Business Country"]="BusinessCountry";
$map["Home Street"]="HomeStreet";
$map["Home Street 2"]="HomeStreet2";
$map["Home Street 3"]="HomeStreet3";
$map["Home City"]="HomeCity";
$map["Home State"]="HomeState";
$map["Home Postal Code"]="HomePostalCode";
$map["Home Country"]="HomeCountry";
$map["Other Street"]="OtherStreet";
$map["Other Street 2"]="OtherStreet2";
$map["Other Street 3"]="OtherStreet3";
$map["Other City"]="OtherCity";
$map["Other State"]="OtherState";
$map["Other Postal Code"]="OtherPostalCode";
$map["Other Country"]="OtherCountry";
$map["Assistant's Phone"]="AssistantPhone";
$map["Business Fax"]="BusinessFax";
$map["Business Phone"]="BusinessPhone";
$map["Business Phone 2"]="BusinessPhone2";
$map["Callback"]="Callback";
$map["Car Phone"]="CarPhone";
$map["Company Main Phone"]="CompanyMainPhone";
$map["Home Fax"]="HomeFax";
$map["Home Phone"]="HomePhone";
$map["Home Phone 2"]="HomePhone2";
$map["ISDN"]="ISDN";
$map["Mobile Phone"]="MobilePhone";
$map["Other Fax"]="OtherFax";
$map["Other Phone"]="OtherPhone";
$map["Pager"]="Pager";
$map["Primary Phone"]="PrimaryPhone";
$map["Radio Phone"]="RadioPhone";
$map["TTY/TDD Phone"]="TTYTDDPhone";
$map["Telex"]="Telex";
$map["Account"]="Account";
$map["Anniversary"]="Anniversary";
$map["Assistant's Name"]="AssistantName";
$map["Billing Information"]="BillingInformation";
$map["Birthday"]="Birthday";
$map["Business Address PO Box"]="BusinessAddressPOBox";
$map["Categories"]="Categories";
$map["Children"]="Children";
$map["Directory Server"]="DirectoryServer";
$map["E-mail Address"]="EmailAddress";
$map["E-mail Type"]="EmailType";
$map["E-mail Display Name"]="EmailDisplayName";
$map["E-mail 2 Address"]="Email2Address";
$map["E-mail 2 Type"]="Email2Type";
$map["E-mail 2 Display Name"]="Email2DisplayName";
$map["E-mail 3 Address"]="Email3Address";
$map["E-mail 3 Type"]="Email3Type";
$map["E-mail 3 Display Name"]="Email3DisplayName";
$map["Gender"]="Gender";
$map["Government ID Number"]="GovernmentIDNumber";
$map["Hobby"]="Hobby";
$map["Home Address PO Box"]="HomeAddressPOBox";
$map["Initials"]="Initials";
$map["Internet Free Busy"]="InternetFreeBusy";
$map["Keywords"]="Keywords";
$map["Language"]="Language";
$map["Location"]="Location";
$map["Manager's Name"]="ManagerName";
$map["Mileage"]="Mileage";
$map["Notes"]="Notes";
$map["Office Location"]="OfficeLocation";
$map["Organizational ID Number"]="OrganizationalIDNumber";
$map["Other Address PO Box"]="OtherAddressPOBox";
$map["Priority"]="Priority";
$map["Private"]="Private";
$map["Profession"]="Profession";
$map["Referred By"]="ReferredBy";
$map["Sensitivity"]="Sensitivity";
$map["Spouse"]="Spouse";
$map["User 1"]="User1";
$map["User 2"]="User2";
$map["User 3"]="User3";
$map["User 4"]="User4";
$map["Web Page"]="WebPage";
//#==========================================================================
$_ABI_FIELDMAPS[]=$map;
$map=array();
//[OutlookGerman]
//#==========================================================================
//#vorname=FirstName
//#2. vorname=MiddleName
//#weitere vornamen=MiddleName
//#nachname=LastName
//#rufname=NickName
//#e-mail-adresse=EmailAddress
//#e-mail adresse=EmailAddress
//#e-mail 2: adresse=Email2Address
//#e-mail 3: adresse=Email3Address
//########################
//#Yahoo german outlook
//########################
$map["Titel"]="Title";
$map["Vorname"]="FirstName";
$map["2. Vorname"]="MiddleName";
$map["Nachname"]="LastName";
$map["Suffix"]="Suffix";
$map["Firma"]="Company";
$map["Abteilung"]="Department";
$map["Position"]="JobTitle";
$map["Stra\xC3\x9Fe (gesch\xC3\xA4ftlich)"]="BusinessStreet";
$map["Stra\xC3\x9Fe 2 (gesch\xC3\xA4ftlich)"]="BusinessStreet2";
$map["Stra\xC3\x9Fe 3 (gesch\xC3\xA4ftlich)"]="BusinessStreet3";
$map["Ort (gesch\xC3\xA4ftlich)"]="BusinessCity";
$map["Bundesland (gesch\xC3\xA4ftlich)"]="BusinessState";
$map["PLZ (gesch\xC3\xA4ftlich)"]="BusinessPostalCode";
$map["Land (gesch\xC3\xA4ftlich)"]="BusinessCountry";
$map["Stra\xC3\x9Fe (privat)"]="HomeStreet";
$map["Stra\xC3\x9Fe 2 (privat)"]="HomeStreet2";
$map["Stra\xC3\x9Fe 3 (privat)"]="HomeStreet3";
$map["Ort (privat)"]="HomeCity";
$map["Bundesland (privat)"]="HomeState";
$map["PLZ (privat)"]="HomePostalCode";
$map["Land (privat)"]="HomeCountry";
$map["Stra\xC3\x9Fe (Sonstiges)"]="OtherStreet";
$map["Stra\xC3\x9Fe 2 (Sonstiges)"]="OtherStreet2";
$map["Stra\xC3\x9Fe 3 (Sonstiges)"]="OtherStreet3";
$map["Ort (Sonstiges)"]="OtherCity";
$map["Bundesland (Sonstiges)"]="OtherState";
$map["PLZ (Sonstiges)"]="OtherPostalCode";
$map["Land (Sonstiges)"]="OtherCountry";
$map["Telefon Assistent(in)"]="AssistantPhone";
$map["Fax (gesch\xC3\xA4ftlich)"]="BusinessFax";
$map["Telefon (gesch\xC3\xA4ftlich)"]="BusinessPhone";
$map["Telefon 2 (gesch\xC3\xA4ftlich)"]="BusinessPhone2";
//#Rückruf
$map["Autotelefon"]="MobilePhone";
//#Firma Zentrale
$map["Fax (privat)"]="HomeFax";
$map["Telefon (privat)"]="HomePhone";
$map["Telefon 2 (privat)"]="HomePhone2";
$map["ISDN"]="ISDN";
$map["Handy"]="MobilePhone";
$map["Fax (Sonstiges)"]="OtherFax";
$map["Telefon (Sonstiges)"]="OtherPhone";
$map["Pager"]="Pager";
//#Haupttelefonnummer   -> main phone? main company phone?
$map["Funktelefon"]="RadioPhone";
$map["Texttelefon"]="TTYTDDPhone";
$map["Telex"]="Telex";
$map["Account"]="Account";
$map["Jahrestag"]="Anniversary";
$map["Name Assistent(in)"]="AssistantName";
//#Rechnungsdaten  -> invoice date?
$map["Geburtstag"]="Birthday";
$map["Kategorien"]="Categories";
$map["Kinder"]="Children";
$map["E-Mail-Adresse"]="EmailAddress";
$map["Anzeigename f\xC3\xBCr E-Mail"]="EmailDisplayName";
$map["E-Mail-Adresse 2"]="Email2Address";
$map["Anzeigename f\xC3\xBCr E-Mail 2"]="Email2DisplayName";
$map["E-Mail-Adresse 3"]="Email3Address";
$map["Anzeigename f\xC3\xBCr E-Mail 3"]="Email3DisplayName";
$map["Geschlecht"]="Gender";
//#Personalnummer Behörde  -> personal number authority?
$map["Hobby"]="Hobby";
$map["Initialen"]="Initials";
//#Not sure
//#Stichwörter=ReferredBy
$map["Sprache"]="Language";
$map["Ort"]="Location";
$map["Kilometerleistung"]="Mileage";
$map["Anmerkungen"]="Notes";
$map["B\xC3\xBCro"]="OfficeLocation";
//#Not sure about this
$map["Personalnummer"]="GovernmentIDNumber";
//#Not sure about this
//#Postfach=BusinessAddressPOBox
$map["Privat"]="Private";
$map["Beruf"]="Profession";
//#Not sure
//#Empfehlung durch
$map["Partner(in)"]="Spouse";
$map["Nutzer 1"]="User1";
$map["Nutzer 2"]="User2";
$map["Nutzer 3"]="User3";
$map["Nutzer 4"]="User4";
$map["Webseite"]="Website";
//########################
//#Outlook 2003 German
//########################
$map["Anrede"]="Title";
$map["Vorname"]="FirstName";
$map["Weitere Vornamen"]="MiddleName";
$map["Nachname"]="LastName";
$map["Suffix"]="Suffix";
$map["Firma"]="Company";
$map["Abteilung"]="Department";
$map["Position"]="JobTitle";
$map["Stra\xC3\x9Fe gesch\xC3\xA4ftlich"]="BusinessStreet";
$map["Stra\xC3\x9Fe gesch\xC3\xA4ftlich 2"]="BusinessStreet2";
$map["Stra\xC3\x9Fe gesch\xC3\xA4ftlich 3"]="BusinessStreet3";
$map["Ort gesch\xC3\xA4ftlich"]="BusinessCity";
$map["Region gesch\xC3\xA4ftlich"]="BusinessState";
$map["Postleitzahl gesch\xC3\xA4ftlich"]="BusinessPostalCode";
$map["Land gesch\xC3\xA4ftlich"]="BusinessCountry";
$map["Stra\xC3\x9Fe privat"]="HomeStreet";
$map["Stra\xC3\x9Fe privat 2"]="HomeStreet2";
$map["Stra\xC3\x9Fe privat 3"]="HomeStreet3";
$map["Ort privat"]="HomeCity";
$map["Region privat"]="HomeState";
$map["Postleitzahl privat"]="HomePostalCode";
$map["Land privat"]="HomeCountry";
$map["Weitere Stra\xC3\x9Fe"]="OtherStreet";
$map["Weitere Stra\xC3\x9Fe 2"]="OtherStreet2";
$map["Weitere Stra\xC3\x9Fe 3"]="OtherStreet3";
$map["Weiterer Ort"]="OtherCity";
$map["Weitere Region"]="OtherState";
$map["Weitere Postleitzahl"]="OtherPostalCode";
$map["Weiteres Land"]="OtherCountry";
$map["Telefon Assistent"]="AssistantPhone";
$map["Fax gesch\xC3\xA4ftlich"]="BusinessFax";
$map["Telefon gesch\xC3\xA4ftlich"]="BusinessPhone";
$map["Telefon gesch\xC3\xA4ftlich 2"]="BusinessPhone2";
$map["R\xC3\xBCckmeldung"]="Callback";
$map["Autotelefon"]="CarPhone";
$map["Telefon Firma"]="CompanyMainPhone";
$map["Fax privat"]="HomeFax";
$map["Telefon privat"]="HomePhone";
$map["Telefon privat 2"]="HomePhone2";
$map["ISDN"]="ISDN";
$map["Mobiltelefon"]="MobilePhone";
$map["Weiteres Fax"]="OtherFax";
$map["Weiteres Telefon"]="OtherPhone";
$map["Pager"]="Pager";
$map["Haupttelefon"]="PrimaryPhone";
$map["Mobiltelefon 2"]="RadioPhone";
$map["Telefon f\xC3\xBCr H\xC3\xB6rbehinderte"]="TTYTDDPhone";
$map["Telex"]="Telex";
$map["Konto"]="Account";
$map["Jahrestag"]="Anniversary";
$map["Name Assistent"]="AssistantName";
$map["Abrechnungsinformation"]="BillingInformation";
$map["Geburtstag"]="Birthday";
$map["Postfach gesch\xC3\xA4ftlich"]="BusinessAddressPOBox";
$map["Kategorien"]="Categories";
$map["Kinder"]="Children";
$map["Verzeichnisserver"]="DirectoryServer";
$map["E-Mail-Adresse"]="EmailAddress";
$map["E-Mail-Typ"]="EmailType";
$map["E-Mail: Angezeigter Name"]="EmailDisplayName";
$map["E-Mail 2: Adresse"]="Email2Address";
$map["E-Mail 2: Typ"]="Email2Type";
$map["E-Mail 2: Angezeigter Name"]="Email2DisplayName";
$map["E-Mail 3: Adresse"]="Email3Address";
$map["E-Mail 3: Typ"]="Email3Type";
$map["E-Mail 3: Angezeigter Name"]="Email3DisplayName";
$map["Keine Angabe"]="Gender";
$map["Regierungs-Nr."]="GovernmentIDNumber";
$map["Hobby"]="Hobby";
$map["Postfach privat"]="HomeAddressPOBox";
$map["Initialen"]="Initials";
//#=InternetFreeBusy
//#=Keywords
$map["Sprache"]="Language";
$map["Ort"]="Location";
$map["Name des/der Vorgesetzten"]="ManagerName";
$map["Reisekilometer"]="Mileage";
$map["Notizen"]="Notes";
$map["B\xC3\xBCro"]="OfficeLocation";
$map["Organisations-Nr."]="OrganizationalIDNumber";
$map["Weiteres Postfach"]="OtherAddressPOBox";
$map["Priorit\xC3\xA4t"]="Priority";
$map["Privat"]="Private";
$map["Beruf"]="Profession";
$map["Stichw\xC3\xB6rter"]="ReferredBy";
//#=Sensitivity
$map["Partner"]="Spouse";
$map["Benutzer 1"]="User1";
$map["Benutzer 2"]="User2";
$map["Benutzer 3"]="User3";
$map["Benutzer 4"]="User4";
$map["Webseite"]="WebPage";
$_ABI_FIELDMAPS[]=$map;
//--------MAPPING END--------



//Definitions of available fields

define('Field_FirstName',"FirstName");
define('Field_MiddleName',"MiddleName");
define('Field_LastName',"LastName");
define('Field_DisplayName',"DisplayName");
define('Field_NickName',"NickName");
define('Field_Title',"Title");
define('Field_Suffix',"Suffix");
define('Field_Company',"Company");
define('Field_Department',"Department");
define('Field_JobTitle',"JobTitle");
define('Field_BusinessStreet',"BusinessStreet");
define('Field_BusinessStreet2',"BusinessStreet2");
define('Field_BusinessStreet3',"BusinessStreet3");
define('Field_BusinessCity',"BusinessCity");
define('Field_BusinessState',"BusinessState");
define('Field_BusinessPostalCode',"BusinessPostalCode");
define('Field_BusinessCountry',"BusinessCountry");
define('Field_HomeStreet',"HomeStreet");
define('Field_HomeStreet2',"HomeStreet2");
define('Field_HomeStreet3',"HomeStreet3");
define('Field_HomeCity',"HomeCity");
define('Field_HomePostalCode',"HomePostalCode");
define('Field_HomeCountry',"HomeCountry");
define('Field_OtherStreet',"OtherStreet");
define('Field_OtherStreet2',"OtherStreet2");
define('Field_OtherStreet3',"OtherStreet3");
define('Field_OtherCity',"OtherCity");
define('Field_OtherState',"OtherState");
define('Field_OtherPostalCode',"OtherPostalCode");
define('Field_OtherCountry',"OtherCountry");
define('Field_AssistantPhone',"AssistantPhone");
define('Field_BusinessFax',"BusinessFax");
define('Field_BusinessPhone',"BusinessPhone");
define('Field_CarPhone',"CarPhone");
define('Field_CompanyMainPhone',"CompanyMainPhone");
define('Field_HomeFax',"HomeFax");
define('Field_HomePhone',"HomePhone");
define('Field_HomePhone2',"HomePhone2");
define('Field_ISDN',"ISDN");
define('Field_MobilePhone',"MobilePhone");
define('Field_OtherFax',"OtherFax");
define('Field_OtherPhone',"OtherPhone");
define('Field_Pager',"Pager");
define('Field_PrimaryPhone',"PrimaryPhone");
define('Field_RadioPhone',"RadioPhone");
define('Field_TTYTDDPhone',"TTYTDDPhone");
define('Field_Telex',"Telex");
define('Field_Account',"Account");
define('Field_Anniversary',"Anniversary");
define('Field_AssistantName',"AssistantName");
define('Field_BillingInformation',"BillingInformation");
define('Field_Birthday',"Birthday");
define('Field_BusinessAddressPOBox',"BusinessAddressPOBox");
define('Field_Categories',"Categories");
define('Field_Children',"Children");
define('Field_DirectoryServer',"DirectoryServer");
define('Field_EmailAddress',"EmailAddress");
define('Field_EmailType',"EmailType");
define('Field_EmailDisplayName',"EmailDisplayName");
define('Field_Email2Address',"Email2Address");
define('Field_Email2Type',"Email2Type");
define('Field_Email2DisplayName',"Email2DisplayName");
define('Field_Email3Address',"Email3Address");
define('Field_Email3Type',"Email3Type");
define('Field_Email3DisplayName',"Email3DisplayName");
define('Field_Gender',"Gender");
define('Field_GovernmentIDNumber',"GovernmentIDNumber");
define('Field_Hobby',"Hobby");
define('Field_HomeAddressPOBox',"HomeAddressPOBox");
define('Field_Initials',"Initials");
define('Field_InternetFreeBusy',"InternetFreeBusy");
define('Field_Keywords',"Keywords");
define('Field_Language',"Language");
define('Field_Location',"Location");
define('Field_ManagerName',"ManagerName");
define('Field_Mileage',"Mileage");
define('Field_Notes',"Notes");
define('Field_OfficeLocation',"OfficeLocation");
define('Field_OrganizationalIDNumber',"OrganizationalIDNumber");
define('Field_OtherAddressPOBox',"OtherAddressPOBox");
define('Field_Priority',"Priority");
define('Field_Private',"Private");
define('Field_Profession',"Profession");
define('Field_ReferredBy',"ReferredBy");
define('Field_Sensitivity',"Sensitivity");
define('Field_Spouse',"Spouse");
define('Field_User1',"User1");
define('Field_User2',"User2");
define('Field_User3',"User3");
define('Field_User4',"User4");
define('Field_WebPage',"WebPage");



class Contact2 extends Contact {

	var $fields = array();
	
	function get($fieldId) {
	 	return isset($this->fields[$fieldId]) ? $this->fields[$fieldId] : null;
	}

	function put($fieldId, $val) {
	 	$this->fields[$fieldId] = $val;
	}

	function remove($fieldId) {
	 	unset($this->fields[$fieldId]);
	}
	
	function clear () {
		$this->fields = array();
	}
	
	function getAvailableFieldIds () {
		return array_keys($this->fields);
	}
	
	//function getEmail () {}
	//function getName () {}
}

class FieldMapping {
 	//Map of field ID to array of index into csv columns
 	var $map = array();
 	
 	function getIndices ($fieldId) {
		return isset($this->map[$fieldId]) ? $this->map[$fieldId] : array();
	}
 	
 	function getIndex ($fieldId) {
 	 	$r = $this->getIndices($fieldId);
 	 	return empty($r) ? -1 : $r[0];
	}
	
	function addIndex ($fieldId, $index) {
		if (!isset($this->map[$fieldId])) {
			$this->map[$fieldId] = array();
		}
		$this->map[$fieldId][] = $index;
	}
	
	function getFieldIds () {
		return array_keys($this->map);
	}
	
}

class FieldMatcher {
 	var $fieldId;
 	var $pattern;
 	
	function FieldMatcher($fieldId, $regex) {
		$this->fieldId = $fieldId;
		$this->pattern = $regex;
	}
	
	function getFieldId () {
		return $this-fieldId;
	}
	
	function matches ($columnName) {
		return preg_match($this->pattern, $columnName)>0;
	}
}


class CsvExtractor {
 
 	//var $OUTLOOKCSV_MATCHING;
 	//var $DEFAULT_MATCHING;

	//$fieldHeaders: Array of CSV header strings
	//Return: FieldMapping object
	function detectFields($fieldHeaders) {
	 	$theObj = null;
	 	
		$n = count($fieldHeaders);
	 	$lastMatchCount = 0;
		global $_ABI_FIELDMAPS;
	 	foreach ($_ABI_FIELDMAPS as $map) {
			$obj = new FieldMapping;
			$matchCount = 0;
			for ($i=0; $i<$n; $i++) {
				$f = $fieldHeaders[$i];
//				$f = mb_strtolower($f,"UTF-8");
				if (isset($map[$f])) {
					$cf = $map[$f];
					$obj->addIndex($cf, $i);
					$matchCount++;
				}
			}
			
			// If all fields are matched, then we don't need to do anything more
			// - it really is the best match
			if ($matchCount >= $lastMatchCount) {
				$lastMatchCount = $matchCount;
				$theObj = $obj;
			}
			// If all fields are matched, then we don't need to do anything more
			// - it really is the best match
			if ($matchCount == $n)
				break;
		}
		
		return $theObj;
	}
	
	function extractFromReader($csvreader, $fieldMapping) {
	 
	 	$cl = array();

		// ArrayList<Contact> al = new ArrayList<Contact>();
		$fieldIds = $fieldMapping->getFieldIds();

		//Read header
		while (true) {
		 	$cells = $csvreader->nextRow();
		 	if ($cells==false) break;

			$c = new Contact2;
			$n = count($cells);
			$nonBlanks = 0;
			foreach ($fieldIds as $fieldId) {
				$i = $fieldMapping->getIndex($fieldId);
				if ($i>=0 && $i<$n) {
				 	$s = $cells[$i];
				 	$c->put($fieldId,$s);
				 	if (!empty($s)) $nonBlanks++;
				}
			}
			if ($nonBlanks>0) $cl[] = $c;
		}
		
		return $cl;
	}

	function countFields ($csv, $delimiter) {
		$reader = new CsvReader($csv,$delimiter);
		$cells = $reader->nextRow();
		if ($cells==false) return 0;
		return count($cells);
	}
	
	function extract ($csv) {
		//Step 1: Detect character set?
		//Cannot do for now
		
		//Step 2: Detect delimiter (comma, tab, semicolon)
		$del = ",";
		$c2 = 0;	//Highest count
		$c = $this->countFields($csv,",");
		if ($c>$c2) {$c2=$c;$del=',';}
		$c = $this->countFields($csv,";");
		if ($c>$c2) {$c2=$c;$del=';';}
		$c = $this->countFields($csv,"\t");
		if ($c>$c2) {$c2=$c;$del="\t";}
		
		//Step 3: Detect fields
		//Read header
		$reader = new CsvReader($csv,$del);
		$cells = $reader->nextRow();
		if ($cells==false) {
		 	//No records??
		 	return array();
		}
		$mapping = $this->detectFields($cells);
		
		//Step 4: Extract fields
		return $this->extractFromReader($reader,$mapping);
	}
		
	function CsvExtractor () {
	}
}


?>
