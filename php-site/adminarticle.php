<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isAdmin($userData['userid'],"articles"))
		die("You do not have permission to see this page");

	$sortlist = array(  'time' => "time",
						'id' => "articles.id",
						'authorid' => "",
						'author' => "author",
						'title' => "title",
						'catname' => "cats.name",
						'category' => ''
						);

	isValidSortt($sortlist,$sortt);
	isValidSortd($sortd,'DESC');


	$categories = & new category("cats");

	$branch = $categories->makebranch();

	if(!isset($action))
		$action="";

	switch($action){
		case "delete":
			$db->prepare_query("DELETE FROM articles WHERE id = ?", $id);
			$db->prepare_query("DELETE FROM comments WHERE itemid = ?", $id);
			$mods->deleteItem('articles',$id);
			break;
		case "edit":
			edit($id);
			break;
		case "Update":
		case "Preview":
			update($id,$category,$title,$msg,$action);
			break;
	}




	if(!isset($cat))
		$cat=0;

	$where = array();

	$where[] = "moded='y'";

	if($cat!=0){
		$catbranch = $categories->makebranch($cat);
		$cats=array();
		$cats[] = $cat;
		foreach($catbranch as $category)
			$cats[] = $category['id'];
		$where[] = $db->prepare("category IN (?)", $cats);
	}

	if(isset($search) && $search!=""){
//		$where[] = "(title LIKE '% " . str_replace('*','%',$search) . " %' || 	text LIKE '% " . str_replace('*','%',$search) . " %')";
		$where[] = "(title REGEXP '(^|^.* )" . $db->escape($search) . "(\$| .*\$)' || text LIKE '(^|^.* )" . $db->escape($search) . "(\$| .*\$)')";
	}else
		$search ='';


	$query = "SELECT count(*) FROM articles WHERE " . implode(" && ",$where);
	$result = $db->query($query);
	$Rows = $db->fetchfield();

	$numpages =  ceil($Rows / $config['linesPerPage']);

	if(!isset($page) || $page<0 || $page>=$numpages) $page=0;

	$where[] = "articles.category=cats.id";

	$query = "SELECT " . makeSortSelect($sortlist) . " FROM articles,cats WHERE " . implode(" && ",$where) . " ORDER BY $sortt $sortd LIMIT ".$page*$config['linesPerPage'].", $config[linesPerPage]";
	$result = $db->query($query);



	incHeader();

	$cols=6;

	echo "<table cellpadding=2 cellspacing=1 border=0 width=100%>\n";

	echo "<form action=$PHP_SELF>";
	echo "<tr><td colspan=$cols class=header>";
	echo "<select class=body name=cat onChange=\"location.href='$PHP_SELF?cat='+(this.options[this.selectedIndex].value)\"><option value=0>Choose a Category<option value=0>Home". makeCatSelect($branch,$cat) . "</select> ";
	echo "Search: <input class=body type=text name=search value='$search'><input class=body type=submit name=action value=Go>";
	echo "</td></form></tr>";





	echo "<td class=header></td>\n";
	$varlist = array('page' =>$page,'cat'=>$cat);
	makeSortTableHeader($sortlist,"Title","title",$varlist);
	makeSortTableHeader($sortlist,"By","author",$varlist);
	makeSortTableHeader($sortlist,"Date","time",$varlist);
	makeSortTableHeader($sortlist,"Category","category",$varlist);

	echo "</tr>\n";

	while ($line = $db->fetchrow($result)) {
		echo "<tr>";

		echo "<td class=body><a class=body href=$PHP_SELF?action=delete&id=$line[id]&cat=$cat&search=$search&page=$page&sortt=$sortt&sortd=$sortd><img src=/images/delete.gif border=0></a><a class=body href=$PHP_SELF?action=edit&id=$line[id]&cat=$cat&search=$search&page=$page&sortt=$sortt&sortd=$sortd><img src=/images/edit.gif border=0></a></td>";
		echo "<td class=body><a class=body href=\"article.php?sortd=$sortd&sortt=$sortt&page=$page&id=$line[id]\">$line[title]</a></td>";

		echo "<td class=body>";
		$uid = getUserId($line['author']);
		if($uid==$line['authorid'])
			echo "<a class=body href=profile.php?uid=$line[authorid]>$line[author]</a>";
		else
			echo "$line[author]";
		echo "</td>";

		echo "<td class=body>" . userdate("m/d/Y", $line['time']) . "</td>";
		echo "<td class=body>";

		$root = $categories->makeroot($line['category']);

		$cats = array();
		foreach($root as $category)
			$cats[] = "<a class=body href=articlelist.php?cat=$category[id]>$category[name]</a>";

		echo implode(" > ",$cats);

		echo "</td>";
		echo "</tr>\n";
	}

	echo "<tr><td colspan=$cols align=right class=header>Page: ";
	echo pageList("$PHP_SELF?sortt=$sortt&sortd=$sortd",$page,$numpages,'header');
	echo "</td></tr>\n";

	echo "</table>\n";


    incFooter();


function edit($id){
	global $cat,$search,$sortt,$sortd,$branch,$PHP_SELF, $db;


	$db->prepare_query("SELECT * FROM articles WHERE id = ?", $id);
	$data = $db->fetchrow();



	incHeader();
	echo "<table width=100% cellspacing=0><form action=\"$PHP_SELF\" method=post enctype=\"application/x-www-form-urlencoded\" name=editbox>\n";

	echo "<tr><td class=body align=center>Category: <select class=body name=category><option value=0>Choose a Category" . makeCatSelect($branch,$data['category']) . "</select></td></tr>";
	echo "<tr><td class=body align=center>Title: <input class=body type=text name=\"title\" value=\"$data[title]\" size=40></td></tr>\n";
	echo "<tr><td class=body align=center>";

	editbox($data['text'],true);

	echo "</td></tr>\n";
	echo "<input type=hidden name=id value=$id>";
	echo "<input type=hidden name=cat value=$cat>";
	echo "<input type=hidden name=search value=$search>";
	echo "<input type=hidden name=sortt value=$sortt>";
	echo "<input type=hidden name=sortd value=$sortd>";
	echo "<tr><td class=body align=center><input type=submit name=action value=Preview> <input type=submit name=action value=Update></td></tr>\n";
	echo "</form></table>\n";


	incFooter();
	exit;
}

function update($id,$category,$title,$msg,$action){
	global $categories,$branch,$PHP_SELF,$msgs,$userData,$cat,$search,$sortt,$sortd, $db;

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
	if(!isset($category) || !$categories->isValidCat($category)){
		$action="Preview";
		$msgs->addMsg("Bad category");
	}

	$ntitle = removeHTML($title);
	$narticle = removeHTML($msg);
	$narticle2 = smilies($narticle);
	$narticle2 = parseHTML($narticle2);
	$narticle3 = nl2br($narticle2);

	if($action=="Preview" || (($narticle2 != $msg || $ntitle != $title) && $action=="changed")){
		incHeader();

		echo "Some changes have been made (be it smilies, html removal, or code to html conversions). Here is a preview of what the joke will look like:<hr><blockquote>\n";

		echo $ntitle;
		echo "<hr>";
		echo $narticle3;

		echo "</blockquote><hr>\n";

		echo "<table width=100% cellspacing=0><form action=\"$PHP_SELF\" method=post enctype=\"application/x-www-form-urlencoded\" name=editbox>\n";
		echo "<tr><td class=body>You can make any changes needed below:</td></tr>\n";
		echo "<tr><td class=body align=center>Category: <select name=category><option value=0>Choose a Category" . makeCatSelect($branch,$category) . "</select></td></tr>";
		echo "<tr><td class=body align=center>Title: <input class=body type=text name=\"title\" value=\"$title\" size=40></td></tr>\n";
		echo "<tr><td class=body align=center>";

		editbox($msg,true);

		echo "</td></tr>\n";
		echo "<input type=hidden name=id value=$id>";
		echo "<input type=hidden name=cat value=$cat>";
		echo "<input type=hidden name=search value=$search>";
		echo "<input type=hidden name=sortt value=$sortt>";
		echo "<input type=hidden name=sortd value=$sortd>";
		echo "<tr><td class=body align=center><input type=submit name=action value=Preview> <input type=submit name=action value=Update></td></tr>\n";
		echo "</form></table>\n";

		incFooter();
		exit;
	}

	$db->prepare_query("UPDATE articles SET category = ?, title = ?, text = ?, ntext = ? WHERE id = ?", $category, $ntitle, $narticle, $narticle3, $id);

}
