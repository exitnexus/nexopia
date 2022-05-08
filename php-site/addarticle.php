<?

	$login=1;

	require_once("include/general.lib.php");

	$cats = & new category( $db, "cats");

	$branch = $cats->makebranch();

	switch($action){
		case "Preview":
		case "Post":

			addArticle($category,$title,$msg,$action);
			break;
	}

	incHeader();

	echo "<table align=center>";
	echo "<form method=post action=$_SERVER[PHP_SELF] name=editbox>";
	echo "<tr><td class=header colspan=2 align=center>Submit Article</td></tr>";
	echo "<tr><td class=body>Category:</td><td class=body><select class=body name=category><option value=0>Choose a Category". makeCatSelect($branch) . "</select></td></tr>";
	echo "<tr><td class=body>Subject:</td><td class=body><input class=body type=text name=title size=50 maxlength=255></td></tr>";
	echo "<tr><td class=body colspan=2>";
//	<textarea class=body name=msg cols=50 rows=10></textarea>
	editbox("",true);
	echo "</td></tr>";
	echo "<tr><td class=body colspan=2>Your article will not show up until it has been approved. You will receive a message when it is accepted or denied.</td></tr>";
	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value=Preview><input class=body type=submit name=action value=Post></td></tr>";
	echo "</table>";
	echo "</form>";

	incFooter();


function addArticle($category,$title,$msg,$action){
	global $branch, $cats,$msgs,$userData,$db, $mods;

	if(!isset($title) || strlen($title)<1){
		$action="Preview";
		$msgs->addMsg("Needs a Title");
	}
	if(strlen($title)>255){
		$action="Preview";
		$msgs->addMsg("Title is too short");
	}
	if(!isset($msg) || strlen($msg)<1){
		$action="Preview";
		$msgs->addMsg("No text");
	}
	if(strlen($msg)>65535){
		$action="Preview";
		$msgs->addMsg("Text is too long");
	}
	if(!isset($category) || !$cats->isValidCat($category)){
		$action="Preview";
		$msgs->addMsg("Bad category");
	}


	$ntitle = removeHTML($title);
	$ntitle = trim($ntitle);
	$narticle = removeHTML($msg);
	$narticle2 = parseHTML($narticle);
	$narticle3 = smilies($narticle2);
	$narticle3 = nl2br($narticle3);


	if($action=="Preview" || $ntitle=="" || (($narticle2 != $narticle || $ntitle != $title) && $action=="changed")){
		incHeader();

		echo "Some changes have been made (be it smilies, html removal, or code to html conversions). Here is a preview of what the article will look like:<hr><blockquote>\n";

		echo $ntitle;
		echo "<hr>";
		echo $narticle3;

		echo "</blockquote><hr>\n";

		echo "<table align=center cellspacing=0><form action=$_SERVER[PHP_SELF] method=post name=editbox>\n";
		echo "<tr><td class=body colspan=2>You can make any changes needed below:</td></tr>\n";
		echo "<tr><td class=body>Category:</td><td class=body><select class=body name=category><option value=0>Choose a Category" . makeCatSelect($branch,$category) . "</select></td></tr>";
		echo "<tr><td class=body>Title:</td><td class=body><input class=body type=text name=title value=\"$title\" size=40></td></tr>\n";
		echo "<tr><td class=body align=center colspan=2>";

		editbox($narticle,true);

//		<textarea class=header cols=50 rows=10 name=msg wrap=virtual>$narticle</textarea>
		echo "</td></tr>\n";
		echo "<tr><td class=body align=center colspan=2><input type=submit name=action value=Preview><input type=submit name=action value=Post></td></tr>\n";
		echo "</form></table>\n";

		incFooter();
		exit;
	}

	$db->prepare_query("SELECT id FROM articles WHERE authorid = ? && title = ? && text = ?", $userData['userid'], $ntitle, $narticle);

	if(!$db->numrows()){ //dupe detection
		$db->prepare_query("INSERT INTO articles SET author = ?, authorid = ?, submittime = ?, category = ?, title = ?, text = ?",//, ntext = ?",
									$userData['username'], $userData['userid'], time(), $category, $ntitle, $narticle);//, $narticle3);

		$articleID = $db->insertid();

		$mods->newItem(MOD_ARTICLE,$articleID);
	}

	header ("Location: articlelist.php");
	exit;
}



