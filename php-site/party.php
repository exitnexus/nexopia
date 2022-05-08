<?
/*

CREATE TABLE `party` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `item` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

*/

	$login=0;

	require_once("include/general.lib.php");

	$isAdmin=false;
	if($userData['loggedIn'] && ($userData['userid']==91 || $userData['userid']==1)) // jon or me
		$isAdmin=true;
	
	sqlSafe(&$id,&$name,&$item);
	
	if($isAdmin){
		switch($action){
			case "Add":
				$query = "INSERT INTO party SET name='$name',item='$item'";
				mysql_query($query);
				
				$msgs->addMsg("Added");
				
				break;
			case "Delete":
				$query = "DELETE FROM party WHERE id IN ('" . implode("','",$checkID) . "')";
				mysql_query($query);
				
				$msgs->addMsg("Deleted");
				
				break;
			case "edit":
				$query = "SELECT * FROM party WHERE id='$id'";
				$result = mysql_query($query);
				$line = mysql_fetch_assoc($result);
				
				incHeader();

				echo "<table><form action=$_SERVER[PHP_SELF]>";
				echo "<tr><td class=header colspan=2>Add row</td></tr>";
				echo "<tr><td class=body>Item</td><td class=body><input class=body type=text name=item value='$line[item]'></td></tr>";
				echo "<tr><td class=body>Person</td><td class=body><input class=body type=text name=name value='$line[name]'></td></tr>";
				echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value=Update><input class=body type=submit name=action value=Cancel></td></tr>";
				echo "</form></table>";

				incFooter();
				exit;
			case "Update":
				$query = "UPDATE party SET name='$name',item='$item'";
				mysql_query($query);
				
				$msgs->addMsg("Updated");
				
				break;
		}
	}
		
	$query = "SELECT * FROM party";
	$result = mysql_query($query);
	
	incHeader();
	
	echo "<table>";
	if($isAdmin)
		echo "<form action=$_SERVER[PHP_SELF] method=get>";
	
	echo "<tr>";
	if($isAdmin)
		echo "<td class=header></td>";
	echo "<td class=header>Item</td><td class=header>Person</td></tr>";
	
	while($line = mysql_fetch_assoc($result)){
		echo "<tr>";
		if($isAdmin)
			echo "<td class=body><input type=checkbox name=checkID[] value=$line[id]></td>";
		echo "<td class=body>";
		if($isAdmin)
			echo "<a class=body href=$_SERVER[PHP_SELF]?action=edit&id=$line[id]>";
		echo "$line[item]";
		if($isAdmin)
			echo "</a>";
		echo "</td><td class=body>$line[name]</td></tr>";
	}
	if($isAdmin)
		echo "<tr><td colspan=3 class=header><input class=body type=submit name=action value=Delete></td></tr></form>";
	echo "</table>";
	
	if($isAdmin){
		echo "<table><form action=$_SERVER[PHP_SELF]>";
		echo "<tr><td class=header colspan=2>Add row</td></tr>";
		echo "<tr><td class=body>Item</td><td class=body><input class=body type=text name=item></td></tr>";
		echo "<tr><td class=body>Person</td><td class=body><input class=body type=text name=name></td></tr>";
		echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value=Add></td></tr>";
		echo "</form></table>";
	}
	
	incFooter();
