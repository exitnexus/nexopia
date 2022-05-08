<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"faq"))
		die("Permission denied");

	if(!isset($action))
		$action="";

	switch($action){
		case "create":		createFAQ();								break;
		case "Add":			insertFAQ($title,$text,$category);			break;
		case "edit":		editFAQ($id);								break;
		case "Update":		updateFAQ($id,$title,$text,$category);		break;
		case "delete":		deleteFAQ($id);							break;
	}

	listFAQ();
//////////////

function createFAQ(){
	global $mods;

	$categories = & new category( $db, 'faqcats');

	$branch = $categories->makeBranch();

	$mods->adminlog('create faq',"create faq");

	incHeader();

	echo "<table><form method=POST action=$_SERVER[PHP_SELF]>\n";
	echo "<tr><td class=body>Category</td><td class=body><select class=body name=category>" . makeCatSelect($branch) . "</select></td></tr>";
	echo "<tr><td class=body>Question</td><td class=body><input class=body type=text name=title size=50></td></tr>";
	echo "<tr><td class=body>Answer</td><td class=body><textarea cols=80 rows=10 name=text class=body></textarea></td></tr>";
	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value=Add><input class=body type=submit name=action value=Cancel></td></tr>\n";
	echo "</form></table>";

	incFooter();
	exit;
}

function insertFAQ($title,$text,$category){
	global $msgs, $db, $mods, $cache;

	$db->prepare_query("INSERT INTO faq SET title = ?, text = ?, parent = ?", $title, $text, $category);

	$id = $db->insertid();

	$cache->remove(array($category, "faqquestions-$category"));

	$mods->adminlog('insert faq',"insert faq $id");

	$msgs->addMsg("Faq entry added");
}

function deleteFAQ($id){
	global $msgs, $db, $mods, $cache;

	if(!isset($id)) return false;

	$mods->adminlog('delete faq',"delete faq $id");

	$db->prepare_query("SELECT parent FROM faq WHERE id = ?", $id);

	if(!$db->numrows())
		return false;

	$category = $db->fetchfield();

	$db->prepare_query("DELETE FROM faq WHERE id = ?", $id);

	$cache->remove(array($category, "faqquestions-$category"));
	$cache->remove(array($id, "faqans-$id"));

	$msgs->addMsg("Message Deleted");
}

function editFAQ($id){
	global $msgs, $db, $mods;

	if(!isset($id)) return false;

	$mods->adminlog('edit faq',"edit faq $id");

	$categories = & new category( $db, 'faqcats');
	$branch = $categories->makeBranch();

	$query = $db->prepare_query("SELECT title,text,parent FROM faq WHERE id = ?", $id);
	$line = $db->fetchrow();

	incHeader();

	echo "<table><form method=POST action=$_SERVER[PHP_SELF]>\n";
	echo "<input type=hidden name=id value=\"$id\">";
	echo "<tr><td class=body>Category</td><td class=body><select class=body name=category>" . makeCatSelect($branch, $line['parent']) . "</select></td></tr>";
	echo "<tr><td class=body>Question</td><td class=body><input class=body type=text name=title size=50 value=\"" . htmlentities($line['title']) . "\"></td></tr>";
	echo "<tr><td class=body>Answer</td><td class=body><textarea cols=80 rows=10 name=text class=body>$line[text]</textarea></td></tr>";
	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value=Update><input class=body type=submit name=action value=Cancel></td></tr>\n";
	echo "</form></table>";

	incFooter();
	exit();
}

function updateFAQ($id,$title,$text,$category){
	global $msgs, $db, $mods, $cache;

	if(!isset($id)) return false;

	$mods->adminlog('update faq',"update faq $id");

	$cache->remove(array($category, "faqquestions-$category"));
	$cache->remove(array($id, "faqans-$id"));

	$db->prepare_query("UPDATE faq SET title = ?, text = ?, parent = ? WHERE id = ?", $title, $text, $category, $id);

	$msgs->addMsg("Faq entry updated");
}



function listFAQ(){
	global $db, $mods, $config;

	$mods->adminlog('list faq',"List FAQ");

	$categories = & new category( $db, 'faqcats');

	$branch = $categories->makeBranch();

	$db->query("SELECT id, parent, priority, title FROM faq ORDER BY 'priority'");

	$data=array();
	while($line = $db->fetchrow())
		$data[$line['parent']][$line['id']]=$line;

	if(isset($data[0]))
		$branch[] = array('id' => 0, 'depth' => 1, 'name' => 'Unfiled', 'parent' => 0, 'isparent' => 0);

	incHeader();

	echo "<table width=100%>\n";
	echo "<tr><td class=header>Functions</td><td class=header>Priority</td><td class=header>Question</td></tr>\n";
	foreach($branch as $category){

		$questions = array();
		if(isset($data[$category['id']]))
			$questions = $data[$category['id']];

		echo "<td class=header colspan=3>"  . str_repeat("- ", $category['depth'] - 1 ) . "<b>$category[name]</b></td></tr>";

		foreach($questions as $line){
			echo "<tr>";
			echo "<td class=body>";
			echo "<a class=body href=\"$_SERVER[PHP_SELF]?action=edit&id=$line[id]\"><img src=$config[imageloc]edit.gif border=0></a>";
			echo "<a class=body href=\"javascript:confirmLink('$_SERVER[PHP_SELF]?action=delete&id=$line[id]','delete this faq entry')\"><img src=$config[imageloc]delete.gif border=0></a>";
			echo "</td>";
			echo "<td class=body>$line[priority]</td>";
			echo "<td class=body>" . str_repeat("- ", $category['depth']);
			echo "<a class=body href=faq.php?cat=$category[id]&q=$line[id]>";
			echo substr($line['title'],0,100);
			echo "</a>";
			echo "</td>\n";
			echo "</tr>";
		}
	}
	echo "<tr><td class=header colspan=3><a class=header href=$_SERVER[PHP_SELF]?action=create>Add Question</a></td></tr>";
	echo "</table>";

	incFooter();
	exit;
}


