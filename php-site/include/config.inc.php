<?

	$cookiedomain = '.www.nexopia.com';
	$wwwdomain = 'www.nexopia.com';
	$pluswwwdomain = 'beta.www.nexopia.com';
	$emaildomain = 'nexopia.com';


	$sitebasedir = "/home/nexopia";
	$masterserver = "$sitebasedir/master";
	$docRoot = "$sitebasedir/public_html";

	$databases = array();

	$databases["insert"] = array(	"host" => "192.168.0.100",
									"login" => "enternexus",
									"passwd" => 'CierlU$I',
									"db" => "enternexus" );
//	if($HTTP_HOST != $pluswwwdomain){
		$databases["select"][] = array(	"weight" => 1,
										"host" => "192.168.0.100",
										"login" => "enternexus",
										"passwd" => 'CierlU$I',
										"db" => "enternexus" );
/*	}else{ //plus
		$databases["select"][] = array(	"weight" => 1,
										"host" => "192.168.0.110",
										"login" => "enternexus",
										"passwd" => 'CierlU$I',
										"db" => "enternexus" );
	}
*/
	$databases["fast"] = array(		"host" => "192.168.0.110",
									"login" => "enternexus",
									"passwd" => 'CierlU$I',
									"db" => "enternexusfast" );

	$databases["backup"] = array(					"host" => "192.168.0.110",
									"login" => "enternexus",
									"passwd" => 'CierlU$I',
									"db" => "enternexus" );


	$contactemails = array(
		"Information" => "info@nexopia.com",
		"Webmaster" => "webmaster@nexopia.com",
		"Admin" => "admin@nexopia.com",
		"Help" => "help@nexopia.com",
		"Sales" => "sales@nexopia.com"
		);



	$debuginfousers = array(1,21);

/*

	$cookiedomain = 'enternexus.fobax.sytes.net';
	$wwwdomain = 'enternexus.fobax.sytes.net';
	$pluswwwdomain = 'fastcgi.enternexus.fobax.sytes.net';
	$emaildomain = 'enternexus.fobax.sytes.net';


	$sitebasedir = "/htdocs/enternexus";
	$masterserver = "$sitebasedir/master";
	$docRoot = "$sitebasedir/public_html";

	$databases = array();

	$databases["insert"] = array(	"host" => "localhost",
									"login" => "root",
									"passwd" => "Hawaii",
									"db" => "enternexus" );
	if($HTTP_HOST == $wwwdomain){
		$databases["select"][] = array(	"weight" => 1,
										"host" => "localhost",
										"login" => "root",
										"passwd" => "Hawaii",
										"db" => "enternexus" );
	}else{ //plus
		$databases["select"][] = array(	"weight" => 1,
										"host" => "localhost",
										"login" => "root",
										"passwd" => "Hawaii",
										"db" => "enternexus" );
	}

	$databases["fast"] = array(		"host" => "localhost",
									"login" => "root",
									"passwd" => "Hawaii",
									"db" => "enternexusfast" );


	$contactemails = array(
		"Information" => "timo@tzc.com",
		"Webmaster" => "timo@tzc.com",
		"Admin" => "timo@tzc.com",
		"Help" => "timo@tzc.com",
		"Sales" => "timo@tzc.com"
		);

	$debuginfousers = array(5,7);

*/
