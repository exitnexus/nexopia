<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"categories"))
		die("Permission denied");


	$tables = array("cats" => array('name' => "Article Categories", 'db' => & $articlesdb),
					"locs" => array('name' => "Locations", 'db' => & $configdb),
					"faqcats" => array('name' => "Help Categories", 'db' => & $db),
					"interests" => array('name' => "Interests", 'db' => & $configdb));


	$catid = getREQval('catid','int');
	$table = getREQval('table');
	if($table && !isset($tables[$table]))
		$table = "";

	if($table){

		$categories = new category( $tables[$table]['db'], $table, '', false);

		switch($action){
			case "Add Category":
				if($catid && ($name = getPOSTval('name')))
					$tables[$table]['db']->prepare_query("INSERT INTO $table SET name = ?, parent = #", $name, $catid);

				break;
			case "Update":
				if(!$catid || !($parent = getPOSTval('parent', 'int')))
					 break;

				$res = $tables[$table]['db']->prepare_query("SELECT parent FROM $table WHERE id = #", $catid);
				$oldparent = $res->fetchfield();

				$branch = $categories->makebranch($catid);

				foreach($branch as $line){
					if($line['id'] == $parent){
						$parent = $oldparent;
						break;
					}
				}
				if($parent == $catid)
					$parent = $oldparent;

				$tables[$table]['db']->prepare_query("UPDATE $table SET name = ?, parent = # WHERE id = #", $name, $parent, $catid);

				break;
			case "Delete":
				if(!($checkID = getPOSTval('checkID', 'array')))
					break;

				foreach($checkID as $check)
					$categories->deleteBranch($check);

				break;
		}
	}

function dispBranch(&$data, $basedepth=0){
	global $table;
	foreach($data as $line){
		echo "<tr><td class=body>";
		echo "<input class=body type=checkbox name=checkID[] value=$line[id]>";
		for($i=0;$i<$line['depth']+$basedepth-1;$i++)
			echo "&nbsp;- ";
		echo "<a class=body href=$_SERVER[PHP_SELF]?catid=$line[id]&table=$table>$line[name]</a> ($line[depth])";
		echo "</td></tr>\n";
	}
}

function dispRoot(&$data){
	global $table;

	$depth=0;
	$maxdepth=count($data)-1;
	foreach($data as $line){
		if($depth==$maxdepth)
			$class = "header";
		else
			$class = "body";
		echo "<tr><td class=$class>";
		echo "<input class=body type=checkbox name=checkID[] value=$line[id]>";
		for($i=0;$i<$depth;$i++)
			echo "&nbsp;- ";
		echo "<a class=$class href=$_SERVER[PHP_SELF]?catid=$line[id]&table=$table>$line[name]</a> ($line[depth])";
		echo "</td></tr>\n";
		$depth++;
	}
}




	incHeader();


	echo "<table width=100%><form action=\"$_SERVER[PHP_SELF]\" method=post>\n";
	echo "<input type=hidden name=catid value=$catid>";
	echo "<tr>\n";
	echo "  <td class=header>Category Name</td>\n";
	echo "</tr>\n";

	$names = array_keys($tables);

	foreach($names as $name){
		if($name == $table){
			$root = $categories->makeroot($catid,$tables[$name]['name']);
			$branch = $categories->makebranch($catid);

			echo "<input type=hidden name=table value=\"$table\">";

			dispRoot($root);
			dispBranch($branch,count($root));
		}else
			echo "<tr><td class=body><a class=body href=$_SERVER[PHP_SELF]?table=$name><b>" . $tables[$name]['name'] . "</b></a></td></tr>";
	}


	echo "<tr><td class=header><input class=body type=submit name=action value=Delete> Note that 'delete' deletes all sub-categories of all selected categories</td></tr>";
	echo "</form></table>\n";

	if($table!=""){
		$dat=end($root);
		$name=$dat['name'];
		$parent = $dat['parent'];

		if($catid!=0){
			echo "<table><form action=\"$_SERVER[PHP_SELF]\" method=post>";
			echo "<input type=hidden name=catid value=$catid>";
			echo "<input type=hidden name=table value=$table>";
			echo "<tr><td class=header colspan=2>Edit $name</td></tr>";
			echo "<tr><td class=body>Category Name: </td><td class=body><input class=body type=text name=\"name\" value=\"$name\"></td></tr>\n";
			echo "<tr><td class=body>Parent: </td><td class=body><select class=body name=parent><option value=0>$tables[$table]" . makeCatSelect($categories->makeBranch(), $parent) . "</select></td></tr>";
			echo "<tr><td class=body></td><td class=body><INPUT class=body TYPE=submit name=action VALUE=\"Update\"></td><td></td></tr>\n";
			echo "</table></form>\n";
		}

		echo "<table><form action=\"$_SERVER[PHP_SELF]\" method=post>";
		echo "<input type=hidden name=catid value=$catid>";
		echo "<input type=hidden name=table value=$table>";
		echo "<tr><td class=header colspan=2>Add a SubCategory under $name</td></tr>";
		echo "<tr><td class=body>Category Name: </td><td class=body><input class=body type=text name=\"name\"></td></tr>\n";
		echo "<tr><td class=body></td><td class=body><INPUT class=body TYPE=submit name=action VALUE=\"Add Category\"></td><td></td></tr>\n";
		echo "</table></form>\n";
	}

	incFooter();

