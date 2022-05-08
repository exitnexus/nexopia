<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"faq"))
		die("Permission denied");

	if(!isset($action))
		$action="";

	switch($action){
		case "create":		create();								break;
		case "Add":			insert($title,$text,$category);			break;
		case "edit":		edit($id);								break;
		case "Update":		update($id,$title,$text,$category);		break;
		case "delete":		delete($id);							break;
	}

	listFAQ();
//////////////

function create(){
	global $PHP_SELF, $mods;

	$categories = & new category('faqcats');

	$branch = $categories->makeBranch();

	$mods->adminlog('create faq',"create faq");

	incHeader();

	echo "<table><form method=POST action=\"$PHP_SELF\">\n";
	echo "<tr><td class=body>Category</td><td class=body><select class=body name=category>" . makeCatSelect($branch) . "</select></td></tr>";
	echo "<tr><td class=body>Question</td><td class=body><input class=body type=text name=title size=50></td></tr>";
	echo "<tr><td class=body>Answer</td><td class=body><textarea cols=80 rows=10 name=text class=body></textarea></td></tr>";
	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value=Add><input class=body type=submit name=action value=Cancel></td></tr>\n";
	echo "</form></table>";

	incFooter();
	exit;
}

function insert($title,$text,$category){
	global $msgs, $db, $mods;

	$db->prepare_query("INSERT INTO faq SET title = ?, text = ?, parent = ?", $title, $text, $category);

	$id = $db->insertid();

	$mods->adminlog('insert faq',"insert faq $id");

	$msgs->addMsg("Faq entry added");
}

function delete($id){
	global $msgs, $db, $mods;

	if(!isset($id)) return false;

	$mods->adminlog('delete faq',"delete faq $id");

	$db->prepare_query("DELETE FROM faq WHERE id = ?", $id);

	$msgs->addMsg("Message Deleted");
}

function edit($id){
	global $msgs,$PHP_SELF, $db, $mods;

	if(!isset($id)) return false;

	$mods->adminlog('edit faq',"edit faq $id");

	$categories = & new category('faqcats');
	$branch = $categories->makeBranch();

	$query = $db->prepare_query("SELECT title,text,parent FROM faq WHERE id = ?", $id);
	$line = $db->fetchrow();

	incHeader();

	echo "<table><form method=POST action=\"$PHP_SELF\">\n";
	echo "<input type=hidden name=id value=\"$id\">";
	echo "<tr><td class=body>Category</td><td class=body><select class=body name=category>" . makeCatSelect($branch, $line['parent']) . "</select></td></tr>";
	echo "<tr><td class=body>Question</td><td class=body><input class=body type=text name=title size=50 value=\"" . htmlentities($line['title']) . "\"></td></tr>";
	echo "<tr><td class=body>Answer</td><td class=body><textarea cols=80 rows=10 name=text class=body>$line[text]</textarea></td></tr>";
	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value=Update><input class=body type=submit name=action value=Cancel></td></tr>\n";
	echo "</form></table>";

	incFooter();
	exit();
}

function update($id,$title,$text,$category){
	global $msgs, $db, $mods;

	if(!isset($id)) return false;

	$mods->adminlog('update faq',"update faq $id");

	$db->prepare_query("UPDATE faq SET title = ?, text = ?, parent = ? WHERE id = ?", $title, $text, $category, $id);

	$msgs->addMsg("Faq entry updated");
}



function listFAQ(){
	global $PHP_SELF,$db, $mods;

	$mods->adminlog('list faq',"List FAQ");

	$categories = & new category('faqcats');

	$branch = $categories->makeBranch();

	$db->query("SELECT * FROM faq ORDER BY 'priority'");

	$data=array();
	while($line = $db->fetchrow())
		$data[$line['parent']][$line['id']]=$line;

	if(isset($data[0]))
		$branch[] = array('id' => 0, 'depth' => 1, 'name' => 'Unfiled', 'parent' => 0, 'isparent' => 0);

	incHeader();

	echo "<table width=100%>\n";
	echo "<tr><td class=header>Functions</td><td class=header>Question</td></tr>\n";
	foreach($branch as $category){

		$questions = array();
		if(isset($data[$category['id']]))
			$questions = $data[$category['id']];

		echo "<td class=header colspan=2>"  . str_repeat("- ", $category['depth'] - 1 ) . "$category[name]</td></tr>";

		foreach($questions as $line){
			echo "<tr>";
			echo "<td class=body>";
			echo "<a class=body href=\"$PHP_SELF?action=edit&id=$line[id]\"><img src=/images/edit.gif border=0></a>";
			echo "<a class=body href=\"javascript:confirmLink('$PHP_SELF?action=delete&id=$line[id]','delete this faq entry')\"><img src=/images/delete.gif border=0></a>";
			echo "</td>";
			echo "<td class=body>" . str_repeat("- ", $category['depth']);
			echo "<a class=body href=faq.php?cat=$category[id]&q=$line[id]>";
			echo substr($line['title'],0,100);
			echo "</a>";
			echo "</td>\n";
			echo "</tr>";
		}
	}
	echo "<tr><td class=header colspan=2><a class=header href=$PHP_SELF?action=create>Add Question</a></td></tr>";
	echo "</table>";

	incFooter();
	exit;
}


