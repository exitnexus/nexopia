<?

	$login=1;

	require_once("include/general.lib.php");

	if(empty($id) || !in_array($type,array("picabuse","userabuse","galleryabuse","forumpost")))
		die("Bad id or type");

	if($action=="Report" && !empty($id)){
		$reason = removeHTML($reason);

		switch($type){
			case "picabuse":		$type2 = MOD_PICABUSE;		break;
			case "userabuse":		$type2 = MOD_USERABUSE;		break;
			case "galleryabuse":	$type2 = MOD_GALLERYABUSE;	break;
			case "forumpost":		$type2 = MOD_FORUMPOST;		break;
		}

		$db->prepare_query("INSERT INTO abuse SET itemid = ?, reason = ?,userid = ?, type = ?", $id, $reason, $userData['userid'], $type2);

		$mods->newItem($type2,$id);

		incHeader();
		echo "Thanks for the report. <a class=body href=\"javascript:history.go(-2)\">Continue</a>";
		incFooter();
		exit;
	}

	incHeader();

	echo "<form action=$PHP_SELF>";

	switch($type){
		case "picabuse":
		case "userabuse":
			echo "Can you give a discription of why you think this is abuse?<br>Please give as much information as possible to make it easier for us to deal with.<br>If you are reporting a Faker, please report it in the <a class=body href=forumthreads.php?fid=39>Fakers Forum</a> and not via this form. If this user has been sending you abusive messages or comments, please use the Ignore function to prevent them from sending you messages or leaving you comments.";
			break;
		case "galleryabuse":
		case "forumpost":
			echo "Can you give a discription of why you think this is abuse?<br>Please give as much information as possible to make it easier for us to deal with.<br>\n";
			break;
	}

	echo "<textarea class=body cols=60 rows=6 name=reason></textarea><br>";
	echo "<input type=hidden name=id value=$id>";
	echo "<input type=hidden name=type value=$type>";
	echo "<input class=body type=submit name=action value=Report>";
	echo "</form>";

	incFooter();

